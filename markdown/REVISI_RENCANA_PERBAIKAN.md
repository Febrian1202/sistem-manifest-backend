# Rencana Perbaikan Sistem — REVISI

## Sistem Informasi Manifest Lisensi Perangkat Lunak untuk Mencegah Pelanggaran Hak Cipta di Lingkungan USN Kolaka

Dokumen ini merincikan seluruh perubahan teknis yang perlu dilakukan pada aplikasi agar sesuai dengan arahan revisi dosen penguji.

---

## Ringkasan Perubahan

| No | Perubahan | Dampak | Prioritas |
|----|----------|--------|-----------|
| 1 | Tambah role `kepala_lab` (PJ Lab) | Model, Seeder, Middleware, Routes | Tinggi |
| 2 | Tambah konsep "Laboratorium" sebagai entitas | Migration, Model, Relasi | Tinggi |
| 3 | Tambah fitur Approval/Review laporan | Migration, Model, Controller, View | Tinggi |
| 4 | Asosiasi PJ Lab dengan Laboratorium | Migration, Model, Controller | Tinggi |
| 5 | Filter laporan Pimpinan berdasarkan approval | Controller, View, Export | Sedang |
| 6 | Revisi dashboard per role | Controller, View | Sedang |
| 7 | Hapus konsep "Agen Scanner sebagai entitas" di UI | View, Dokumentasi | Rendah |
| 8 | Modifikasi Agent Scanner — tambah `laboratory_id` di config.json | API, PowerShell Script, Controller | Tinggi |
| 9 | Preview PDF di halaman review PJ Lab | Controller, View, Export | Sedang |

---

## Keputusan Desain

Berikut keputusan desain yang telah disepakati melalui diskusi:

| No | Keputusan | Detail |
|----|----------|--------|
| 1 | Approval bersifat **per lab per periode** | Tidak bisa approve sebagian komputer. Semua data lab dalam satu periode di-approve/reject secara utuh. |
| 2 | **Preview PDF** tersedia di halaman review | PJ Lab bisa melihat bentuk laporan final sebelum memberikan keputusan approve/reject. |
| 3 | **Tidak bisa revoke** approval | Sekali approve, bersifat final. Jika Admin mengirim ulang laporan di periode yang sama, sistem membuat record approval baru dengan status `pending`. |
| 4 | Admin **tidak perlu** dikaitkan ke laboratorium | Admin punya akses ke semua lab. Hanya role `kepala_lab` yang wajib punya `laboratory_id`. |
| 5 | Hanya **Admin** yang bisa CRUD laboratorium dan akun PJ Lab | PJ Lab hanya bisa: lihat inventaris lab sendiri, review & approve/reject laporan, ubah password sendiri. |
| 6 | Agent Scanner menyertakan `laboratory_id` di `config.json` | Saat download agent, Admin memilih lab tujuan. Komputer otomatis terasosiasi ke lab sejak registrasi pertama. |
| 7 | Pengiriman laporan ke PJ Lab di-trigger **manual oleh Admin** | Scan harian hanya mengupdate data compliance. Admin mengirim laporan ke PJ Lab secara periodik (misal akhir bulan) ketika data sudah lengkap. Ini mencegah PJ Lab dibanjiri notifikasi harian. |

---

## FASE 1: Fondasi Data (Database & Model)

### 1.1 Migration — Tabel `laboratories`

Tabel baru untuk merepresentasikan laboratorium sebagai entitas yang dikelola.

```php
// database/migrations/xxxx_create_laboratories_table.php
Schema::create('laboratories', function (Blueprint $table) {
    $table->id();
    $table->string('name');            // "Lab Komputer 1", "Lab Jaringan", dll
    $table->string('code')->unique();  // "LAB-01", "LAB-JRG", dll
    $table->string('building')->nullable();  // Gedung
    $table->string('floor')->nullable();     // Lantai
    $table->text('description')->nullable();
    $table->timestamps();
});
```

