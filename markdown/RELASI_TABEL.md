### 4.1.2 Relasi Tabel

*(Tempatkan gambar relasi tabel/ERD Anda di sini)*

**Gambar 4.2 Daftar Relasi**

Gambar 4.2 merupakan relasi antar tabel yang digunakan untuk menyimpan dan mengelola informasi dalam sistem informasi manifest lisensi *software* secara terstruktur. Setiap tabel memiliki keterhubungan melalui *foreign key* untuk mendukung integrasi data dan mempermudah proses pengambilan informasi pada sistem. Relasi tabel tersebut terdiri dari tabel `users`, tabel `computers`, tabel `software_catalogs`, tabel `software_discoveries`, tabel `license_inventories`, tabel `compliance_reports`, dan tabel `activity_log` yang berfungsi sebagai tempat penyimpanan data seperti identitas pengguna, data spesifikasi komputer klien, daftar master perangkat lunak, riwayat temuan instalasi aplikasi, inventaris lisensi komersial, hasil laporan kepatuhan, serta riwayat log aktivitas. Dengan adanya relasi antar tabel tersebut, sistem dapat mengelola proses pengawasan lisensi secara terintegrasi mulai dari pengelolaan data komputer target, pemindaian instalasi perangkat lunak, validasi status perangkat lunak berdasarkan katalog, hingga pembuatan laporan kepatuhan hak cipta secara otomatis.
