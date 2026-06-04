# Sequence Diagram MVC - Sistem Manifest

## Visualisasi Mermaid

```mermaid
sequenceDiagram
    autonumber
    actor Admin
    participant View as View<br/>(Blade UI)
    participant Ctrl as Controller<br/>(Web & API Controller)
    participant Model as Model<br/>(ScanRequest, ComplianceReport)
    participant DB as Database
    participant Agent as Client Agent

    %% Tahap 1: Inisiasi Scan oleh Admin
    rect rgb(240, 248, 255)
        note right of Admin: Tahap 1: Inisiasi Permintaan Scan (Sinkron)
        Admin->>View: Klik tombol "Inisiasi Scan"
        View->>Ctrl: POST /scan/initiate
        Ctrl->>Model: create(status = 'requested')
        Model->>DB: INSERT INTO scan_requests
        DB-->>Model: Return OK
        Model-->>Ctrl: Object ScanRequest
        Ctrl-->>View: Redirect with Success Message
        View-->>Admin: Tampilkan Notifikasi Berhasil
    end

    %% Tahap 2: Polling & Eksekusi Scan oleh Agent
    rect rgb(255, 250, 240)
        note right of Admin: Tahap 2: Polling & Eksekusi (Asinkron)
        loop Periodik (misal: 5 Menit)
            Agent->>Ctrl: GET /api/agent/check
            Ctrl->>Model: checkPendingScan()
            Model->>DB: SELECT * FROM scan_requests WHERE status='requested'
            DB-->>Model: Return Data
            Model-->>Ctrl: Status Permintaan
            
            alt Ada permintaan scan
                Ctrl-->>Agent: Response JSON {scan_required: true}
                Agent->>Agent: Eksekusi Scan (WMI & Registry)
            else Tidak ada permintaan
                Ctrl-->>Agent: Response JSON {scan_required: false}
            end
        end
    end

    %% Tahap 3: Pengiriman Hasil Scan & Klasifikasi
    rect rgb(240, 255, 240)
        note right of Admin: Tahap 3: Penerimaan & Pemrosesan Data
        Agent->>Ctrl: POST /api/agent/submit (Payload JSON)
        Ctrl->>Ctrl: Validasi Request & Payload JSON
        Ctrl->>Model: processAndClassify(payload)
        
        note over Model,DB: Logic klasifikasi lisensi
        Model->>DB: INSERT/UPDATE compliance_reports
        DB-->>Model: Return OK
        Model->>DB: UPDATE scan_requests (status='completed')
        DB-->>Model: Return OK
        
        Model-->>Ctrl: Pemrosesan Berhasil (Boolean)
        Ctrl-->>Agent: Response HTTP 200 OK
    end
```

## Penjelasan Akademik (Format Proposal/Skripsi)

**Penjelasan Sequence Diagram MVC - Proses Pemindaian (Scanning) Sistem Manifest**

*Sequence diagram* di atas memodelkan alur interaksi antar objek dalam proses pemindaian (*scanning*) lisensi perangkat lunak menggunakan arsitektur perangkat lunak *Model-View-Controller* (MVC). Sistem beroperasi dalam arsitektur *client-server* di mana proses utamanya dipecah menjadi tiga tahapan asinkron:

1. **Tahap Inisiasi Permintaan Scan**: 
   Aktor (Admin) berinteraksi dengan antarmuka sistem (**View**) untuk memulai proses pemindaian. Permintaan tersebut diteruskan ke **Controller** yang bertugas menangani logika HTTP *request*. Controller kemudian menginstruksikan **Model** (representasi *business logic* aplikasi) untuk merekam status permintaan baru ke dalam **Database**. Setelah data persisten tersimpan, Controller mengembalikan *response* ke View untuk memberikan umpan balik (notifikasi) visual kepada Admin.

2. **Tahap Polling dan Eksekusi Scan**:
   Mengingat sifat pemindaian yang berjalan di latar belakang klien, **Client Agent** didesain untuk secara periodik melakukan *polling* (pemeriksaan rutin) ke *endpoint* API **Controller**. Controller akan memanggil *method* pada **Model** untuk memverifikasi ketersediaan tugas pemindaian di **Database**. Jika terdeteksi adanya *flag* permintaan, Controller memberikan sinyal eksekusi kepada Agent. Agent selanjutnya akan memproses instruksi tersebut secara lokal pada mesin klien memanfaatkan *Windows Management Instrumentation* (WMI) dan *Registry*.

3. **Tahap Pengiriman dan Klasifikasi Hasil**:
   Setelah akuisisi data selesai, **Client Agent** mentransmisikan hasil pemindaian (*raw data*) dalam format JSON kembali ke **Controller**. Controller bertanggung jawab memvalidasi *payload* sebelum mendelegasikan pemrosesan inti ke **Model**. Di dalam **Model**, algoritma klasifikasi dieksekusi untuk memetakan perangkat lunak ke dalam status kepatuhan (*compliant/non-compliant*). Model kemudian menyimpan hasil akhirnya ke entitas laporan di **Database** (tabel `compliance_reports`) dan memperbarui status pemindaian menjadi selesai (*completed*).

**Kesesuaian dengan Pemisahan Tanggung Jawab (Separation of Concerns):**
Diagram ini secara ketat mematuhi prinsip desain *framework* Laravel:
* **View (Blade UI)** murni berfungsi sebagai lapisan presentasi interaktif bagi pengguna.
* **Controller (Web & API)** bertindak sebagai fasilitator komunikasi yang mengatur *routing*, *middleware*, dan validasi struktur data dari/ke *Client Agent* maupun antarmuka Admin.
* **Model (Eloquent ORM)** menangkap seluruh kompleksitas *business logic* (algoritma pencocokan lisensi) dan secara eksklusif memanipulasi entitas di dalam **Database**, sehingga memisahkan logika pengolahan dari logika transportasi data.