**Alasan:** Saat ini field `location` di tabel `computers` hanya berupa string bebas. Dengan tabel `laboratories`, lokasi menjadi terstruktur dan bisa diasosiasikan dengan PJ Lab.

### 1.2 Migration — Tambah `laboratory_id` di `computers`

```php
// database/migrations/xxxx_add_laboratory_id_to_computers_table.php
Schema::table('computers', function (Blueprint $table) {
    $table->foreignId('laboratory_id')->nullable()->constrained('laboratories')->nullOnDelete();
});
```

**Catatan:** Field `location` (string) yang sudah ada tetap dipertahankan untuk backward compatibility dan bisa digunakan sebagai deskripsi tambahan. `laboratory_id` menjadi referensi formal.

### 1.3 Migration — Tambah `laboratory_id` di `users` (untuk PJ Lab)

```php
// database/migrations/xxxx_add_laboratory_id_to_users_table.php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('laboratory_id')->nullable()->constrained('laboratories')->nullOnDelete();
});
```

**Alasan:** Satu PJ Lab bertanggung jawab atas satu laboratorium. Field ini nullable karena Admin dan Pimpinan tidak terikat ke lab tertentu.

### 1.4 Migration — Tabel `report_approvals`

Tabel baru untuk menyimpan riwayat persetujuan/penolakan laporan oleh PJ Lab.

```php
// database/migrations/xxxx_create_report_approvals_table.php
Schema::create('report_approvals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('laboratory_id')->constrained('laboratories')->cascadeOnDelete();
    $table->foreignId('reviewed_by')->constrained('users')->cascadeOnDelete(); // PJ Lab user
    $table->string('report_type');         // 'kepatuhan', 'eksekutif', 'komputer', 'software', 'lisensi'
    $table->string('period');              // '2026-07' (tahun-bulan)
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->text('notes')->nullable();     // Catatan review PJ Lab
    $table->timestamp('reviewed_at')->nullable();
    $table->timestamps();

    $table->index(['laboratory_id', 'report_type', 'period']);
});
```

**Catatan:** Tidak menggunakan `unique` constraint pada kombinasi (laboratory_id, report_type, period) karena satu lab bisa punya beberapa record approval di periode yang sama (misal: approved lalu ada re-scan sehingga muncul pending baru). Yang ditampilkan ke sistem adalah record **terbaru** per kombinasi tersebut.

### 1.5 Model — `Laboratory`

```php
// app/Models/Laboratory.php
class Laboratory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'building', 'floor', 'description'];

    public function computers() { return $this->hasMany(Computer::class); }
    public function penanggungJawab() { return $this->hasMany(User::class); }
    public function reportApprovals() { return $this->hasMany(ReportApproval::class); }
}
```

### 1.6 Model — `ReportApproval`

```php
// app/Models/ReportApproval.php
class ReportApproval extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'laboratory_id', 'reviewed_by', 'report_type',
        'period', 'status', 'notes', 'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function laboratory() { return $this->belongsTo(Laboratory::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
```

### 1.7 Update Model — `Computer`

Tambah relasi ke Laboratory:

```php
// Di app/Models/Computer.php, tambah:
public function laboratory()
{
    return $this->belongsTo(Laboratory::class);
}
```

### 1.8 Update Model — `User`

Tambah relasi ke Laboratory:

```php
// Di app/Models/User.php, tambah:
public function laboratory()
{
    return $this->belongsTo(Laboratory::class);
}
```

---

## FASE 2: Role & Permission

### 2.1 Update `RoleAndPermissionSeeder`

Tambah role `kepala_lab` dengan permission yang sesuai:

```php
// Permissions baru yang perlu ditambahkan:
'manage laboratories'    // CRUD laboratorium (admin only)
'review reports'         // Review & approve/reject laporan (kepala_lab)
'view lab inventory'     // Lihat inventaris lab sendiri (kepala_lab)

// Role baru:
$kepalaLab = Role::create(['name' => 'kepala_lab', 'guard_name' => 'web']);
$kepalaLab->givePermissionTo([
    'access admin panel',
    'view reports',
    'review reports',
    'view lab inventory',
]);

// Update role admin — tambah permission baru:
$admin->givePermissionTo('manage laboratories');
```

