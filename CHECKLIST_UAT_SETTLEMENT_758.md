# 📋 LEMBAR KERJA & CHECKLIST UAT (USER ACCEPTANCE TESTING)
## Modul: Settlement 758 (Penerimaan Setoran Kas Pusat) — Modul Settlement

| Dokumen Kontrol | Keterangan |
| :--- | :--- |
| **Nomor Dokumen** | `UAT-EVEREST-SETTLEMENT-758` |
| **Modul Aplikasi** | `settlement` ([coTransaksiUi.php](file:///z:/everest_9sep/application/modules/settlement/config/coTransaksiUi.php) & [coTransaksiCore.php](file:///z:/everest_9sep/application/modules/settlement/config/coTransaksiCore.php)) |
| **Jenis Transaksi** | `758` (Penerimaan Setoran Kas di Pusat &rarr; Step 1: `758r`, Step 2: `758`) |
| **Alur Terkait** | `759` (Settle Harian Cabang), `756` (Banking Setor Kas ke Bank), `582` (Nota Penjualan Asal) |
| **Role Penguji (Cabang)** | Kasir / Finance Cabang (`o_finance`, `place = branch`) |
| **Role Penguji (SPV)** | Supervisor Cabang / Holding (`c_holding`, otorisator uncheck kas) |
| **Role Penguji (Pusat)** | Finance Kantor Pusat (`c_finance`, `place = center`) |
| **Tanggal Pengujian** | ____________________ |
| **Nama Penguji (Cabang)** | ____________________ |
| **Nama Penguji (SPV)** | ____________________ |
| **Nama Penguji (Pusat)** | ____________________ |

---

### Petunjuk Pengisian Status
- Beri tanda centang `[x]` pada kolom **Status** jika langkah berhasil diuji.
- Tuliskan hasil pada kolom **Hasil Aktual / Catatan** (misal: *Modal muncul*, *Status berubah Terverifikasi*, *Jurnal balance*, dll.).
- Nilai kesimpulan pengujian: **PASS**, **FAIL**, atau **BLOCKED**.

---

### FASE 1: Prasyarat & Lingkungan UAT (Pre-Conditions)

| No | Komponen yang Diperiksa | Kriteria Keberhasilan (Expected Result) | Status | Hasil Aktual / Catatan |
| :-: | :--- | :--- | :-: | :--- |
| 1.1 | **Hak Akses Finance Cabang** | Akun kasir cabang memiliki role `o_finance` pada cabang operasional. Berhasil mengakses menu settlement cabang ([Transaksi.php](file:///z:/everest_9sep/application/modules/settlement/controllers/Transaksi.php#L4559)). | [ ] PASS<br>[ ] FAIL | |
| 1.2 | **Hak Akses SPV / Holding** | Akun atasan memiliki grup wewenang `c_holding` atau kewenangan otorisasi pada menu `758`. Tab otorisasi pengecualian kas aktif saat login akun ini. | [ ] PASS<br>[ ] FAIL | |
| 1.3 | **Hak Akses Finance Pusat** | Akun kantor pusat memiliki role `c_finance` dengan `place = center` (Cabang ID = 0 / Pusat). Berhasil mengakses antarmuka penerimaan setoran kas `758`. | [ ] PASS<br>[ ] FAIL | |
| 1.4 | **Ketersediaan Nota Penjualan Kas** | Terdapat nota penjualan (`582`) bertransaksi kas fisik yang masih memiliki sisa tagihan/setoran (`sisa > 0`) di tabel `transaksi_payment_source`. | [ ] PASS<br>[ ] FAIL | No. Nota Kas: ____________<br>Nominal: Rp ____________ |
| 1.5 | **Ketersediaan Nota Penjualan Bank** | Terdapat nota penjualan (`582`) bertransaksi non-tunai (Transfer BCA / Mandiri / EDC / QRIS) yang siap diverifikasi uang masuknya. | [ ] PASS<br>[ ] FAIL | No. Nota Bank: ____________<br>Nominal: Rp ____________ |

---

### FASE 2: Skenario Kepatuhan Kas Fisik 100% & Permohonan Pengecualian Kasir

| No | Langkah Pengujian | Tindakan & Input Data | Kriteria Keberhasilan (Expected Result) | Status | Hasil Aktual / Catatan |
| :-: | :--- | :--- | :--- | :-: | :--- |
| 2.1 | **Akses Modal Setor Kas** | - Login sebagai Kasir Cabang.<br>- Buka URL `/settlement/Transaksi/selectPaymentSrc/759`.<br>- Klik tombol hijau **Setor Kas ke Bank** pada kartu KAS TUNAI. | Modal [modalCashSetor](file:///z:/everest_9sep/application/modules/settlement/views/transaksi.php#L12135) terbuka. Seluruh baris nota kas fisik tercentang otomatis 100% (`checkAllCashSetor = checked`). Total nominal kas terpilih terakumulasi penuh. | [ ] PASS<br>[ ] FAIL | Total Kas: Rp ____________ |
| 2.2 | **Uji Blokir Uncheck Mandiri** | Kasir mencoba mengklik checkbox salah satu nota kas untuk membatalkan centang (*uncheck*). | Checkbox **tidak boleh lepas** (kembali tercentang otomatis). Muncul modal merah [modalRequestUncheckCash](file:///z:/everest_9sep/application/modules/settlement/views/transaksi.php#L12246) dengan peringatan SOP Kepatuhan Kasir 100%. | [ ] PASS<br>[ ] FAIL | |
| 2.3 | **Input Permohonan Uncheck** | - Baca data no nota, pelanggan, dan nominal.<br>- Ketik alasan pada kolom textarea: *"Uang fisik dipakai untuk biaya darurat operasional"*. | Kolom alasan terisi valid. Tombol **1. Kirim ke Tab Atasan** dan **2. Persetujuan di Tempat** dalam kondisi aktif. | [ ] PASS<br>[ ] FAIL | Alasan: ___________________ |
| 2.4 | **Pengajuan Jalur Remote (Kirim ke SPV)** | Klik tombol kuning **1. Kirim ke Tab Atasan (Remote Approval)**. | Permohonan terkirim via AJAX ke [requestUncheckCashAjax](file:///z:/everest_9sep/application/modules/settlement/controllers/Transaksi.php#L8174). Modal tertutup. Baris nota di kasir memiliki badge kuning *"Menunggu Otorisasi Oleh Atasan (REQ/...)"*. | [ ] PASS<br>[ ] FAIL | No. Request: `REQ/________` |
| 2.5 | **Pencegahan Double Request** | Kasir mencoba mengklik uncheck lagi pada nota yang berstatus *Pending*. | Modal permohonan terbuka kembali dalam mode *Read-Only*, tombol kirim *disabled*, menampilkan alert: *"Permohonan Sedang Diproses Atasan"*. Kasir tidak bisa spam request ganda. | [ ] PASS<br>[ ] FAIL | |

---

### FASE 3: Skenario Otorisasi Atasan / SPV (Approval & Rejection)

| No | Langkah Pengujian | Tindakan & Input Data | Kriteria Keberhasilan (Expected Result) | Status | Hasil Aktual / Catatan |
| :-: | :--- | :--- | :--- | :-: | :--- |
| 3.1 | **Monitoring Tab Otorisasi Atasan** | - Login sebagai Atasan/SPV (`c_holding`).<br>- Buka URL `/settlement/Transaksi/selectPaymentSrc/758`. | Tab merah **Otorisasi Pengecualian Kas Cabang** ([views/transaksi.php](file:///z:/everest_9sep/application/modules/settlement/views/transaksi.php#L12524)) aktif. Badge merah menampilkan jumlah permohonan pending (`badgeSpvPendingCountTop > 0`). | [ ] PASS<br>[ ] FAIL | Pending Count: _____ req |
| 3.2 | **Pemeriksaan Detail Permohonan** | Periksa baris permohonan kasir pada tabel [tableSpvUncheckRequests](file:///z:/everest_9sep/application/modules/settlement/views/transaksi.php#L12542). | Data tampil lengkap: No. Request, Tanggal, Cabang, Kasir Pemohon, No. Nota, Nominal, Alasan Kasir, dan Status *Pending*. | [ ] PASS<br>[ ] FAIL | |
| 3.3 | **Aksi Setujui (Approve) oleh Atasan** | Klik tombol hijau **Setujui** pada baris permohonan. | Sistem mengeksekusi [actionUncheckCashApprovalAjax](file:///z:/everest_9sep/application/modules/settlement/controllers/Transaksi.php#L8387). Status berubah hijau *"Disetujui"*. Kolom aksi berganti menjadi info otorisasi nama atasan dan waktu persetujuan. | [ ] PASS<br>[ ] FAIL | Disetujui oleh: ___________ |
| 3.4 | **Verifikasi Uncheck Pasca-Approve** | Kasir membuka kembali modal setor kas di cabang dan mengklik uncheck pada nota yang telah disetujui. | Checkbox **berhasil terlepas (uncheck)**. Nilai total kas yang disetor berkurang senilai nota tersebut. Nilai sisa belum disetor bertambah. Badge hijau *"Izin: [Nama SPV]"* terpampang jelas. | [ ] PASS<br>[ ] FAIL | Sisa Kas: Rp _____________ |
| 3.5 | **Aksi Tolak (Reject) oleh Atasan** | - Pada permohonan lain, SPV klik tombol merah **Tolak**.<br>- Modal input alasan penolakan terbuka.<br>- Isi alasan: *"Kas fisik wajib disetor 100% tanpa pengecualian"*, klik Konfirmasi Tolak. | Status permohonan berubah merah *"Ditolak"*. Di sisi kasir, nota terkunci permanen wajib disetor 100% dengan badge merah *"Ditolak Atasan"*. | [ ] PASS<br>[ ] FAIL | Alasan Tolak: _____________ |
| 3.6 | **Persetujuan Cepat di Tempat (Instant SPV)** | - Kasir ajukan uncheck nota baru.<br>- Klik tombol **2. Persetujuan di Tempat**.<br>- SPV mengetikkan Username/NIK & Password di form panel.<br>- Klik tombol Verifikasi & Setujui Sekarang. | Kredensial divalidasi via [instantApproveUncheckCashAjax](file:///z:/everest_9sep/application/modules/settlement/controllers/Transaksi.php#L8251). Jika password valid, nota langsung disetujui seketika tanpa perlu membuka tab baru. Centang nota otomatis terlepas. | [ ] PASS<br>[ ] FAIL | SPV: _____________________ |

---

### FASE 4: Skenario Rekonsiliasi & Verifikasi Uang Masuk Bank (Bank Checker)

| No | Langkah Pengujian | Tindakan & Input Data | Kriteria Keberhasilan (Expected Result) | Status | Hasil Aktual / Catatan |
| :-: | :--- | :--- | :--- | :-: | :--- |
| 4.1 | **Pemeriksaan Status Awal Bank** | - Di layar Settlement 758, periksa kartu bank (misal: BCA / Mandiri).<br>- Periksa tabel utama di bawah kartu. | Kartu bank menampilkan badge abu-abu/kuning (*"0 / X Terverifikasi"*). Pada tabel utama, baris nota bank memiliki badge kuning *"Belum Verifikasi"* dan kotak centangnya **terkunci mati (disabled)**. | [ ] PASS<br>[ ] FAIL | |
| 4.2 | **Akses Modal Rekonsiliasi Bank** | Klik tombol biru **Verifikasi Uang Masuk** pada kartu bank terkait. | Modal [modalBankReconcile](file:///z:/everest_9sep/application/modules/settlement/views/transaksi.php#L12054) terbuka. Menampilkan 3 kartu metrik: *Total di Sistem*, *Sudah Terverifikasi*, dan *Belum Diverifikasi / Pending*. | [ ] PASS<br>[ ] FAIL | |
| 4.3 | **Pencocokan Mutasi Rekening Koran** | Cocokkan nomor nota, nama pelanggan, dan nominal transfer dengan mutasi rekening koran asli perusahaan. | Data nominal sistem cocok persis dengan mutasi riil bank. | [ ] PASS<br>[ ] FAIL | Nominal: Rp ____________ |
| 4.4 | **Input Verifikasi Bank** | - Kolom Tgl Uang Masuk: pilih tanggal dana masuk.<br>- Kolom Catatan Bank: input referensi/jam mutasi (misal: *"Mutasi BCA ref #8812 pkl 11:20"*).<br>- Klik tombol merah **Verifikasi**. | Sistem menyimpan verifikasi via [recordBankCheckerAjax](file:///z:/everest_9sep/application/modules/settlement/controllers/Transaksi.php#L7994). Tombol berubah menjadi badge hijau **"Terverifikasi"** dengan tooltip nama verifikator & timestamp audit. | [ ] PASS<br>[ ] FAIL | Ref: ____________________ |
| 4.5 | **Kalkulasi Ulang Metrik Realtime** | Amati 3 kartu metrik di bagian atas modal setelah verifikasi diklik. | Angka *Sudah Terverifikasi* bertambah, angka *Pending* berkurang secara otomatis tanpa perlu merefresh halaman browser. | [ ] PASS<br>[ ] FAIL | Verified: Rp ____________ |
| 4.6 | **Pelepasan Kunci Tabel Utama** | Klik tombol **Tutup Verifikasi** untuk kembali ke workspace settlement utama. | Baris nota bank tersebut di tabel utama otomatis berganti menjadi badge hijau **"Terverifikasi"**, dan kotak centang kolom pertama **langsung aktif (enabled / siap dipilih)**. | [ ] PASS<br>[ ] FAIL | |

---

### FASE 5: Skenario Penerimaan Setoran Kas di Pusat (FollowUp Step 1 &rarr; Step 2)

| No | Langkah Pengujian | Tindakan & Input Data | Kriteria Keberhasilan (Expected Result) | Status | Hasil Aktual / Catatan |
| :-: | :--- | :--- | :--- | :-: | :--- |
| 5.1 | **Inisiasi Setoran Cabang (758r)** | - Finance Cabang memilih nota kas & bank yang telah sah disetor.<br>- Eksekusi simpan transaksi setoran. | Sistem menerbitkan nomor transaksi setoran berstatus `758r` (*pending acceptance* oleh pusat). | [ ] PASS<br>[ ] FAIL | No. Tr Setor: `758r/_____` |
| 5.2 | **Buka FollowUp di Pusat** | Finance Pusat membuka URL FollowUp:<br>`/settlement/FollowUp/followupPrePreview/758/[ID_SETORAN]/2/1`. | Halaman [FollowUp.php](file:///z:/everest_9sep/application/modules/settlement/controllers/FollowUp.php#L672) terbuka. Menampilkan ringkasan cabang pengirim, nominal setoran, dan rincian alokasi nota. | [ ] PASS<br>[ ] FAIL | Total Setor: Rp _________ |
| 5.3 | **Aktivasi Concurrency Locker** | Sesaat setelah Finance Pusat membuka halaman FollowUp, amati tabel `stock_locker_transaksi`. | Record baru terbuat di `stock_locker_transaksi`: `state = 'hold'`, `jumlah = '1'`, `oleh_id = my_id()`, `transaksi_id = [ID_SETORAN]`. Transaksi terkunci atas nama petugas pusat tersebut. | [ ] PASS<br>[ ] FAIL | Holder: __________________ |
| 5.4 | **Eksekusi Terima Setoran (Save FollowUp)** | Finance Pusat memeriksa kesesuaian nilai, lalu mengklik tombol hijau **Konfirmasi Penerimaan / Selesai** (`doFollowup`). | Sistem memproses penerimaan via [doFollowup()](file:///z:/everest_9sep/application/modules/settlement/controllers/FollowUp.php#L4585). Muncul bar progress, transaksi sukses disimpan, dan status transaksi berubah menjadi `758` (*Completed*). | [ ] PASS<br>[ ] FAIL | Status Final: `758` |
| 5.5 | **Pelepasan Locker Transaksi** | Periksa kembali tabel `stock_locker_transaksi` setelah proses selesai. | Kunci transaksi `state = 'hold'` dilepaskan (`jumlah = '0'`). Transaksi tidak lagi dalam status terkunci. | [ ] PASS<br>[ ] FAIL | |

---

### FASE 6: Verifikasi Backend, Database & Jurnal Akuntansi Dual-Entry

| No | Objek Pemeriksaan | Sumber Verifikasi / Query SQL | Kriteria Keberhasilan (Expected Result) | Status | Hasil Aktual / Catatan |
| :-: | :--- | :--- | :--- | :-: | :--- |
| 6.1 | **Tabel Audit Uncheck Kas** | `SELECT * FROM cash_uncheck_request WHERE transaksi_id = [ID];` | Record tercatat valid: `status` ('approved'/'rejected'), `kasir_id`, `kasir_nama`, `spv_id`, `spv_nama`, `alasan_kasir`, `dtime_request`, `dtime_action`. | [ ] PASS<br>[ ] FAIL | ID Request: ____________ |
| 6.2 | **Tabel Audit Bank Checker** | `SELECT * FROM bank_checker WHERE transaksi_id = [ID];` | Record tercatat valid: `checker_id`, `checker_nama`, `dtime_bank`, `notes`, `isAudit`. | [ ] PASS<br>[ ] FAIL | Checker: ________________ |
| 6.3 | **Header Transaksi MySQL** | `SELECT * FROM transaksi WHERE id = [ID_SETORAN];` | `jenis = '758'`, `step_current = 2`, `transaksi_nilai` cocok dengan total setoran, `trash = 0`. | [ ] PASS<br>[ ] FAIL | Nilai: Rp _______________ |
| 6.4 | **Jurnal Buku Cabang** | Buku Besar Cabang (`place2ID`):<br>- Kas Cabang (`1010010010`)<br>- Hutang ke Pusat (`2040010`)<br>- Laba Ditempatkan Pusat (`3020050`) | 1. Akun Kas Cabang berkurang di posisi **KREDIT (-)** senilai setoran.<br>2. Akun Hutang ke Pusat / Laba berkurang di posisi **DEBET (+)**.<br>3. Total Debet = Total Kredit (**Balance**). | [ ] PASS<br>[ ] FAIL | Debet: Rp _______________<br>Kredit: Rp _______________ |
| 6.5 | **Jurnal Buku Pusat** | Buku Besar Pusat (`placeID`):<br>- Kas / Bank Pusat (`1010010010`)<br>- Piutang Cabang (`1010060010`)<br>- Laba Ditempatkan Pusat (`3020050`) | 1. Akun Kas Pusat bertambah di posisi **DEBET (+)** senilai setoran.<br>2. Akun Piutang Cabang / Laba berkurang di posisi **KREDIT (-)**.<br>3. Total Debet = Total Kredit (**Balance**). | [ ] PASS<br>[ ] FAIL | Debet: Rp _______________<br>Kredit: Rp _______________ |
| 6.6 | **Rekening Pembantu Antarcabang** | Tabel `rekening_pembantu_antarcabang` | Mutasi antar-cabang tereliminasi secara simetris antara buku cabang dan buku pusat tanpa ada selisih saldo menggantung. | [ ] PASS<br>[ ] FAIL | Selisih: Rp 0 (NOL) |

---

### FASE 7: Pengujian Kasus Batas, Negatif & Concurrency Control

| No | Skenario Uji Negatif | Tindakan Pengujian | Kriteria Keberhasilan (Expected Result) | Status | Hasil Aktual / Catatan |
| :-: | :--- | :--- | :--- | :-: | :--- |
| 7.1 | **Tabrakan User (Concurrency Lock)** | User B membuka transaksi di FollowUp. User C membuka URL yang sama persis di browser lain. | Layar User C **diblokir** dengan tampilan *"Transaksi Terkunci oleh [User B]"*. User C tidak bisa melakukan approval paralel. | [ ] PASS<br>[ ] FAIL | |
| 7.2 | **Proteksi Ambil Alih (< 5 Menit)** | User C melihat transaksi terkunci saat User B baru aktif < 5 menit. | Tombol *"Ambil Alih Transaksi"* dalam kondisi **mati / disabled**. User C tidak boleh merebut transaksi petugas aktif. | [ ] PASS<br>[ ] FAIL | |
| 7.3 | **Ambil Alih Petugas Idle (> 5 Menit)** | User B mendiamkan halaman tanpa aktivitas selama lebih dari 5 menit. User C merefresh halaman. | Tombol **"Ambil Alih Transaksi (Petugas Idle)"** aktif. User C mengklik tombol tersebut &rarr; lock User B dibersihkan &rarr; User C berhasil mengambil alih transaksi. | [ ] PASS<br>[ ] FAIL | |
| 7.4 | **Password SPV Salah di Tempat** | Kasir memasukkan password SPV yang salah pada form persetujuan di tempat (*instant approval*). | Sistem menolak dengan pesan: *"Password atasan tidak sesuai"*. Permohonan tidak disetujui dan centang nota tetap terkunci. | [ ] PASS<br>[ ] FAIL | |
| 7.5 | **Bypass Centang Nota Bank Bodong** | Mencoba mencentang nota bank yang belum diverifikasi mutasinya. | Checkbox tidak dapat diklik (*disabled*), tooltip menampilkan: *"Wajib melakukan verifikasi uang masuk terlebih dahulu..."*. | [ ] PASS<br>[ ] FAIL | |

---

### KESIMPULAN & TANDA TANGAN UAT

| Parameter Evaluasi | Hasil Penilaian |
| :--- | :--- |
| **Total Skenario Diuji** | _____ Kasus Uji |
| **Jumlah Kasus Berhasil (PASS)** | _____ Kasus Uji |
| **Jumlah Kasus Gagal (FAIL)** | _____ Kasus Uji |
| **Persentase Keberhasilan** | _____ % |
| **Rekomendasi Rilis** | [ ] **DITERIMA (Ready for Production)**<br>[ ] **DITERIMA DENGAN CATATAN (Minor Issue)**<br>[ ] **DITOLAK (Major Bug / Need Re-test)** |

**Catatan Khusus Tim Penguji / Supervisor Akuntansi:**
> ____________________________________________________________________________________
> ____________________________________________________________________________________
> ____________________________________________________________________________________

<br>

| Disusun Oleh (Penguji Cabang) | Diverifikasi Oleh (SPV Otorisator) | Disetujui Oleh (Finance Pusat) |
| :---: | :---: | :---: |
| <br><br><br>_________________________<br>Tanggal: | <br><br><br>_________________________<br>Tanggal: | <br><br><br>_________________________<br>Tanggal: |
