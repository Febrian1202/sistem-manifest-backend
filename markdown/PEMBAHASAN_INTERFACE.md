### 4.1.4 Pembahasan Interface/Antarmuka Program

**a. Tampilan Halaman Login**
Halaman login merupakan halaman utama yang pertama kali ditampilkan dan digunakan pengguna untuk masuk ke dalam sistem web administrasi. Halaman ini mensyaratkan pengguna untuk memasukkan kredensial berupa email dan _password_ yang telah terdaftar. Halaman ini membatasi akses masuk ke dalam dasbor hanya untuk pengguna yang memiliki hak akses sah, yaitu dengan _role_ Admin atau Pimpinan.

_(Tempatkan gambar Anda di sini)_
**Gambar 4.3 Halaman Login**

**b. Tampilan Halaman Dashboard Utama**
Halaman dashboard utama merupakan halaman ringkasan yang ditampilkan setelah pengguna berhasil login ke dalam sistem. Pada halaman ini, pengguna (baik Admin maupun Pimpinan) dapat melihat visualisasi data statistik, seperti total komputer terdaftar, grafik tingkat kepatuhan lisensi (status _Safe_, _Warning_, dan _Critical_), serta metrik jumlah perangkat lunak ilegal yang terdeteksi di lingkungan USN Kolaka.

_(Tempatkan gambar Anda di sini)_
**Gambar 4.4 Halaman Dashboard Utama**

**c. Halaman Data Komputer (Agen)**
Halaman data komputer digunakan untuk menampilkan seluruh daftar perangkat klien yang telah diinstal _script_ agen pemindai. Pada halaman ini, pengguna dapat melihat status keaktifan komputer (_last seen_), _hostname_, sistem operasi, IP _Address_, dan MAC _Address_ dari tiap perangkat keras yang terhubung ke jaringan instansi.
biarkan dulu seperti ini, kalau ada revisi oleh pembimbing atau penguji saya baru nanti diubah
_(Tempatkan gambar Anda di sini)_
**Gambar 4.5 Halaman Data Komputer**

**d. Halaman Detail Komputer & Hasil Scan**
Halaman detail komputer merupakan kelanjutan dari halaman data komputer. Pada halaman ini, pengguna dapat melihat rincian spesifikasi perangkat keras (seperti RAM, Prosesor, dan penyimpanan) sekaligus menampilkan daftar mentah seluruh instalasi perangkat lunak (_Software Discoveries_) yang berhasil dipindai oleh agen pada perangkat tersebut beserta versi dan _vendor_-nya.

_(Tempatkan gambar Anda di sini)_
**Gambar 4.6 Halaman Detail Komputer & Hasil Scan**

**e. Halaman Katalog Software**
Halaman katalog software digunakan khusus oleh Admin untuk mengelola basis data rujukan (_Master Data_) perangkat lunak. Pada halaman ini, Admin dapat melihat daftar _software_ yang telah dinormalisasi, mengklasifikasikan kategori aplikasi (seperti _Freeware_ atau _Commercial_), serta menentukan status legalitasnya (_Whitelist_ atau _Blacklist_) yang menjadi acuan aturan deteksi pembajakan.

_(Tempatkan gambar Anda di sini)_
**Gambar 4.7 Halaman Katalog Software**

**f. Halaman Inventaris Lisensi**
Halaman inventaris lisensi digunakan Admin untuk mendokumentasikan aset lisensi komersial yang dimiliki oleh USN Kolaka. Pada halaman ini, admin dapat menambah, mengubah, atau menghapus data lisensi, termasuk mengatur batas kuota pengguna (_quota limit_), memasukkan tanggal kadaluarsa, nomor tagihan, serta menyimpan _license key_ secara rahasia dan aman.

_(Tempatkan gambar Anda di sini)_
**Gambar 4.8 Halaman Inventaris Lisensi**

**g. Halaman Laporan Kepatuhan (Compliance Report)**
Halaman laporan kepatuhan merupakan pusat informasi hasil evaluasi silang sistem. Halaman ini digunakan untuk melihat status pelanggaran lisensi dari setiap komputer. Pengguna (terutama Pimpinan) dapat mengevaluasi perangkat mana saja yang berstatus _Critical_ (terindikasi menggunakan perangkat lunak bajakan) atau melampaui batas kuota lisensi instansi, beserta detail keterangannya.

_(Tempatkan gambar Anda di sini)_
**Gambar 4.9 Halaman Laporan Kepatuhan**

**h. Halaman Manajemen Pengguna**
Halaman manajemen pengguna berfungsi untuk mengelola akun yang memiliki hak akses ke dalam sistem _backend_. Pada halaman ini, Admin dapat menambah akun baru, memperbarui kata sandi, serta menetapkan peran (_role_) apakah pengguna tersebut bertindak sebagai Administrator penuh atau hanya sebagai Pimpinan (dengan hak akses _read-only_).

_(Tempatkan gambar Anda di sini)_
**Gambar 4.10 Halaman Manajemen Pengguna**

**i. Halaman Log Aktivitas (Audit Trail)**
Halaman log aktivitas digunakan untuk melacak jejak rekam (_history_) penggunaan sistem. Pada halaman ini, sistem akan menampilkan data rekam jejak terkait siapa pengguna yang melakukan perubahan data, kapan waktu terjadinya, dan detail perubahan atribut (seperti penambahan lisensi atau perubahan status _blacklist_ pada katalog), sehingga keamanan dan transparansi sistem tetap terjaga.

_(Tempatkan gambar Anda di sini)_
**Gambar 4.11 Halaman Log Aktivitas**