### 2.2 Mapping Role → Entitas DFD

| Role Sistem | Entitas DFD | Hak Akses |
|------------|-------------|-----------|
| `admin` | Admin / Staff | Full CRUD, scan, kelola user, kelola lab, kelola lisensi |
| `kepala_lab` | Penanggung Jawab Lab | View inventaris lab sendiri, review & approve laporan |
| `pimpinan` | Pimpinan | View dashboard & laporan yang sudah approved |

### 2.3 Default User Seeder

Tambahkan default user PJ Lab:

```php
$kepalaLab = User::create([
    'name' => 'Kepala Lab Komputer',
    'email' => 'kepalalab@usn.ac.id',
    'password' => Hash::make(env('DEFAULT_USER_PASSWORD', 'ManifestUSN_2026!')),
]);
$kepalaLab->assignRole('kepala_lab');
// laboratory_id di-assign via UI setelah tabel laboratories terisi
```

---

## FASE 3: Routes & Middleware

### 3.1 Penambahan Route Group untuk Kepala Lab

```php
// routes/web.php

// === Route yang bisa diakses semua role ===
Route::middleware(['auth', 'role:admin|kepala_lab|pimpinan'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// === Route khusus Admin/Staff (operasional) ===
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Semua route CRUD yang sudah ada tetap di sini
    // Tambah: CRUD Laboratorium
    Route::resource('laboratories', LaboratoryController::class);
    // Scan komputer (yang sebelumnya dilakukan "agen")
    Route::post('/computers/{computer}/request-scan', ...);
    Route::post('/computers/request-scan-all', ...);
    // Kirim laporan ke PJ Lab (trigger manual)
    Route::get('/reports/submit-to-lab', [ReportSubmissionController::class, 'index']);
    Route::post('/reports/submit-to-lab', [ReportSubmissionController::class, 'submit']);
});

// === Route khusus Kepala Lab ===
Route::middleware(['auth', 'role:kepala_lab'])->group(function () {
    // Inventaris lab sendiri
    Route::get('/lab/inventory', [LabInventoryController::class, 'index']);
    Route::get('/lab/inventory/{computer}', [LabInventoryController::class, 'show']);

    // Review & Approval laporan
    Route::get('/lab/reports', [ReportApprovalController::class, 'index']);
    Route::get('/lab/reports/{reportApproval}', [ReportApprovalController::class, 'show']);
    Route::get('/lab/reports/{reportApproval}/preview-pdf', [ReportApprovalController::class, 'previewPdf']);
    Route::post('/lab/reports/{reportApproval}/approve', [ReportApprovalController::class, 'approve']);
    Route::post('/lab/reports/{reportApproval}/reject', [ReportApprovalController::class, 'reject']);
});

// === Route khusus Pimpinan (view approved reports) ===
Route::middleware(['auth', 'role:pimpinan'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index']); // hanya approved
    Route::get('/reports/{type}/preview', [ReportController::class, 'preview']);
    Route::get('/reports/{type}/export/{format}', [ReportController::class, 'export']);
});
```

### 3.2 API Routes — Modifikasi Registrasi

Route API endpoint registrasi perlu dimodifikasi untuk menerima `laboratory_id`:

```php
// routes/api.php — endpoint tetap sama, tapi payload berubah
POST /api/agent/register
// Body sebelumnya: { hostname, mac_address }
// Body sesudahnya: { hostname, mac_address, laboratory_id }
```

Perubahan di `AgentRegisterController`:
```php
// Tambah validasi:
'laboratory_id' => 'required|exists:laboratories,id'

// Saat updateOrCreate Computer, tambah laboratory_id:
Computer::updateOrCreate(
    ['mac_address' => $validated['mac_address']],
    [
        'hostname' => $validated['hostname'],
        'laboratory_id' => $validated['laboratory_id'], // BARU
        // ... field lainnya
    ]
);
```

