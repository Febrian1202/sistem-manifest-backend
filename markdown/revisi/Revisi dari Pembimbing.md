Berikut adalah ringkasan poin-poin penting dari sesi diskusi/bimbingan perancangan diagram alir data (DFD) tersebut:

---

### **Ringkasan Umum**

Percakapan membahas perbaikan dan penyelarasan diagram perancangan sistem (_Data Flow Diagram_ / DFD, baik tingkat konteks maupun level 1/diagram 0) untuk sistem pemindaian manifest lisensi perangkat lunak. Fokus utama meliputi konsistensi penamaan arus data, pemisahan fungsi proses dan entitas, serta kejelasan notasi basis data/tabel.

---

### **Poin-Poin Utama Pembahasan & Revisi**

#### 1. **Entitas Eksternal (_External Entity_) & Struktur Pelaporan**

- **Dekan vs. Kaprodi:** Terdapat diskusi mengenai ke mana laporan sistem dialirkan. Disarankan untuk menggunakan entitas yang jelas dan konsisten, seperti **Pimpinan** atau **Dekan**, sesuai struktur organisasi kampus, serta menyiapkan alasan yang kuat jika diuji.
- **Admin / Staf Lab:** Dikonfirmasi sebagai salah satu entitas yang berinteraksi langsung dengan sistem (misal: menerima/mengirimkan data pemindaian).

#### 2. **Kaidah Penamaan Arus Data (_Data Flow_)**

- **Gunakan Kata Benda:** Arus data wajib dinamai dengan kata benda (misal: _Data Token_, _Data Scan Komputer_), bukan kata kerja. Kata kerja (seperti _simpan_, _deploy_, _inisiasi_, _berikan token_) merupakan penamaan untuk **proses**.
- **Konsistensi Antar-Level:** Penamaan arus data pada diagram konteks harus sama persis dengan yang ada di diagram level berikutnya (level 1 / diagram 0).
- **Spesifikasi Data Scan:** Karena data hasil pemindaian komputer mencakup spesifikasi OS, _hardware_, dan aplikasi terpasang, penamaannya disarankan tetap konsisten dengan induknya, misalnya:
- `Data Scan Komputer (Data Komputer/OS)`
- `Data Scan Komputer (Data Software Terdeteksi)`
- `Data Scan Komputer (Data Lisensi)`

#### 3. **Perancangan Proses (_Process_)**

- **Struktur Proses:** Proses harus memakai kata kerja aktif.
- **Jumlah Proses:** Disarankan agar proses utama di level 1 dibuat ringkas dan terfokus (cukup sekitar 3 proses utama, tidak perlu terlalu banyak/rumit).
- **Rujukan Teori:** Diingatkan terkait perbedaan istilah di literatur (misal: referensi Roger S. Pressman vs rujukan lain terkait penyebutan Diagram Konteks, Diagram 0, dan Level 1).

#### 4. **Penyusunan Notasi Data Store (_Database_ vs. Tabel)**

- **Simbol D1, D2, dst.:** Perlu diperjelas apakah simbol data store merujuk pada basis data utuh atau tabel-tabel tersendiri.
- **Penyelarasan:** Disarankan menggunakan penamaan yang terpadu, misalnya: `Database Manifest (Tabel Komputer)`, `Database Manifest (Tabel Software)`, dan seterusnya, untuk membedakan struktur penyimpanan data secara teratur (kurang lebih 4–5 tabel).

#### 5. **Koreksi Teknis Alur & Notasi Diagram**

- **Arah Panah:** Periksa kelengkapan tanda panah arus data; pastikan tidak ada garis yang tertinggal atau panah bolak-balik yang membingungkan.
- **Siklus Data Masuk & Keluar:** Untuk proses seperti pembersihan data (_clean/normalize data software_), gambarkan alur yang jelas: data masuk ke proses $\rightarrow$ diproses/dibersihkan $\rightarrow$ disimpan kembali ke data store atau dikeluarkan.

---

### **Daftar Tindak Lanjut (_Action Items_)**

1. Ganti semua nama arus data yang masih menggunakan kata kerja (_deploy_, _simpan_, _inisiasi_, dll.) menjadi kata benda.
2. Selaraskan nama input/output antara diagram konteks dan level 1 agar sepenuhnya identik.
3. Tetapkan entitas pelaporan (Pimpinan/Dekan) secara konsisten.
4. Perbaiki simbol data store (D1, D2, dst.) dengan format penamaan basis data dan tabel yang spesifik.
5. Cek ulang seluruh sambungan garis dan mata panah untuk menghindari kesalahan notasi DFD.
