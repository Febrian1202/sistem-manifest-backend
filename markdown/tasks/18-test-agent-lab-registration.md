# Task 18 — Test: Registrasi Agent dengan `laboratory_id`

## Sprint: 3 (Agent Scanner + Lab)
## Prioritas: Tinggi
## Dependensi: Task 16
## Estimasi: 30-45 menit

---

## Deskripsi

Buat/update test Pest untuk memverifikasi bahwa endpoint registrasi agent menerima dan menyimpan `laboratory_id` dengan benar.

## Langkah-langkah

### 1. Cari Test Registrasi yang Sudah Ada

Cari file test yang sudah mentest registrasi agent. Biasanya di `tests/Feature/` dengan nama yang mengandung "Agent", "Register", atau "Api".

### 2. Tambah/Update Skenario Test

```php
// Test registrasi dengan laboratory_id
it('registers a computer with laboratory_id', function () {
    $lab = Laboratory::factory()->create();

    $response = $this->postJson('/api/agent/register', [
        'hostname' => 'PC-LAB1-01',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'laboratory_id' => $lab->id,
    ], [
        'X-Agent-Key' => config atau env value,
    ]);

    $response->assertStatus(200); // atau 201
    $this->assertDatabaseHas('computers', [
        'hostname' => 'PC-LAB1-01',
        'laboratory_id' => $lab->id,
    ]);
});

// Test registrasi tanpa laboratory_id (harus gagal atau nullable)
it('rejects registration without laboratory_id', function () {
    $response = $this->postJson('/api/agent/register', [
        'hostname' => 'PC-LAB1-01',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        // tanpa laboratory_id
    ], [
        'X-Agent-Key' => config atau env value,
    ]);

    $response->assertStatus(422); // validation error
});

// Test re-registrasi mengupdate laboratory_id
it('updates laboratory_id on re-registration', function () {
    $lab1 = Laboratory::factory()->create();
    $lab2 = Laboratory::factory()->create();

    // Register pertama ke lab1
    $this->postJson('/api/agent/register', [
        'hostname' => 'PC-01',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'laboratory_id' => $lab1->id,
    ], ['X-Agent-Key' => ...]);

    // Re-register ke lab2
    $this->postJson('/api/agent/register', [
        'hostname' => 'PC-01',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'laboratory_id' => $lab2->id,
    ], ['X-Agent-Key' => ...]);

    $this->assertDatabaseHas('computers', [
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'laboratory_id' => $lab2->id,
    ]);
});

// Test laboratory_id invalid
it('rejects registration with invalid laboratory_id', function () {
    $response = $this->postJson('/api/agent/register', [
        'hostname' => 'PC-01',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'laboratory_id' => 9999,
    ], ['X-Agent-Key' => ...]);

    $response->assertStatus(422);
});
```

## Referensi
- Lihat test registrasi agent yang sudah ada untuk mengetahui cara setup header `X-Agent-Key`
- Sesuaikan assertion status code dengan behavior controller yang sebenarnya

## File yang Dimodifikasi/Dibuat
- Test file yang sudah mentest registrasi agent (tambah skenario baru)
- Atau buat file baru `tests/Feature/AgentLabRegistrationTest.php`

## Verifikasi
- [x] `php artisan test --filter=AgentLabRegistration` (atau nama filter yang sesuai) — semua pass
- [x] `composer test` — semua test (lama + baru) pass