---

## FASE 3.5: Modifikasi Agent Scanner

### 3.5.1 Update `AgentDownloadController`

Halaman download agent perlu menampilkan dropdown pilih laboratorium sebelum download:

```php
// Di AgentDownloadController:
public function showDownloadPage()
{
    $laboratories = Laboratory::orderBy('name')->get();
    return view('agent-download', compact('laboratories'));
}

public function download(Request $request)
{
    $request->validate(['laboratory_id' => 'required|exists:laboratories,id']);
    $lab = Laboratory::findOrFail($request->laboratory_id);

    // Generate config.json dengan laboratory_id
    $config = [
        'baseUrl' => config('app.url'),
        'registrationKey' => env('AGENT_REGISTRATION_KEY'),
        'laboratoryId' => $lab->id,           // BARU
        'laboratoryName' => $lab->name,       // BARU (untuk referensi)
    ];

    // ... buat ZIP seperti biasa, tapi config.json menyertakan lab info
}
```

### 3.5.2 Update Script PowerShell (`scanner.ps1`)

Script scanner perlu membaca `laboratoryId` dari config.json dan mengirimkannya saat registrasi:

```powershell
# Baca config
$config = Get-Content "config.json" | ConvertFrom-Json

# Saat registrasi, sertakan laboratory_id
$regBody = @{
    hostname       = $env:COMPUTERNAME
    mac_address    = $mac
    laboratory_id  = $config.laboratoryId   # BARU
} | ConvertTo-Json
```

---

## FASE 4: Controller & Logika Bisnis

### 4.1 Controller Baru — `LaboratoryController`

```
app/Http/Controllers/LaboratoryController.php
```

CRUD laboratorium (admin only):
- `index()` — Daftar semua laboratorium dengan jumlah komputer dan PJ Lab
- `create()` / `store()` — Tambah lab baru
- `edit()` / `update()` — Edit informasi lab
- `destroy()` — Hapus lab (cek apakah ada komputer terkait)

### 4.2 Controller Baru — `LabInventoryController`

```
app/Http/Controllers/LabInventoryController.php
```

View inventaris khusus PJ Lab (scoped ke lab sendiri):
- `index()` — Daftar komputer di lab miliknya + ringkasan kepatuhan
- `show()` — Detail komputer + software terinstal + status kepatuhan

**Penting:** Query di-scope berdasarkan `auth()->user()->laboratory_id`.

### 4.3 Controller Baru — `ReportApprovalController`

```
app/Http/Controllers/ReportApprovalController.php
```

Fitur review dan approval laporan untuk PJ Lab:
- `index()` — Daftar laporan yang perlu di-review (status pending) + riwayat approval
- `show()` — Preview detail laporan kepatuhan per lab per periode. Menampilkan:
  - Ringkasan: total komputer, sudah/belum scan, tingkat kepatuhan lab
  - Daftar temuan pelanggaran (software terlarang, tanpa lisensi, expired, kuota penuh)
  - Daftar seluruh komputer dengan status kepatuhan masing-masing
- `previewPdf()` — **Preview PDF** laporan sebelum approve (PJ Lab bisa lihat bentuk final dokumen)
- `approve()` — Set status `approved` + catatan opsional + timestamp. Bersifat **final** (tidak bisa di-revoke).
- `reject()` — Set status `rejected` + alasan penolakan **wajib diisi** + timestamp.

**Aturan approval:**
- Approval bersifat **per lab per periode** — semua atau tidak sama sekali, tidak bisa approve sebagian komputer.
- Sekali approved, **tidak bisa di-revoke**. Jika Admin mengirim ulang laporan di periode yang sama, sistem membuat record approval baru dengan status `pending`.
- Catatan review (`notes`) opsional saat approve, **wajib** saat reject.

**Scope keamanan:**
- Semua query di-scope ke `auth()->user()->laboratory_id` — PJ Lab hanya bisa melihat dan meng-approve laporan lab sendiri.
- PJ Lab **tidak bisa** mengubah data apapun selain status approval dan catatan review.

