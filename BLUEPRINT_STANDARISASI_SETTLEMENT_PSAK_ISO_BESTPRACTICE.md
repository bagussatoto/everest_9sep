# 📐 BLUEPRINT LENGKAP: STANDARISASI MODUL SETTLEMENT (758 - PENERIMAAN SETORAN KAS)
**Kepatuhan:** Best Practice ERP (COSO), Standar ISO (ISO 9001:2015 & ISO 27001:2022), Standar Akuntansi Keuangan (PSAK 1 & PSAK 2)  
**Target Modul:** `application/modules/settlement/`  
**Fitur Utama:** Verifikasi Otorisasi FollowUp Preview (`FollowUp::followupPreview`)  
**Status:** Draf Blueprint Teknis (Menunggu Persetujuan Eksekusi)

---

## 1. Eksekutif Ringkasan & Latar Belakang

Halaman **Followup Preview Penerimaan Setoran Kas (`758`)** pada URL `settlement/FollowUp/followupPreview/758/[ID_REF]/2/1` merupakan pintu gerbang validasi otorisasi keuangan (*maker-checker*) saat petugas keuangan pusat (*c_finance*) menerima berkas setoran kas/bank yang diajukan oleh penyetor cabang (*o_finance*).

Hasil audit sistem terhadap antarmuka dan basis kode menemukan **3 anomali kritis** serta beberapa catatan kepatuhan tata kelola:
1. **Kegagalan Rekonsiliasi Kas (Footing Discrepancy):**  
   Tabel 1 (Rincian Nota) menampilkan total Rp 24.922.000 yang bersumber dari 3 pos penerimaan (Tunai Rp 2,442 jt, Rekening Bank Rp 19,98 jt, dan Renos Rp 2,5 jt). Namun Tabel 2 (Rekapitulasi Akun) hanya mencantumkan 1 baris (*Renos Rp 2.500.000*) sementara baris totalnya tetap Rp 24.922.000. Pos Tunai dan Bank hilang dari rekapitulasi.
2. **Informasi Header Statis yang Menyesatkan (Misleading Metadata):**  
   Header tertulis *"metode setoran tunai: Cash/Tunai"* dan *"akun kas/bank setor tunai: Tunai"*, padahal >80% nilai transaksi berasal dari transfer bank.
3. **Format Identitas Rekening Bank yang Ambigu:**  
   Nomor rekening ditulis dalam format koma numerik uang (`6,583.797.888`) menyerupai nominal rupiah, memicu risiko kesalahan manusia (*human error*).
4. **Ketiadaan Pratinjau Jurnal Berpasangan & Bukti Lampiran Fisik:**  
   Belum ada pratinjau debit/kredit sebelum otorisasi serta ketiadaan tautan bukti setor transfer bank untuk pencocokan mutasi rekening koran.

---

## 2. Analisis Akar Masalah Teknis (Root Cause Analysis)

