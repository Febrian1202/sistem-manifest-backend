# Dokumen Skenario Pengujian Perangkat Lunak
**Proyek:** Sistem Informasi Manifest Lisensi Software (USN Kolaka)
**Peran:** Senior Quality Assurance (QA) Engineer

---

## Pendahuluan
Dokumen ini merinci rencana pengujian untuk memastikan sistem manifest lisensi software berfungsi sesuai spesifikasi teknis dan kebutuhan pengguna. Pengujian dibagi menjadi dua tahap utama: *Black Box Testing* untuk validasi fungsionalitas dan integrasi data, serta *User Acceptance Testing* (UAT) untuk validasi aspek manajerial.

---

## Bagian 1: Black Box Testing (Pengujian Fungsionalitas)
Fokus pengujian ini adalah pada integritas aliran data dari skrip klien (PowerShell/WMI) menuju server Laravel melalui API, serta ketepatan logika pemrosesan data di sisi server.

| ID Test | Fitur/Fungsi yang Diuji | Skenario Pengujian | Hasil yang Diharapkan | Status Validasi |
|:---:|:---|:---|:---|:---:|
| BB-01 | Ekstraksi Data WMI (Klien) | Menjalankan skrip `scanner.ps1` pada komputer klien untuk mengambil data *software* terinstal dan nomor seri OS. | Skrip berhasil mengambil daftar nama aplikasi, versi, dan vendor tanpa ada data yang terlewat atau korup. | [Lulus/Gagal] |
| BB-02 | Autentikasi API Agen | Agen mencoba melakukan registrasi ke endpoint `/api/agent/register` menggunakan alamat MAC yang unik. | Server memberikan *Bearer Token* (Sanctum) yang valid untuk komunikasi data selanjutnya. | [Lulus/Gagal] |
| BB-03 | Transmisi Payload JSON | Mengirimkan data hasil pemindaian dalam format JSON dari klien ke server melalui endpoint `/api/scan-result`. | Server menerima muatan JSON, merespons dengan status HTTP 200 (Success), dan memasukkan tugas ke dalam antrean (*queue*). | [Lulus/Gagal] |
| BB-04 | Penanganan Masalah Jaringan | Memutus koneksi internet pada komputer klien saat skrip pengiriman data sedang berjalan. | Skrip klien memberikan pesan galat yang informatif dan melakukan mekanisme penyimpanan log lokal atau *retry* saat koneksi kembali tersedia. | [Lulus/Gagal] |
| BB-05 | Akurasi Logika Kepatuhan | Server mencocokkan software yang ditemukan dengan katalog lisensi yang dimiliki universitas. | Sistem secara otomatis mengubah status menjadi "Terlisensi" jika kuota tersedia, dan "Tidak Berlisensi" jika software tidak terdaftar atau kuota habis. | [Lulus/Gagal] |
| BB-06 | Integritas Antrean (Queue) | Mengirimkan data scan dari 5 komputer klien secara bersamaan ke server. | Laravel Horizon/Worker berhasil memproses semua data secara asinkron tanpa kehilangan informasi atau *race condition* pada database. | [Lulus/Gagal] |

---

## Bagian 2: User Acceptance Testing (UAT)
Pengujian ini bertujuan untuk mengukur sejauh mana sistem memenuhi kebutuhan operasional Admin IT dan kebutuhan strategis Pimpinan.

| ID UAT | Skenario Kebutuhan Pengguna | Langkah Pengujian | Kriteria Diterima (Acceptance Criteria) |
|:---:|:---|:---|:---|
| UAT-01 | Monitoring Aset secara Real-time (Admin) | Login sebagai Admin dan membuka dasbor untuk melihat jumlah komputer yang aktif melakukan scan. | Dasbor menampilkan statistik jumlah komputer, software terdeteksi, dan status kepatuhan secara akurat dan mutakhir. |
| UAT-02 | Manajemen Katalog Software (Admin) | Menambahkan entri software baru ke dalam `SoftwareCatalog` dan mengatur kategori lisensinya. | Data software tersimpan dengan benar dan menjadi acuan validasi pada proses pemindaian berikutnya. |
| UAT-03 | Visualisasi Kepatuhan Lisensi (Pimpinan) | Login sebagai Pimpinan dan mengakses menu "Laporan Kepatuhan" untuk melihat ringkasan legalitas. | Sistem menyajikan grafik atau tabel yang jelas membedakan antara software legal dan bajakan per unit/laboratorium. |
| UAT-04 | Laporan Pendukung Keputusan (Pimpinan) | Mengekspor laporan rekapitulasi ke format PDF/Excel untuk keperluan rapat anggaran. | Laporan yang dihasilkan memuat informasi detail mengenai kekurangan lisensi yang harus dibeli, memudahkan penentuan skala prioritas pengadaan. |
| UAT-05 | Keamanan Hak Akses (RBAC) | Mencoba mengakses menu manajemen lisensi menggunakan akun dengan role "Pimpinan". | Sistem menolak akses dan memberikan pesan peringatan, memastikan hanya Admin yang dapat mengubah konfigurasi lisensi. |

---

## Penutup
Hasil dari skenario pengujian di atas akan menjadi bukti empiris bahwa "Sistem Informasi Manifest Lisensi Software" layak untuk diimplementasikan di lingkungan universitas dan mampu memberikan kontribusi nyata dalam pengelolaan aset digital secara legal dan terorganisir.