### 4.4 Controller Baru — `ReportSubmissionController`

```
app/Http/Controllers/ReportSubmissionController.php
```

Fitur pengiriman laporan ke PJ Lab (admin only):
- `index()` — Halaman "Kirim Laporan ke PJ Lab". Menampilkan daftar semua laboratorium beserta:
  - Jumlah komputer total dan yang sudah ter-scan di periode tersebut
  - Status kesiapan (siap / belum lengkap)
  - Status pengiriman terakhir (belum dikirim / pending review / approved / rejected)
- `submit()` — Buat record `report_approvals` (status: pending) untuk lab yang dipilih.
  - Validasi: tidak bisa kirim jika masih ada record pending untuk lab + periode tersebut.
  - Bisa kirim ulang jika record sebelumnya sudah approved/rejected (buat record baru).

### 4.5 Update `ReportController`

Modifikasi agar laporan yang ditampilkan ke Pimpinan hanya yang sudah approved:

```php
// Pada setiap method preview dan export:
if (auth()->user()->hasRole('pimpinan')) {
    // Filter hanya compliance data dari lab yang sudah approved
    $approvedLabIds = ReportApproval::where('status', 'approved')
        ->where('report_type', $type)
        ->where('period', $period)
        ->pluck('laboratory_id');

    $query->whereHas('computer', fn($q) =>
        $q->whereIn('laboratory_id', $approvedLabIds)
    );
}
```

### 4.6 Update `DashboardController`

Sesuaikan dashboard per role:

```php
public function index()
{
    $user = auth()->user();

    if ($user->hasRole('kepala_lab')) {
        return $this->labDashboard($user);
    }

    if ($user->hasRole('pimpinan')) {
        return $this->pimpinanDashboard(); // hanya data approved
    }

    return $this->adminDashboard(); // full data
}
```

- **Admin Dashboard:** Sama seperti sekarang (semua data).
- **Kepala Lab Dashboard:** Hanya data lab sendiri — jumlah komputer, software terdeteksi, skor kepatuhan lab, laporan pending review.
- **Pimpinan Dashboard:** Hanya data aggregat dari lab yang sudah approved.

### 4.7 Update `AccountController`

Tambah field `laboratory_id` dan role `kepala_lab` di CRUD akun:

```php
// Di form create/edit user:
// - Tambah dropdown "Role" dengan opsi: admin, kepala_lab, pimpinan
// - Tambah dropdown "Laboratorium" (tampil hanya jika role = kepala_lab)
// - Validasi: jika role = kepala_lab, laboratory_id wajib diisi
```

---

## FASE 5: Views (Blade Templates)

### 5.1 View Baru

| File | Deskripsi |
|------|-----------|
| `resources/views/laboratories/index.blade.php` | Daftar laboratorium (admin) |
| `resources/views/laboratories/create.blade.php` | Form tambah lab (admin) |
| `resources/views/laboratories/edit.blade.php` | Form edit lab (admin) |
| `resources/views/lab-inventory/index.blade.php` | Inventaris lab — daftar komputer + ringkasan kepatuhan (PJ Lab) |
| `resources/views/lab-inventory/show.blade.php` | Detail komputer + software terinstal + status kepatuhan (PJ Lab) |
| `resources/views/report-approvals/index.blade.php` | Daftar laporan pending + riwayat approval (PJ Lab) |
| `resources/views/report-approvals/show.blade.php` | Halaman review laporan: ringkasan, temuan, daftar komputer, form approve/reject (PJ Lab) |
| `resources/views/report-approvals/preview-pdf.blade.php` | Preview PDF laporan sebelum approve (PJ Lab) |
| `resources/views/report-submissions/index.blade.php` | Halaman "Kirim Laporan ke PJ Lab" — daftar lab + status kesiapan (Admin) |

### 5.2 View yang Perlu Dimodifikasi

