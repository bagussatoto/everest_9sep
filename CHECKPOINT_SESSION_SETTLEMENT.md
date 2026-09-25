<!--  --># CHECKPOINT STATUS SESI - MODUL SETTLEMENT (759)
Tanggal Simpan: 11 September 2026

Dokumen ini mencatat ringkasan status pekerjaan, berkas yang diubah, dan langkah lanjutan berikutnya agar sesi dapat dilanjutkan kembali dengan presisi setelah restart.

---

## 1. Status Pekerjaan yang Telah Selesai (Completed)

1. **Gatekeeper Validasi Bank vs Tunai:**
   - Transaksi non-tunai/bank wajib diverifikasi terlebih dahulu sebelum checkbox dapat dipilih (`disabled` jika belum terverifikasi).
   - Kas tunai otomatis siap diproses langsung (`always tickable`).

2. **Penyelarasan Total Label UI ("Verifikasi Uang Masuk" & "Terverifikasi"):**
   - Tombol Card Bank: `<i class='fa fa-check-circle'></i> Verifikasi Uang Masuk`.
   - Judul Modal Rekonsiliasi: `Verifikasi Uang Masuk: [Nama Rekening]`.
   - Kolom Akun Kas/Bank di Tabel Utama: Badge `Terverifikasi` (Hijau) & `Belum Verifikasi` (Kuning).
   - Counter Card Atas: `X / Y Terverifikasi`.
   - Komponen Modal: Info box `SUDAH TERVERIFIKASI`, tombol `Verifikasi`, header kolom `Status Verifikasi`, tombol footer `Tutup Verifikasi`.

3. **Perapian Tata Letak Visual (Layout & Alignment):**
   - Badge status kolom "Akun Kas / Bank" tabel utama dibuat rata kanan (`pull-right`) sehingga sejajar vertikal lurus.
   - Header Card Kas Tunai ditambahkan info cabang pengguna via `my_cabang_nama()` (format: `KAS TUNAI • [Nama Cabang]`).

4. **Perbaikan Sinkronisasi Memori JavaScript (UAT Bug Fix):**
   - Penambahan sinkronisasi `is_bank_checked = 1` ke dalam array memori `bankReconItemsBySlug` pada callback AJAX verifikasi.
   - Info box, badge card, dan baris modal langsung ter-update seketika dan tidak kembali ke status sebelum verifikasi saat modal ditutup-buka tanpa reload.

5. **Aktivasi UI Fitur Setor Kas ke Bank:**
   - Tombol `Setor Kas ke Bank` di kartu Kas Tunai telah aktif.
   - Dialog modal rincian kas fisik (`#modalCashSetor`) siap digunakan lengkap dengan tabel nota kas cabang, pencarian instan, checkbox per baris & select all, serta kalkulator akumulasi nominal real-time.

---

## 2. Keputusan Arsitektur Terakhir

> **PENTING:**
> Disepakati untuk **membuat KODE JENIS TRANSAKSI BARU KHUSUS** untuk Setoran Kas ke Bank (BUKAN menggunakan transaksi `757`), agar alur akuntansi, gerbang nilai (*valueGates*), dan transaksi *existing* `757` tetap steril dan aman.

---

## 3. Berkas yang Terlibat & Telah Dimodifikasi

| Berkas | Keterangan Perubahan |
| :--- | :--- |
| `application/modules/settlement/views/transaksi.php` | Modifikasi Card Kas/Bank, Modal Rekonsiliasi Bank, Modal Setor Kas Fisik, JavaScript State & Handler. |
| `application/modules/settlement/controllers/Transaksi.php` | Pembaruan pesan sukses response AJAX `recordBankCheckerAjax`. |
| `ADVANCE_BANK_GRUP_RECONSILE.md` | Blueprint pengelompokan hierarki bank bertingkat (BCA, Mandiri, dll). |
| `HANDBOOK_BADGE_UNSETTLED_TRANSAKSI.md` | Panduan referensi badge transaksi belum terselesaikan. |

---

