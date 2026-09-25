# 📋 LEMBAR KERJA & CHECKLIST UAT (USER ACCEPTANCE TESTING)
## Modul: Pettycash Pusat (`1671` &rarr; `1671r`) — Modul Pettycast

| Dokumen Kontrol | Keterangan |
| :--- | :--- |
| **Nomor Dokumen** | `UAT-EVEREST-PETTYCAST-1671` |
| **Modul Aplikasi** | `pettycast` ([coTransaksiUi.php](file:///z:/everest_9sep/application/modules/pettycast/config/coTransaksiUi.php) & [coTransaksiCore.php](file:///z:/everest_9sep/application/modules/pettycast/config/coTransaksiCore.php)) |
| **Jenis Transaksi** | `1671` (Pengajuan Pettycash Kantor Pusat &rarr; Target: `1671r`) |
| **Alur Terhubung** | `1672` (Otorisasi Claim), `1671re` (Edit), `1671rrj` (Reject) |
| **Role Penguji (Kasir)**| Kasir Kantor Pusat (`c_holding`, `place = center`) |
| **Tanggal Pengujian** | ____________________ |
| **Nama Penguji (Kasir)**| ____________________ |
| **Nama Otorisator** | ____________________ |

---

### Petunjuk Pengisian Status
- Beri tanda centang `[x]` pada kolom **Status** jika langkah berhasil diuji.
- Tuliskan hasil pada kolom **Hasil Aktual / Keterangan** (misal: *Nomor nota terbit*, *Pesan error muncul sesuai harapan*, dll.).
- Nilai kesimpulan pengujian: **PASS**, **FAIL**, atau **BLOCKED**.

---

### FASE 1: Prasyarat & Lingkungan UAT (Pre-Conditions)

| No | Komponen yang Diperiksa | Kriteria Keberhasilan (Expected Result) | Status | Hasil Aktual / Catatan |
| :-: | :--- | :--- | :-: | :--- |
| 1.1 | **Hak Akses Pengguna** | Login menggunakan akun dengan grup wewenang `c_holding` di Cabang Pusat (`place = center` / Head Office). Menu pettycash pusat dapat diakses tanpa hambatan hak akses. | [ ] PASS<br>[ ] FAIL | |
| 1.2 | **Ketersediaan Saldo Kas Kecil** | Akun Kas Kecil Pusat pada [MdlPettycashAccount](file:///z:/everest_9sep/application/modules/pettycast/config/coTransaksiUi.php#L1775) memiliki saldo aktif yang cukup di tabel `locker_value` (`state = 'active'`). | [ ] PASS<br>[ ] FAIL | Saldo awal: Rp ____________ |
| 1.3 | **Master Data Kategori Biaya** | Dropdown [MdlPettycashStatic](file:///z:/everest_9sep/application/modules/pettycast/config/coTransaksiUi.php#L1487) menampilkan kategori: Biaya Usaha, Biaya Umum, dan Biaya Produksi. | [ ] PASS<br>[ ] FAIL | |
| 1.4 | **Master Data Item Biaya** | Pencarian item pada [MdlExpense](file:///z:/everest_9sep/application/modules/pettycast/config/coTransaksiUi.php#L1429) berfungsi normal dan memunculkan daftar akun biaya operasional. | [ ] PASS<br>[ ] FAIL | |
| 1.5 | **Master Cabang Pembebanan** | Pilihan `MdlCabang` menampilkan daftar cabang pembebanan yang sah. | [ ] PASS<br>[ ] FAIL | |

---

### FASE 2: Skenario Operasional Kasir (Happy Path Testing)

| No | Langkah Pengujian | Tindakan & Input Data | Kriteria Keberhasilan (Expected Result) | Status | Hasil Aktual / Catatan |
| :-: | :--- | :--- | :--- | :-: | :--- |
| 2.1 | **Akses Form 1671** | Buka URL `/pettycast/Create/index/1671/1`. | Formulir [transaksi_pettycash_dc.html](file:///z:/everest_9sep/application/modules/pettycast/template/transaksi_pettycash_dc.html) terbuka sempurna. Header keranjang belanja menampilkan info **Plafon**, **Terpakai**, dan **Saldo**. | [x] PASS<br>[ ] FAIL | |
| 2.2 | **Pilih Cabang Pembebanan** | Klik selector cabang pada panel kiri. Pilih cabang yang menanggung biaya. | Nilai cabang terpilih terkunci pada input `pihakID` / `data_cabang`. | [x] PASS<br>[ ] FAIL | Cabang: ____________ |
| 2.3 | **Pilih Kategori Biaya** | Klik input `pihakMainName`, pilih salah satu kategori (misal: *Biaya Umum*). | Kategori berhasil dipilih dan kolom pencarian item biaya di bawahnya aktif. | [x] PASS<br>[ ] FAIL | Kategori: ____________ |
| 2.4 | **Pilih Item Biaya** | Ketik nama biaya di `itemKeyword`, pilih item dari daftar hasil pencarian. | Baris item biaya masuk ke tabel Shopping Cart di panel kanan. | [x] PASS<br>[ ] FAIL | Item: ____________ |
| 2.5 | **Input Pengeluaran Non-PPN** | - Isi nominal belanja (misal: Rp 100.000).<br>- Isi keterangan/referensi bon.<br>- Pilih radio button **Non PPN**. | Kolom harga terisi, total shopping cart terhitung Rp 100.000 tanpa DPP/PPN tambahan. | [x] PASS<br>[ ] FAIL | Nominal: Rp ____________ |
| 2.6 | **Input Pengeluaran PPN** | - Tambah item kedua (misal: Rp 222.000).<br>- Pilih opsi **PPN**.<br>- Input No e-Faktur, NPWP, dan Tgl Faktur. | Modal faktur tersimpan. Sistem menghitung otomatis nilai DPP dan PPN sesuai `ppnFactor`. Subtotal keranjang terakumulasi dengan benar. | [x] PASS<br>[ ] FAIL | DPP: Rp ____________<br>PPN: Rp ____________ |
| 2.7 | **Pengisian Catatan Transaksi** | Ketik deskripsi ringkas pada kotak *Description Note*. | Catatan tersimpan ke sesi tanpa menghilangkan data keranjang belanja. | [x] PASS<br>[ ] FAIL | |
| 2.8 | **Validasi & Lanjut ke Preview** | Klik tombol hijau **Process / Lanjut** (`btnProcess`). | Tidak ada pesan validasi gagal. Halaman berpindah ke halaman **Preview** ([Create.php](file:///z:/everest_9sep/application/modules/pettycast/controllers/Create.php#L754)). | [x] PASS<br>[ ] FAIL | |
| 2.9 | **Pemilihan Akun Pettycash** | Pada bagian *Receipt Elements* di Preview, pilih radio button akun kas kecil pusat. | Akun kas kecil terpilih dan saldo aktif akun tersebut tampil jelas. | [x] PASS<br>[ ] FAIL | Akun Kas: ____________ |
| 2.10 | **Penyimpanan Transaksi (Save)** | Klik tombol **Save / Simpan**. | Muncul notifikasi sukses (*sweetalert*). Sistem menerbitkan nomor nota resmi dengan awalan kode **`1671r/`** (status: *Pending Approval*). | [x] PASS<br>[ ] FAIL | No. Nota: `1671r/_____` |

---

### FASE 3: Verifikasi Backend, Database & Akuntansi

| No | Objek Pemeriksaan | Sumber Verifikasi / Query | Kriteria Keberhasilan (Expected Result) | Status | Hasil Aktual / Catatan |
| :-: | :--- | :--- | :--- | :-: | :--- |
| 3.1 | **Header Transaksi (MySQL)** | Tabel `transaksi`<br>`WHERE nomer = '1671r/...'` | Record ditemukan: `jenis = '1671r'`, `cabang_id` sesuai cabang pusat, `transaksi_nilai` sesuai total belanja, `trash = 0`. | [x] PASS<br>[ ] FAIL | |
| 3.2 | **Rincian Item (MySQL)** | Tabel `transaksi_data`<br>`WHERE transaksi_id = [ID]` | Jumlah baris detail sesuai item belanja, nilai `harga`, `dpp_nilai`, dan `ppn_nilai` cocok persis. | [x] PASS<br>[ ] FAIL | |
| 3.3 | **Dokumen NoSQL (MongoDB)** | Koleksi `transaksi` & `transaksi_data` | Dokumen transaksi tersimpan di MongoDB lengkap dengan metadata pemilik session (`oleh_id = my_id()`). | [x] PASS<br>[ ] FAIL | |
| 3.4 | **Locker Saldo Kas (Locking)** | Tabel `locker_value`<br>`WHERE jenis = 'pettycash'` | 1. Baris `state = 'active'` berkurang senilai total transaksi.<br>2. Baris baru `state = 'hold'` bertambah senilai total transaksi dengan referensi ID nota `1671r`. | [x] PASS<br>[ ] FAIL | Nilai Hold: Rp _________ |
| 3.5 | **Buku Pembantu Kas Kecil** | Tabel `rekening_pembantu_kas` / `RekeningPembantuPettycash` | Mutasi tercatat pada akun COA kas kecil (`1010010040`) dengan referensi nomor nota `1671r`. | [x] PASS<br>[ ] FAIL | |

---

### FASE 4: Pengujian Kasus Batas & Validasi Negatif (Boundary / Negative Test)

| No | Skenario Uji Negatif | Tindakan Pengujian | Kriteria Keberhasilan (Expected Result) | Status | Hasil Aktual / Catatan |
| :-: | :--- | :--- | :--- | :-: | :--- |
| 4.1 | **Nominal Melebihi Saldo Kas** | Masukkan item belanja dengan nilai lebih besar dari sisa saldo kas aktif di header. | Sistem memblokir proses simpan dengan peringatan: *Saldo kas kecil tidak mencukupi*. | [x] PASS<br>[ ] FAIL | |
| 4.2 | **Tanpa Cabang Pembebanan** | Kosongkan pilihan cabang pembebanan lalu klik tombol proses. | Sistem menolak proses dan memunculkan pesan peringatan untuk memilih cabang pembebanan. | [x] PASS<br>[ ] FAIL | |
| 4.3 | **Tanpa Kategori / Item Biaya** | Memproses transaksi dengan keranjang belanja yang masih kosong. | Tombol proses tidak dapat dieksekusi atau muncul peringatan keranjang masih kosong. | [x] PASS<br>[ ] FAIL | |
| 4.4 | **PPN Tanpa Faktur Pajak** | Memilih opsi PPN namun data No e-Faktur / NPWP tidak diisi. | Sistem mewajibkan pengisian data faktur sebelum transaksi dilanjutkan. | [ ] PASS<br>[ ] FAIL | |
| 4.5 | **Akses dari Cabang Non-Pusat** | Login menggunakan akun cabang non-pusat (bukan grup `c_holding`). | Akses ke form `1671` ditolak (*Forbidden / Redirect ke Login/Dashboard*). | [x] PASS<br>[ ] FAIL | |

---

### FASE 5: Pengujian Alur Terhubung (Connected Workflow)

| No | Skenario Alur Lanjutan | Target Alur | Kriteria Keberhasilan (Expected Result) | Status | Hasil Aktual / Catatan |
| :-: | :--- | :--- | :--- | :-: | :--- |
| 5.1 | **Koneksi Otorisasi 1672** | Menu [FollowUp.php](file:///z:/everest_9sep/application/modules/pettycast/controllers/FollowUp.php)<br>`connectTo => 1672` | Nota `1671r` muncul di daftar kerja otorisator pada modul `1672`. Otorisator dapat menyetujui klaim sehingga status berubah menjadi approved (`1672`). | [x] PASS<br>[ ] FAIL | No. Nota Approval: `1672/_____` |
| 5.2 | **Fitur Edit Pengajuan** | `connectToEdit => 1671re` | Selama masih pending, kasir dapat membuka kembali nota `1671r` via `1671re`. Saldo `hold` di `locker_value` menyesuaikan kembali dengan revisi nilai. | [] PASS<br>[ ] FAIL | |
| 5.3 | **Fitur Reject / Pembatalan** | `connectToReject => 1671rrj` | Jika nota `1671r` di-reject oleh otorisator, status nota menjadi dibatalkan dan saldo `hold` kas kecil otomatis dilepas kembali ke saldo `active` (*reversal* utuh). | [x] PASS<br>[ ] FAIL | Saldo Reversal: Rp _________ |

---

### KESIMPULAN & TANDA TANGAN UAT

| Parameter Evaluasi | Hasil Penilaian |
| :--- | :--- |
| **Total Skenario Diuji** | _____ Kasus Uji |
| **Jumlah Kasus Berhasil (PASS)** | _____ Kasus Uji |
| **Jumlah Kasus Gagal (FAIL)** | _____ Kasus Uji |
| **Rekomendasi Rilis** | [ ] **DITERIMA (Ready for Production)**<br>[ ] **DITERIMA DENGAN CATATAN**<br>[ ] **DITOLAK (Need Fix / Re-test)** |

**Catatan Khusus Penguji / Supervisor:**
> ____________________________________________________________________________________
> ____________________________________________________________________________________
> ____________________________________________________________________________________

<br>

| Disiapkan Oleh (Kasir / Tester) | Disetujui Oleh (Supervisor / Otorisator) | Diverifikasi Oleh (Lead QA / IT) |
| :---: | :---: | :---: |
| <br><br><br>__________________________<br>Tanggal: | <br><br><br>__________________________<br>Tanggal: | <br><br><br>__________________________<br>Tanggal: |