| File | Perubahan |
|------|-----------|
| Sidebar/Navigasi | Tambah menu untuk PJ Lab (Inventaris Lab, Review Laporan). Tambah menu Admin "Kirim Laporan ke PJ Lab". |
| Dashboard | Conditional rendering berdasarkan role |
| Computer list/show | Tampilkan nama Laboratorium (bukan hanya string location) |
| Account create/edit | Tambah field role `kepala_lab` + pilih laboratorium |
| Reports | Tampilkan badge status approval, filter by lab |
| Agent download | Ubah narasi dari "Agen Scanner" menjadi "Tools Pemindai" yang dioperasikan Staff. Tambah **dropdown pilih laboratorium** sebelum download ZIP. |

### 5.3 Perubahan Narasi UI

Semua referensi ke "Agen Scanner" sebagai entitas mandiri diganti menjadi bahasa yang menunjukkan ini adalah tools:

| Sebelumnya | Sesudahnya |
|-----------|-----------|
| "Agen Scanner" | "Tools Pemindai" / "Scanner" |
| "Komputer klien mengirim data" | "Admin menjalankan scan pada komputer" |
| "Agen terdaftar" | "Komputer terdaftar (scanner aktif)" |

---

## FASE 6: Workflow Approval — Alur Kerja Detail

### Alur Lengkap (End-to-End)

```
HARIAN (otomatis, background):
  1. Agent scanner jalan sesuai schedule di komputer lab
         ↓
  2. Data scan masuk ke sistem (via API)
         ↓
  3. Sistem memproses: normalisasi → validasi lisensi → evaluasi kepatuhan
         ↓
  4. Hasil kepatuhan tersimpan di compliance_reports
         ↓
  5. Data terakumulasi di dashboard Admin (belum dikirim ke PJ Lab)

PERIODIK (manual oleh Admin, misal akhir bulan):
  6. Admin buka halaman "Kirim Laporan ke PJ Lab"
         ↓
  7. Admin melihat daftar semua lab + status kesiapan scan
     (berapa komputer sudah scan / total komputer per lab)
         ↓
  8. Admin pilih lab yang siap → klik "Kirim"
         ↓
  9. Sistem buat record report_approvals (status: pending)
     untuk lab yang dipilih
         ↓
  10. PJ Lab melihat ada laporan pending di dashboard-nya
         ↓
  11. PJ Lab review inventaris, temuan, preview PDF
         ↓
  12. PJ Lab approve / reject laporan
         ↓
  13. Jika approved → laporan tersedia untuk Pimpinan
      Jika rejected → Admin tindak lanjut, kirim ulang nanti
         ↓
  14. Pimpinan melihat dashboard & mengunduh laporan final
```

### Mengapa Tidak Otomatis Setelah Scan?

Agent scanner di-schedule untuk berjalan **setiap hari**. Jika setiap scan selesai langsung generate approval pending, PJ Lab akan dibanjiri notifikasi harian untuk data yang mungkin belum berubah signifikan. Pendekatan manual oleh Admin memberikan kontrol:

- Admin bisa memastikan **semua komputer di lab sudah ter-scan** sebelum mengirim
- Admin bisa menunggu sampai **data lisensi sudah diinput lengkap**
- Pengiriman terjadi **sekali per periode** (bukan setiap hari)
- PJ Lab hanya review laporan yang sudah siap, bukan data parsial

### Halaman "Kirim Laporan ke PJ Lab" (Admin Only)

