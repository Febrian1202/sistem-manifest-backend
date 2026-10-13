# Pengujian Pengguna (User Testing)

Dalam pelaksanaan pengujian pengguna (_user testing_) terstandarisasi, rangkaian instrumen pengujian digunakan secara berkesinambungan:

1. **Penyusunan Skenario Tugas (_Task Scenarios_)**: Dibuat secara realistis sebagai stimulus aktivitas pengguna.
2. **Lembar Observasi Antarmuka**: Digunakan oleh evaluator untuk merekam metrik performa serta perilaku riil di lapangan secara langsung.
3. **Kuesioner Pasca-Pengujian (_Post-Test Questionnaire_)**: Menggunakan standar **System Usability Scale (SUS)** untuk menangkap data persepsi serta tingkat kepuasan subjektif responden.

Integrasi ketiga instrumen praktis ini memberikan gambaran utuh mengenai kekuatan dan kelemahan interaksi produk digital, sehingga rekomendasi perbaikan antarmuka benar-benar didasarkan pada fakta kebutuhan pengguna nyata.

---

## 1. Pembuatan Skenario Tugas (Task Scenarios)

Menurut **Rubin & Chisnell**, skenario harus:

- Realistis
- Memiliki konteks/cerita
- Memberikan tujuan yang jelas
- **Menghindari instruksi mengarahkan (_avoid leading instructions_)**: Tidak boleh mendikte tombol atau langkah spesifik yang harus diklik.

### Contoh Skenario Tugas

- **Skenario Tugas 1 (Eksplorasi & Pencarian)**:

> _"Anda ingin membeli hadiah ulang tahun berupa 'Sepatu Lari Merk X' ukuran 42 dengan rentang harga Rp500.000 - Rp800.000. Silakan temukan produk tersebut di aplikasi ini, lalu tambahkan ke keranjang belanja Anda."_

- **Skenario Tugas 2 (Transisi Alur Kerja / Checkout)**:

> _"Sekarang produk sudah ada di keranjang. Anda memutuskan untuk langsung membelinya menggunakan metode pembayaran transfer bank dan memilih opsi pengiriman reguler. Selesaikan proses ini hingga Anda mencapai halaman ringkasan pembayaran."_

- **Skenario Tugas 3 (Penanganan Eror / Error Handling)**:

> _"Anda menyadari salah memasukkan alamat pengiriman utama. Coba temukan cara pada antarmuka untuk mengubah alamat pengiriman Anda ke alamat yang baru tanpa membatalkan pesanan yang sedang berjalan."_

Skenario tugas ini diberikan kepada pengguna (partisipan) sebagai misi. Selama pengguna mencoba menyelesaikannya, evaluator bertindak sebagai pengamat yang mengawasi gerak-gerik, klik, waktu, dan ucapan pengguna (_think-aloud_), kemudian mencatat seluruh temuan pada **Lembar Observasi**.

---

## 2. Lembar Observasi Antarmuka

Lembar Observasi berfungsi sebagai lembar kerja utama bagi evaluator untuk merekam bukti empiris selama pengujian. Metrik yang dicatat meliputi:

- **Status Keberhasilan (_Task Success_)**: Apakah pengguna berhasil menyelesaikan tugas secara mandiri, dengan bantuan, atau gagal/menyerah.
- **Waktu Pengerjaan (_Time on Task_)**: Durasi (menit/detik) yang dihabiskan untuk menuntaskan skenario.
- **Jumlah Kesalahan (_Error Count_)**: Frekuensi salah klik menu atau kebingungan akibat elemen antarmuka.
- **Catatan Perilaku & Keluhan (_Think-Aloud Notes_)**: Catatan kualitatif mengenai ucapan, keluhan verbal, atau kendala pengguna.

---

### Format Tabel Lembar Observasi Evaluator

| No. Tugas   | Deskripsi Tugas                                  | Status Keberhasilan (_Task Success_) | Waktu Pengerjaan (_Time on Task_) | Jumlah Kesalahan (_Error Count_) | Catatan Perilaku & Keluhan (_Think-Aloud Notes_) |
| ----------- | ------------------------------------------------ | ------------------------------------ | --------------------------------- | -------------------------------- | ------------------------------------------------ |
| **Tugas 1** | Mencari sepatu lari dan memasukkan ke keranjang. | - [ ] Berhasil mandiri<br>           |

<br>- [ ] Berhasil dengan bantuan<br>

<br>- [ ] Gagal/Menyerah | _____ Menit<br>

<br>_____ Detik | _____ kali | _Contoh: Partisipan sempat bingung mencari filter ukuran karena ikon filter terlalu kecil dan tersembunyi._ |
| **Tugas 2** | Melakukan proses _checkout_ sampai halaman bank. | - [ ] Berhasil mandiri<br>

<br>- [ ] Berhasil dengan bantuan<br>

<br>- [ ] Gagal/Menyerah | _____ Menit<br>

<br>_____ Detik | _____ kali | _Contoh: Ragu-ragu saat memilih kurir karena teks opsi pengiriman tumpang tindih pada layar._ |
| **Tugas 3** | Mengubah alamat pengiriman pada antarmuka. | - [ ] Berhasil mandiri<br>

<br>- [ ] Berhasil dengan bantuan<br>

<br>- [ ] Gagal/Menyerah | _____ Menit<br>

<br>_____ Detik | _____ kali | _Contoh: Muncul pesan eror saat simpan alamat, namun teks eror berupa kode sistem, membuat user frustrasi._ |