### 2.1 Bug Agregasi Sesi pada `_processSelectNota.php` (Penyebab Utama Tabel 2 Hilang)
Pemeriksaan kode pada berkas [`_processSelectNota.php`](file:///z:/everest_9sep/application/modules/settlement/controllers/_processSelectNota.php#L1225-L1240) mengungkap adanya cacat logika perulangan:

```php
// KODE SAAT INI DI _processSelectNota.php (L1225-L1238)
if(count($items8) > 0){
    foreach ($items8 as $cash_id => $cashData){
        $items8_sum = array(); // <-- BUG: Array rekap di-reset menjadi kosong di setiap iterasi akun kas!
        $aa = 0;
        foreach ($cashData as $cashData_0){
            $aa += $cashData_0["sisa"];
            $items8_sum[$cash_id] = $cashData_0;
            if(!isset($items8_sum[$cash_id]["nilai_setor"])){
                $items8_sum[$cash_id]["nilai_setor"] = 0;
            }
            $items8_sum[$cash_id]["nilai_setor"] = $aa;
        }
        $_SESSION[$cCode]['items8_sum'] = $items8_sum; // <-- BUG: Ditimpa di dalam loop, sehingga hanya akun terakhir (Renos) yang tersimpan!
    }
}
```

* **Dampak:** Ketika transaksi memiliki beberapa akun kas (misal: 1=Tunai, 2=BCA, 3=Renos), iterasi ke-2 menghapus data iterasi ke-1, dan iterasi ke-3 menghapus data iterasi ke-2. Akibatnya, `$_SESSION[$cCode]['items8_sum']` hanya menyisakan data akun terakhir (Renos Rp 2,5 jt).

### 2.2 Format Nomor Rekening Menggunakan Formatter Angka Nominal
Pada proses pembentukan data, nomor rekening bank yang tersimpan sebagai string angka diformat menggunakan fungsi `number_format()`, menghasilkan tampilan `6,583.797.888` yang membingungkan kasir.

### 2.3 Label Hak Akses Stepper Belum Terdefinisi
Pada `coTransaksiUi.php` jenis transaksi `758`, step 1 memiliki `userGroup => "sys"`, namun relasi `connectTo` dan label hak akses `availHakAkses()` belum terhubung sempurna ke kamus peran pengguna, memunculkan teks abu-abu *"HAK AKSES BELUM DIATUR"*.

---

## 3. Matriks Standarisasi Kepatuhan (Compliance Matrix)

| Dimensi | Standar Acuan | Persyaratan Standar | Kondisi Saat Ini | Desain Solusi Blueprint |
| :--- | :--- | :--- | :--- | :--- |
| **PSAK 1 & 2** | Standar Akuntansi Keuangan | *Faithful Representation & Completeness*: Total rekonsiliasi kas wajib sama persis dengan rincian nota ($\sum \text{Rekap} = \sum \text{Nota}$). | Rekap kas hanya Rp 2,5 jt, total Rp 24,92 jt (selisih Rp 22,42 jt hilang dari rekap). | Perbaikan algoritma grouping multi-akun kas tanpa *overwrite*, menjamin $\sum \text{Rekap} = \sum \text{Detail}$. |
| **PSAK 1** | Standar Akuntansi Keuangan | *Accrual & Dual-Entry Verification*: Kejelasan pos debit & kredit sebelum pengesahan buku besar. | Otorisator hanya melihat angka nota, tanpa tahu mutasi jurnal yang akan terbentuk. | Penambahan panel pratinjau jurnal (*Pre-Journal Preview*) sebelum tombol approve ditekan. |
| **ISO 27001** | Keamanan & Integritas Data (A.12/A.14) | *Data Processing Integrity*: Sistem wajib memvalidasi keutuhan data dan mencegah transaksi dengan data timpang. | Halaman merender total Rp 24,92 jt meski rincian rekapitulasi tidak lengkap tanpa peringatan. | Penerapan *Integrity Assertion Guard*: Blokir/peringatkan jika terjadi ketimpangan angka rekapitulasi. |
| **ISO 9001** | Manajemen Mutu (Klausul 5.3 & 7.5) | *Roles, Authorities & Traceability*: Wewenang setiap tahapan terdefinisi jelas dan dokumen terlacak. | Stepper step 1 menampilkan *"HAK AKSES BELUM DIATUR"*. | Pemetaan eksplisit grup pengguna (`o_finance` penyetor, `c_finance` penerima) pada kamus hak akses. |
| **Best Practice** | COSO Internal Control | *Dual Verification & Fraud Prevention*: Pencocokan fisik/mutasi bank sebelum serah terima kas. | Kasir pusat tidak memiliki akses cepat melihat lampiran slip/bukti mutasi bank cabang. | Penyediaan tombol/tautan preview lampiran bukti setor (*supporting document attachment*). |
| **Best Practice** | UI/UX & Human Error Prevention | *Clarity & Consistency*: Label header harus mencerminkan isi dan nomor rekening tidak ambigu. | Header menulis "Tunai", nomor rekening berformat koma uang (`6,583.797.888`). | Header dinamis ("Campuran/Multi-Akun"), nomor rekening diformat tanpa pemisah desimal koma uang. |

---

## 4. Arsitektur Solusi Teknis Per Komponen

### 4.1 Modifikasi Controller `_processSelectNota.php` (Perbaikan Agregasi Akun Kas)
Lokasi: `application/modules/settlement/controllers/_processSelectNota.php`

**Logika Solusi:**
Inisialisasi `$items8_sum = array()` di luar loop utama, dan simpan ke `$_SESSION[$cCode]['items8_sum']` setelah seluruh perulangan akun selesai diproses:

```php
// START OF COMPLETE REPEATED LOGIC
if (count($items8) > 0) {
    $items8_sum = array(); // Inisialisasi SEKALI di luar loop
    foreach ($items8 as $cash_id => $cashData) {
        $totalPerAkun = 0;
        $sampleData = null;
        foreach ($cashData as $cashData_0) {
            $totalPerAkun += $cashData_0["sisa"];
            if ($sampleData === null) {
                $sampleData = $cashData_0;
            }
        }
        if ($sampleData !== null) {
            $items8_sum[$cash_id] = $sampleData;
            $items8_sum[$cash_id]["nilai_setor"] = $totalPerAkun;
        }
    }
    $_SESSION[$cCode]['items8_sum'] = $items8_sum; // Disimpan utuh setelah seluruh akun diagregasi
}
// END OF COMPLETE REPEATED LOGIC
```

### 4.2 Modifikasi Controller `FollowUp.php` & `coTransaksiLayout.php` (Header Dinamis & Tipografi)
Lokasi: `application/modules/settlement/controllers/FollowUp.php`

1. **Header Dinamis (Metode Setoran):**
   - Hitung komposisi pos kas dalam setoran:
     - Jika 100% adalah kas fisik $\rightarrow$ Tampilkan *"Cash / Tunai"*.
     - Jika 100% adalah bank transfer $\rightarrow$ Tampilkan *"Bank Transfer"*.
     - Jika terdapat >1 jenis akun kas $\rightarrow$ Tampilkan *"Multi-Akun / Campuran (Tunai, Bank, Marketplace)"*.
2. **Normalisasi Tampilan Nomor Rekening:**
   - Bersihkan pemformatan nomor rekening agar tidak memanggil `number_format()` yang menyisipkan koma uang. Gunakan format string bersih (misal: `6583797888` atau `658-379-7888`).

### 4.3 Penambahan Pratinjau Jurnal Berpasangan (*Pre-Journal Preview*)
Lokasi: View `transaksi.php` dan Controller `FollowUp.php`

Menampilkan tabel akuntansi sebelum tombol otorisasi:
```
┌────────────────────────────────────────────────────────┐
│ PRATINJAU JURNAL TRANSAKSI (SETTLEMENT 758)            │
├──────────────┬────────────────────────┬────────┬───────┤
│ Kode Akun    │ Nama Akun              │ Debet  │ Kredit│
├──────────────┼────────────────────────┼────────┼───────┤
│ 11201-001    │ Bank BCA Pusat         │ 19.980 │     - │
│ 11101-001    │ Kas Operasional Pusat  │  2.442 │     - │
│ 11305-001    │ Escrow Renos           │  2.500 │     - │
│ 11199-002    │ Kas Transit Cab Pinang │      - │ 24.922│
├──────────────┴────────────────────────┼────────┼───────┤
│ TOTAL (BALANCE)                       │ 24.922 │ 24.922│
└────────────────────────────────────────────────────────┘
```

### 4.4 Komponen Lampiran Bukti Setor (*Attachment Verification*)
Menyediakan ikon/tombol di samping header untuk membuka modal bukti setor transfer bank yang diunggah oleh kasir cabang saat Step 1.

---

## 5. Rencana Pentahapan Implementasi (Phasing Strategy)

```
┌───────────────────────────────────────────────────────────────────────────┐
│ FASE 1: PERBAIKAN KRITIS (Zero Tolerance & Data Integrity)                │
│ • Perbaikan loop items8_sum di _processSelectNota.php                     │
│ • Header dinamis metode setoran (Tunai vs Bank vs Campuran)               │
│ • Penegakan Footing Test: Rekapitulasi Kas = Total Nota                   │
└───────────────────────────────────────────────────────────────────────────┘
                                      │
                                      ▼
┌───────────────────────────────────────────────────────────────────────────┐
│ FASE 2: KEPATUHAN MUTU & OPERASIONAL (ISO 9001 & Best Practice)           │
│ • Pemetaan kamus hak akses stepper (hilangkan "Hak Akses Belum Diatur")   │
│ • Pembersihan format tipografi nomor rekening                             │
│ • Integrasi modal pratinjau bukti transfer cabang                         │
└───────────────────────────────────────────────────────────────────────────┘
                                      │
                                      ▼
┌───────────────────────────────────────────────────────────────────────────┐
│ FASE 3: STANDARISASI AKUNTANSI LANJUTAN (PSAK Compliance)                 │
│ • Panel Pratinjau Jurnal Berpasangan (Debit / Kredit) di Followup Preview │
│ • Standardisasi Chart of Accounts (COA) resmi                             │
└───────────────────────────────────────────────────────────────────────────┘
```

---

## 6. Rencana Verifikasi & Pengujian (Verification Plan)

| ID Uji | Skenario Pengujian | Hasil yang Diharapkan |
| :--- | :--- | :--- |
| **TC-01** | Buka URL followupPreview dengan multi-akun (Tunai, Bank, Renos). | Tabel 2 menampilkan ke-3 baris akun: Tunai Rp 2,442 jt, Bank Rp 19,98 jt, Renos Rp 2,5 jt. |
| **TC-02** | Verifikasi Baris Total pada Tabel 2. | Total Tabel 2 persis sama dengan Total Tabel 1 (Rp 24.922.000). |
| **TC-03** | Verifikasi Teks Header Metode Setoran. | Header menulis "Multi-Akun / Campuran", bukan statis "Cash/Tunai". |
| **TC-04** | Verifikasi Tipografi Nomor Rekening. | Rekening tertulis `6583797888`, tidak ada titik/koma uang pecahan. |
| **TC-05** | Pengujian Transaksi Tunggal (100% Tunai). | Tabel 2 hanya 1 baris (Tunai), Header otomatis menulis "Cash / Tunai". |
| **TC-06** | Pengujian Transaksi Tunggal (100% Bank). | Tabel 2 hanya 1 baris (Bank), Header otomatis menulis "Bank Transfer". |