```
Route: POST /reports/submit-to-lab
Controller: ReportSubmissionController

Halaman menampilkan:
┌─────────────────────────────────────────────────────────┐
│  Kirim Laporan ke PJ Lab                                │
│                                                         │
│  Periode: [Juli 2026 ▼]                                 │
│                                                         │
│  ┌──────────────┬──────────┬────────┬────────────────┐  │
│  │ Laboratorium │ Komputer │ Sudah  │ Status         │  │
│  │              │ Total    │ Scan   │                │  │
│  ├──────────────┼──────────┼────────┼────────────────┤  │
│  │ Lab Komp 1   │ 25       │ 25/25  │ Siap ✓         │  │
│  │ Lab Komp 2   │ 20       │ 18/20  │ Belum lengkap  │  │
│  │ Lab Jaringan │ 15       │ 15/15  │ Siap ✓         │  │
│  └──────────────┴──────────┴────────┴────────────────┘  │
│                                                         │
│  [Kirim Semua yang Siap]   atau   [Pilih Lab ▼] [Kirim] │
└─────────────────────────────────────────────────────────┘

Catatan:
- Admin bisa kirim lab yang belum lengkap (dengan peringatan)
- Admin bisa kirim ulang lab yang sudah pernah approved (buat pending baru)
- Tidak bisa kirim lab yang masih ada pending belum di-review PJ Lab
- Siapa yang mengirim tercatat di activity_log
```

### Logic Pembuatan Record Approval (di Controller, bukan di Job)

```php
// ReportSubmissionController@submit (Admin only)
public function submit(Request $request)
{
    $request->validate([
        'laboratory_id' => 'required|exists:laboratories,id',
        'period'        => 'required|date_format:Y-m',
    ]);

    $labId = $request->laboratory_id;
    $period = $request->period;

    // Cek apakah sudah ada pending yang belum di-review
    $hasPending = ReportApproval::where([
        'laboratory_id' => $labId,
        'report_type'   => 'kepatuhan',
        'period'        => $period,
        'status'        => 'pending',
    ])->exists();

    if ($hasPending) {
        return back()->with('error', 'Lab ini masih memiliki laporan pending.');
    }

    // Buat record approval baru
    ReportApproval::create([
        'laboratory_id' => $labId,
        'report_type'   => 'kepatuhan',
        'period'        => $period,
        'status'        => 'pending',
        'reviewed_by'   => User::role('kepala_lab')
                            ->where('laboratory_id', $labId)
                            ->first()?->id,
    ]);

    // Log aktivitas
    activity()
        ->causedBy(auth()->user())
        ->withProperties(['laboratory_id' => $labId, 'period' => $period])
        ->log("Mengirim laporan kepatuhan ke PJ Lab untuk periode {$period}");

    return back()->with('success', 'Laporan berhasil dikirim ke PJ Lab.');
}
```

**Aturan pengiriman:**
- Satu lab hanya boleh punya **satu record pending** per periode. Admin tidak bisa kirim ulang jika masih ada yang pending.
- Jika PJ Lab sudah approve/reject, Admin **bisa kirim ulang** — akan membuat record baru (record lama tetap ada sebagai riwayat).
- Record approval yang sudah `approved`/`rejected` bersifat **immutable** — tidak pernah diubah.

---

## FASE 7: Testing

### Test Baru yang Perlu Dibuat

```
tests/Feature/
├── LaboratoryManagementTest.php     # CRUD lab oleh admin
├── KepalaLabRoleTest.php            # RBAC: kepala_lab hanya lihat lab sendiri
├── ReportSubmissionTest.php         # Admin kirim laporan ke PJ Lab
├── ReportApprovalTest.php           # Approve/reject flow
├── PimpinanApprovedReportsTest.php  # Pimpinan hanya lihat approved
├── LabScopedDashboardTest.php       # Dashboard per role
└── AccountKepalaLabTest.php         # Manage akun kepala_lab + assign lab
```

### Skenario Test Kritis

1. **PJ Lab hanya melihat komputer di labnya sendiri** — bukan lab lain.
2. **PJ Lab tidak bisa mengubah data** — hanya review dan approve/reject.
3. **Pimpinan tidak melihat laporan pending/rejected** — hanya approved.
4. **Admin bisa mengelola semua data** — termasuk assign PJ Lab ke lab.
5. **Approve/Reject mengubah status** dan tercatat di activity log.
6. **Admin harus kirim laporan dulu** sebelum PJ Lab bisa melihat dan review.
7. **Admin tidak bisa kirim ulang** jika masih ada pending yang belum di-review PJ Lab.
8. **Admin bisa kirim ulang** setelah PJ Lab approve/reject (buat record baru).