5. **Implementasi Metode B: Embed Form Modul Banking Resmi di Dalam Modal (0 Tab Baru) - [SELESAI & BERHASIL]:**
   - **Tujuan**: Kasir tidak perlu berpindah atau membuka tab baru, namun yang dieksekusi tetaplah form dan pipeline resmi modul Banking (`coTransaksiCore.php` $\rightarrow$ `components` master/detail $\rightarrow$ `gateway` $\rightarrow$ `Create.php:save()`).
   - **Alur Kerja**:
     1. Modal `#modalCashSetor` di Settlement: Kasir mencentang nota kas fisik yang akan disetor $\rightarrow$ nominal terakumulasi secara real-time.
     2. Tombol footer *"Lanjutkan Form Setor Kas (Rp X)"* memicu fungsi `openBankingEmbedModal()`:
        - Menutup modal pemilih nota kas.
        - Membuka modal container iframe `#modalBankingEmbed` (ukuran 96% layar).
        - Memuat URL `banking/Create/index/756?selector&id=[rekId]&minValue=[totalAkumulasi]&is_embed=1`.
     3. Form Transaksi 756 Embed:
        - Kolom selector kiri (`.col-md-3`), navigasi pagination, dan radio button view disembunyikan otomatis.
        - Form transaksi utama melebar 100% penuh (`.col-md-12`).
        - Label tabel dan formulir diselaraskan ke bahasa kasir perbankan:
          - `SOURCE ACCOUNT` $\rightarrow$ `SUMBER KAS`
          - `TRANSFER AMOUNT` $\rightarrow$ `NOMINAL SETORAN`
          - `TOTAL PRICE` $\rightarrow$ `TOTAL DISETOR`
          - `Account Source` $\rightarrow$ `Kas Tunai Asal`
          - `Rekening Bank` $\rightarrow$ `Rekening Bank Tujuan`
          - Placeholder catatan $\rightarrow$ `No. slip setoran bank / catatan kasir (opsional)...`
     4. Transaksi tersimpan ke database via pipeline resmi modul banking (`Create.php:save()`), yang secara native mengeksekusi `topReload()` sehingga otomatis menutup modal dan menyegarkan tampilan data kas Settlement. Tombol "Buka di Tab Baru" di header dan seluruh kontainer footer modal (`modal-footer`) telah dibersihkan agar antarmuka kasir bersih dan tidak membingungkan.

---

6. **Pemuatan Data Nota Terpilih via Selector Resmi & Model `MdlPaymentSource` - [SELESAI]:**
   - **Tujuan**: Mengambil data murni langsung dari database tabel `transaksi_payment_source` untuk setiap nota yang dicentang kasir, lalu memuatnya ke dalam sesi `$_SESSION['_TR_756']['items3_sum'][$id]`.
   - **Mekanisme**:
     1. Di `settlement/views/transaksi.php`: Setiap item nota menyertakan `payment_source_id` (`$it['id']` / `$it['tabel_id']`).
     2. Dibuat controller selector baru di modul Banking: `application/modules/banking/controllers/_processSelectPaymentSource.php`.
     3. Controller memanggil Model `Mdls/MdlPaymentSource`:
        ```php
        $this->load->model("Mdls/MdlPaymentSource");
        $mps = new MdlPaymentSource();
        $mps->setFilters(array());
        $mps->addFilter("id in ('" . implode("','", $cleanIds) . "')");
        $rows = $mps->lookupAll()->result_array();
        ```
     4. Data murni di-loop dan dimasukkan ke `$_SESSION[$cCode]['items3_sum'][$row['id']] = $row;`.
     5. Di frontend `settlement/views/transaksi.php`: Pemanggilan AJAX diarahkan ke `banking/_processSelectPaymentSource/select/756` sebelum modal embed maupun tab baru dimuat.
     6. Di modul banking `Create.php`: Array `items3_sum` terintegrasi otomatis ke dalam `$baseRegistries` saat penyimpanan transaksi.

7. **Penyempurnaan Layout Header Settlement (Hero Card + Grid 2 Baris Akun) - [SELESAI]:**
   - **Tujuan**: Menghadirkan hierarki visual yang jelas antara Grand Total konsolidasi dengan rincian rekening akun, serta menghemat ruang vertikal layar.
   - **Implementasi**:
     - Sisi Kiri: Card *"SEMUA REKENING"* mengambil tinggi penuh (*span 2 baris vertikal* dengan `align-self: stretch`), nominal angka besar `26px font-weight: 900`, subtitle konsolidasi, dan tombol *"Pilih Semua Item"*.
     - Sisi Kanan: Card akun rekening (Kas Tunai & Bank) disusun dalam format grid bertumpuk 2 baris vertikal (`grid-template-rows: repeat(2, minmax(82px, 1fr)); grid-auto-flow: column;`), berorientasi horizontal kompak (info judul & badge di baris atas; nominal & tombol aksi di baris bawah).

