# 📐 BLUEPRINT LENGKAP: REUSABLE KPI SUMMARY CARDS (TOTAL + PER-PENYETOR)
**Modul/Target:** Reusable Widget (Dapat dipasang di Modul `settlement` & Dashboard `home/welcome`)  
**Target User:** Finance DC / Pusat, Executive / Management, Cabang Supervisor  
**Status:** Draf Blueprint (Menunggu Persetujuan Eksekusi)

---

## 1. Latar Belakang & Konsep Arsitektur Modular

Komponen **KPI SUMMARY CARDS (Total + Per-Penyetor)** dirancang secara **modular (*Reusable Component/Widget Pattern*)** di atas arsitektur HMVC CodeIgniter 3. 

Tujuannya adalah menyajikan visibilitas seketika (*instant visibility*) atas seluruh nominal hutang setoran tunai/bank dari cabang ke Pusat (`target_jenis = '759'` dan `sisa > 0`).

## 2. Arsitektur Data & Query (Data Aggregation Layer)

### 2.1 Sumber Data Database
Data dihitung dari tabel relational **`transaksi_payment_source`** dengan kriteria:
* `target_jenis = '759'` (Penyetoran Kas / Hutang Setoran)
* `sisa > 0` (Belum disetorkan / belum lunas disetor ke Pusat)

### 2.2 Agregasi Data yang Dibutuhkan
Data dihitung dari `transaksi_payment_source`:

### 1.1 Lokasi Pemasangan (Multi-Placement)
1. **Halaman Dashboard Utama (`home` / `welcome`)**:  
   Berfungsi sebagai *Executive Early Warning Alert System* yang menyambut pengguna Finance Pusat saat pertama kali login.
2. **Halaman Modul Settlement (`settlement/Transaksi/index/758`)**:  
   Berfungsi sebagai instrumen pengawasan operasional dan filter cepat (*in-place filter*) untuk memproses penerimaan setoran kas.

---

## 2. Struktur Komponen Kode (HMVC Component Hierarchy)

```
application/
├── models/
│   └── Coms/
│       └── ComKpiSettlement.php          <-- Model Bisnis Global: Agregasi SQL target_jenis=759 & sisa>0
├── views/
│   └── widgets/
│       └── v_kpi_settlement_cards.php    <-- Template View Reusable (HTML/CSS UI Component)
└── modules/
    ├── home/
    │   └── controllers/Home.php         <-- Controller Welcome/Home (Render Widget Alert)
    └── settlement/
        └── controllers/Transaksi.php    <-- Controller Modul Settlement (Render Widget Filter)
```

### 2.1 Model Bisnis: `ComKpiSettlement.php`
Berlokasi di `application/models/Coms/ComKpiSettlement.php` (extends `CI_Model`):
* `getSummaryGlobal($cabangId = null)`:
  * Menghitung `total_nominal_outstanding` (`SUM(sisa)`).
  * Menghitung `total_penyetor_aktif` (`COUNT(DISTINCT extern_id)`).
  * Menghitung `total_nota_outstanding` (`COUNT(id)`).
  * Menghitung `total_overdue_count` (`COUNT(DISTINCT extern_id)` di mana `dtime < NOW() - 24 JAM`).
* `getSummaryPerPenyetor($cabangId = null)`:
  * Mengelompokkan data berdasarkan `extern_id`, `extern_nama`, `cabang_id`, `cabang_nama`.
  * Mengembalikan daftar penyetor berisi: `extern_id`, `extern_nama`, `cabang_nama`, `total_sisa`, `jumlah_nota`, `min_dtime`, `max_aging_hours`, dan `status_aging` (`normal` / `warning` / `overdue`).

### 2.2 Reusable View Component: `v_kpi_settlement_cards.php`
Berlokasi di `application/views/widgets/v_kpi_settlement_cards.php`:
* Mengadaptasi tampilan seragam baik di Dashboard Home maupun di Modul Settlement.
* Menggunakan parameter `$context` (`'welcome'` atau `'settlement'`) untuk menyesuaikan aksi tombol:
  * Jika `$context == 'welcome'`: Tombol berupa **Direct Link** (`settlement/Transaksi/index/758?extern_id=XXX`).
  * Jika `$context == 'settlement'`: Tombol berupa **In-Place Filter** (Trigger JS/AJAX penyaringan tabel di halaman yang sama).