---

## Urutan Implementasi (Roadmap)

### Sprint 1 — Fondasi (Estimasi: 2-3 hari)
- [ ] Migration: `laboratories`, tambah `laboratory_id` ke `computers` & `users`
- [ ] Migration: `report_approvals`
- [ ] Model: `Laboratory`, `ReportApproval`
- [ ] Update Model: `Computer`, `User` (tambah relasi)
- [ ] Update Seeder: tambah role `kepala_lab`, permission baru, default user, sample lab data
- [ ] Jalankan `php artisan migrate:fresh --seed` dan pastikan semua test lama masih pass

### Sprint 2 — CRUD Lab & Akun (Estimasi: 2 hari)
- [ ] `LaboratoryController` + views (index, create, edit)
- [ ] Update `AccountController` — tambah field role `kepala_lab` + pilih lab
- [ ] Update navigasi sidebar — menu berdasarkan role
- [ ] Test: `LaboratoryManagementTest`, `AccountKepalaLabTest`

### Sprint 3 — Agent Scanner + Lab (Estimasi: 1-2 hari)
- [ ] Update `AgentDownloadController` — dropdown pilih lab + config.json dengan `laboratoryId`
- [ ] Update `AgentRegisterController` — terima dan simpan `laboratory_id`
- [ ] Update script PowerShell (`scanner.ps1`) — baca dan kirim `laboratoryId`
- [ ] Test: registrasi agent dengan `laboratory_id`

### Sprint 4 — Fitur PJ Lab + Kirim Laporan (Estimasi: 3-4 hari)
- [ ] `ReportSubmissionController` + view — halaman admin kirim laporan ke PJ Lab
- [ ] `LabInventoryController` + views — inventaris lab scoped
- [ ] `ReportApprovalController` + views — review, preview PDF, approval
- [ ] Update `DashboardController` — dashboard per role
- [ ] Test: `ReportSubmissionTest`, `KepalaLabRoleTest`, `ReportApprovalTest`, `LabScopedDashboardTest`

### Sprint 5 — Integrasi Pimpinan & Polish (Estimasi: 2-3 hari)
- [ ] Update `ReportController` — filter laporan by approval status
- [ ] Update export classes — tambah metadata approval
- [ ] Update `ComplianceDataController` — scope by role
- [ ] Revisi narasi UI (Agen → Tools)
- [ ] Test: `PimpinanApprovedReportsTest`
- [ ] Full regression test: `composer test`

### Sprint 6 — Migrasi Data & Deployment (Estimasi: 1-2 hari)
- [ ] Script migrasi: mapping `location` string lama ke `laboratory_id`
- [ ] Re-deploy agent scanner ke komputer yang sudah ada (dengan config.json baru)
- [ ] Update dokumentasi (AGENTS.md, README)
- [ ] Deploy ke staging, UAT
- [ ] Deploy ke production

**Total estimasi: 12-16 hari kerja**

---

## Dampak pada Dokumen Skripsi

| Bab/Bagian | Perubahan |
|-----------|-----------|
| Diagram Konteks | Ganti 3 entitas lama → 3 entitas baru (lihat `REVISI_DIAGRAM_KONTEKS.md`) |
| DFD Level 1 | 3 proses → 4 proses, tambah D5 (lihat `REVISI_DFD_LEVEL_1.md`) |
| DFD Level 2 | Revisi proses 1.0, tambah proses 3.0, revisi proses 4.0 (lihat `REVISI_DFD_LEVEL_2.md`) |
| ERD | Tambah tabel `laboratories` dan `report_approvals` + relasi baru |
| Use Case Diagram | Tambah aktor Kepala Lab + use case: review inventaris, approve laporan |
| Sequence Diagram | Tambah sequence: approval flow |
| Pembahasan Interface | Tambah screenshot halaman PJ Lab |
| Listing Program | Tambah kode controller & model baru |