---

8. **Pengelompokan Card Settlement Berbasis Rekening Bank Penampung & Logo Bank - [SELESAI]:**
   - **Tujuan**: Mencegah kasir harus menghafal nomor rekening panjang dan menyatukan puluhan mesin EDC/QR ke rekening bank muaranya masing-masing.
   - **Implementasi**:
     - Memuat master `bank` sekali query (`bankMap`).
     - Menggunakan fungsi resolver `settlementResolveBankAccountGroup` untuk menelusuri rantai folder (`edc` $\rightarrow$ `account_in` $\rightarrow$ `bank`).
     - Menampilkan **Logo Resmi Bank (Hasil Unduhan Internet / Repositori Bank Resmi)** yang tersimpan di direktori `/public/images/` di header setiap kartu rekening.
     - Setiap nomor rekening bank penampung yang berbeda memiliki kartunya masing-masing (akurat 1-to-1 dengan rekening koran).
     - Menyematkan nomor rekening di footer kartu dan badge sub-channel asal pembayaran (misal: `<span class='badge bg-purple'>QR Mandiri</span>`) di dalam tabel modal rekonsiliasi.

9. **Fitur Auto-Select All pada Modal Setor Kas Fisik ke Bank - [SELESAI]:**
   - **Tujuan**: Mempercepat proses kerja kasir saat menyetor uang kas fisik ke bank.
   - **Implementasi**:
     - Saat modal `#modalCashSetor` dibuka via `openCashInterchangeModal(accSlug)`, sistem langsung menginisialisasi `cashSetorSelectedMap[it.transaksi_id] = true` untuk seluruh nota kas fisik.
     - Master checkbox header `#checkAllCashSetor` otomatis `checked`.
     - Metrik *"DIPILIH UNTUK DISETOR"* langsung terisi 100% nominal kas di sistem dan nominal tombol aksi *"Lanjutkan Form Setor Kas"* langsung terisi otomatis.
     - Kasir hanya perlu melakukan *uncheck* jika ada nota tertentu yang uang fisiknya tidak ikut disetor.

---

10. **Mekanisme Kontrol Ketat Kas Tunai 100% & Permohonan Uncheck Kasir (Approval Pengecualian Kas Cabang) - [SELESAI]:**
    - **Tujuan**: Mencegah kasir cabang meng-uncheck nota kas secara sepihak untuk menghindari kebocoran kas, namun tetap menyediakan alur permohonan resmi jika memang ada alasan operasional darurat.
    - **Alur Kasir**:
      - Checkbox nota kas terkunci secara default. Mengklik checkbox atau tombol izin akan membuka modal pengajuan: `#modalAjukanUncheckCash`.
      - Kasir menginput alasan mengapa nota tersebut belum/tidak disetor.
      - Sistem menyimpan permohonan ke tabel `cash_uncheck_request` dengan kode `REQ-EXC.YYYYMMDD.XXXX` dan mengirim notifikasi WhatsApp otomatis ke atasan melalui Fonnte API.
    - **Alur Atasan (c_holding / Settlement 758)**:
      - Atasan membuka Settlement jenis `758` (`settlement/Transaksi/index/758`), langsung disajikan **"Panel Otorisasi Permohonan Pengecualian Kas Cabang"** tepat di bawah widget rekapitulasi kas cabang.
      - Panel menampilkan badge counter pending real-time, tombol segarkan, dan tabel rincian request kasir (cabang, kasir, nota, nominal, dan alasan).
      - Tombol aksi *"Setujui"* atau *"Tolak"* memproses keputusan atasan seketika via AJAX.
      - Setelah disetujui, checkbox nota kas di modal kasir otomatis terbuka (`unlocked`), memungkinkan kasir menyelesaikan proses setor kas.

