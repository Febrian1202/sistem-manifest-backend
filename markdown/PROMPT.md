# Prompt: Perbaikan Form Input Lisensi Baru

# Sistem Manifest Backend (Laravel 12 + Blade + Tailwind CSS)

## Context

Proyek Laravel 12 (PHP 8.2+) untuk IT Asset Management. Form yang diperbaiki adalah modal "Input Lisensi Baru" untuk entitas license_inventories. Enkripsi license_key sudah dihandle otomatis di Model — jangan ubah.

## Tasks

### T1 — Tambah field license_key ke form

Tambahkan input untuk license key di antara field "Pilih Software" dan "Nomor PO".

- Gunakan input[type=password] dengan tombol toggle show/hide
- Label: "License Key / Product Key"
- Hint: "Opsional — akan dienkripsi sebelum disimpan"
- Atribut: name="license_key", nullable

### T2 — Perbaiki format tanggal ke lokal Indonesia

Field "Tanggal Beli" dan "Kedaluwarsa" menampilkan format mm/dd/yyyy.

- Ubah display/placeholder ke format dd/mm/yyyy
- Nilai dikirim ke backend tetap format ISO (YYYY-MM-DD)

### T3 — Ubah default "Harga Per Unit" dari 0 menjadi kosong

- Hapus value="0", ganti dengan placeholder="Opsional"
- Nilai kosong dikirim sebagai null ke backend, bukan 0

### T4 — Tambah validasi backend untuk license_key

Di controller atau FormRequest untuk LicenseInventory:

- Tambahkan rule: 'license_key' => 'nullable|string'
- Jangan tambahkan max length yang ketat

## Constraints

- Jangan ubah logika enkripsi di Model
- Jangan ubah struktur modal secara keseluruhan
- Pertahankan class Tailwind yang sudah ada

## Files to Modify

- F1: Blade view form — cari file yang berisi string "Nomor Purchase Order" atau name="purchase_order_number"
- F2: StoreLicenseInventoryRequest atau LicenseInventoryController@store

## Definition of Done

- [ ] Field license_key tampil di form dengan toggle show/hide
- [ ] Data license_key tersimpan terenkripsi saat form disubmit
- [ ] Field harga tampil kosong (bukan 0) saat form dibuka
- [ ] Tidak ada breaking change pada field yang sudah ada