---

## 3. Tata Letak UI & Desain Visual Widget

```
====================================================================================================
 📊 REUSABLE KPI SUMMARY CARDS: OUTSTANDING KAS CABANG (DC / PUSAT)
====================================================================================================

 [ BARIS 1: KARTU TOTAL GLOBAL ]
 +--------------------------+ +--------------------------+ +--------------------------+
 | 💳 Total Belum Disetor   | | 👥 Penyetor Aktif        | | 🚨 Overdue (> 24 Jam)    |
 | Rp 45.200.000            | | 3 Personil               | | 1 Personil               |
 | (14 Nota Outstanding)    | | (Cabang Surabaya, Bndg) | | (Membutuhkan Action)   |
 +--------------------------+ +--------------------------+ +--------------------------+

 [ BARIS 2: REKAPITULASI PER-PENYETOR (CHIPS / MINI-CARDS GRID) ]
 +--------------------------------------------------------------------------------------------------+
 | 👤 Penyetor: Widya (Bandung)          | 👤 Penyetor: Indah (Surabaya)    | 👤 Penyetor: Sys Shadow (DC)|
 | Total  : Rp 17.580.000                | Total  : Rp 3.044.000            | Total  : Rp 4.000.000       |
 | Nota   : 5 Transaksi                  | Nota   : 3 Transaksi              | Nota   : 1 Transaksi         |
 | Status : 🔴 Overdue (3 Hari)          | Status : 🟢 Normal (6 Jam)        | Status : 🟡 Warning (28 Jam)|
 | [ 🚀 Buka Setoran (Direct Link) ]     | [ 🚀 Buka Setoran (Direct Link) ]| [ 🚀 Buka Setoran ]        |
 +--------------------------------------------------------------------------------------------------+
```

### 3.1 Aturan Indikator Warna Aging (Aging Color Badge)
* 🟢 **Normal (Hijau)**: Umur penahanan kas `< 24 jam` sejak tanggal transaksi (`dtime`).
* 🟡 **Warning (Kuning)**: Umur penahanan kas `24 - 48 jam`.
* 🔴 **Overdue (Merah)**: Umur penahanan kas `> 48 jam` (Prioritas penagihan utama).

---

## 4. Matriks Perilaku Komponen Berdasarkan Context

| Parameter / Fitur | Context: Halaman `home/welcome` | Context: Halaman `settlement/758` |
|---|---|---|
| **Tujuan Utama** | Alert & Early Warning saat login | Filter & Eksekusi Operasional |
| **Lokasi Layout** | Atas / Mid-Section Dashboard Welcome | Atas Zona `TRANSAKSI YANG PERLU ACTION` |
| **Tampilan Tombol** | `[ 🚀 Buka Setoran ]` | `[ 🔍 Filter Tabel ]` |
| **Aksi Tombol** | `location.href = base_url + 'settlement/Transaksi/index/758?extern_id=XXX'` | Trigger JS filter/AJAX reload tabel transaksi tanpa pindah halaman |

---

## 5. Standard Kepatuhan Coding (PHP 5.6 & CodeIgniter 3)
1. **PHP 5.6 Compatibility**:
   * DILARANG menggunakan operator null coalescing (`??`), short array syntax (`[]`), atau arrow function (`fn() => ...`).
   * Gunakan `isset($x) ? $x : $default` dan `array()`.
2. **Keamanan Query Database**:
   * Seluruh query wajib menggunakan CI Query Builder atau Query Binding (`$this->db->query($sql, $binds)`).
3. **Zero Placeholder**:
   * Penulisan fungsi pada tahap implementasi nantinya wajib lengkap dari pembuka `{` hingga penutup `}` tanpa pemotongan kode.