11. **Implementasi Modal Setor Kas Langsung (Direct Inline Modal / 0 Tab Baru) - [SELESAI]:**
    - **Tujuan**: Kasir dapat menyelesaikan proses setoran kas fisik ke bank secara cepat dan tuntas langsung di dalam modal tanpa membuka tab baru ataupun embed iframe.
    - **Alur Kerja**:
      1. Modal `#modalCashSetor` dilengkapi panel input:
         - Dropdown **Rekening Bank Tujuan** (di-load langsung dari master `MdlBankAccount_in` dengan format Nama Bank, Nomor Rekening, dan Nama Pemilik).
         - Input **Tanggal Setoran** (default: hari ini).
         - Input **Catatan / No. Slip Fisik** (opsional).
      2. Kasir memilih rekening bank tujuan, memeriksa nota yang dicentang, lalu menekan tombol hijau:
         **"Proses Setoran Kas Sekarang"** (`#btnCashSetorSubmit`).
      3. JavaScript `submitCashSetorDirect(btnEl)` memvalidasi input dan mengirim data via AJAX ke:
         `settlement/Transaksi/prosesSetorKasDirectAjax`.
      4. Endpoint backend memproses transaksi jenis `756` secara atomik di database:
         - Mengunci nomor nota berikutnya via counter generator.
         - Menyimpan header transaksi (`transaksi`), detail item setoran (`transaksi_data`), value gate nominal (`transaksi_values`), dan payment source (`transaksi_payment_source`).
      5. Sisi frontend menampilkan SweetAlert sukses, menutup modal secara otomatis, dan memperbarui metrik kas di halaman Settlement.

---

## 4. Berkas yang Terlibat & Telah Dimodifikasi

| Berkas | Keterangan Perubahan |
| :--- | :--- |
| `public/images/bca.svg` & `bca.png` | Logo resmi Bank Central Asia (BCA) diunduh dari repositori logo perbankan resmi. |
| `public/images/mandiri.svg` & `mandiri.png` | Logo resmi Bank Mandiri (Navy + Golden Ribbon) diunduh dari repositori resmi & Wikimedia. |
| `public/images/bni.svg` & `bni.png` | Logo resmi Bank Negara Indonesia (BNI 46) diunduh dari repositori logo perbankan resmi. |
| `public/images/bri.svg` & `bri.png` | Logo resmi Bank Rakyat Indonesia (BRI) diunduh dari repositori logo perbankan resmi. |
| `public/images/tunai.png` | Aset ikon Kas Tunai hijau emerald transparan 32-bit. |
| `application/models/Mdls/MdlCashUncheckRequest.php` | Model database untuk mengelola permohonan pengecualian kasir dan otorisasi atasan (`cash_uncheck_request`). |
| `application/modules/settlement/views/transaksi.php` | Penambahan panel input bank tujuan di modal setor kas, tombol submit AJAX langsung, modal permohonan uncheck kasir, dan tab otorisasi atasan 758. |
| `application/modules/settlement/controllers/Transaksi.php` | Endpoint AJAX `prosesSetorKasDirectAjax()`, `submitUncheckCashRequestAjax()`, `actionUncheckCashApprovalAjax()`, dan `getUncheckCashStatusListAjax()`. |
| `application/modules/banking/controllers/_processSelectPaymentSource.php` | Controller selector untuk memuat nota terpilih ke `items3_sum` dengan transformasi key `transaksi_id` $\rightarrow$ `refID` dan `nomer` $\rightarrow$ `refNum`. |
| `application/modules/settlement/controllers/Transaksi.php` | Penyelarasan method `prepareCashSetorSessionAjax()` dengan transformasi key `transaksi_id` $\rightarrow$ `refID` dan `nomer` $\rightarrow$ `refNum`. |
| `application/modules/banking/config/coTransaksiCore.php` | Registrasi konfigurasi transaksi jenis baru `756`. |
| `application/modules/banking/config/coTransaksiUi.php` | Konfigurasi UI, selector, label shopping cart, receiptElements untuk `756`. |
| `application/modules/banking/config/coTransaksiLayout.php` | Layout tabel dan template transaksi `756`. |
| `application/modules/banking/config/coTransaksiValues.php` | Aturan kalkulasi dan value gate untuk `756`. |
| `application/modules/banking/controllers/_processSelectRekening.php` | Penanganan injeksi parameter `minValue` ke field `harga` dan `cash_account_source`. |
| `application/modules/banking/controllers/Create.php` | Auto-load script selector item dan penyesuaian layout mode embed (lebar 100%, sembunyikan selector kiri, styling clean). |
| `application/modules/banking/controllers/_shoppingCart.php` | Dukungan dinamis `shoppingCartSubtotalLabel` untuk label total tabel item. |
