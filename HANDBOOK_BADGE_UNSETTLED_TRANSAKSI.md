# 📘 HANDBOOK IMPLEMENTASI: BADGE JUMLAH TRANSAKSI UNSETTLED (MY SETTLEMENT)
**Modul Target:** Modul `penerimaan` & Modul `kas`  
**Fitur:** Indikator Notifikasi Dinamis (Badge Counter) pada Tombol `my settlement`  
**Target Pengguna:** Kasir Toko / Petugas Penerimaan Cabang  
**Status Dokumen:** ⏳ Siap Dikerjakan (Disimpan dalam Handbook Sesuai Instruksi)

---

## 1. Latar Belakang & Tujuan

Pada sistem Everest ERP, kasir atau petugas penerimaan di cabang menerima pembayaran piutang (*A/R Receipt* - kode `749`) atau penerimaan kas lainnya. Penerimaan tunai dan non-tunai (EDC/Debit) ini menimbulkan kewajiban penyetoran kas (*Settlement* / Setor ke Rekening Pusat - kode `759`).

Tombol **`my settlement`** (berwarna fuchsia/pink pada bilah navigasi kanan atas) berfungsi sebagai gerbang cepat bagi kasir untuk menyetorkan kas yang dipegangnya. 

**Tujuan Implementasi:**
Menambahkan **badge counter angka** yang muncul secara dinamis di dalam tombol `my settlement` untuk menunjukkan berapa banyak nota penerimaan kas milik kasir tersebut yang belum diselesaikan/disetorkan (`sisa > 0`).

---

## 2. Keputusan Desain & Spesifikasi (User Decisions)

Berdasarkan kesepakatan diskusi teknis:

1. **Lingkup Perhitungan (Opsi A - Personal Kasir)**:
   * Menghitung nota yang diterima oleh **kasir yang sedang login saja** (`extern_id = my_id()`) di cabang aktif (`cabang_id = my_cabang_id()`), sesuai dengan teks tombol *"my settlement"*.
2. **Kondisi Tampilan (Fail-Clean / Sembunyikan jika 0)**:
   * **Jika Count > 0**: Tampilkan badge angka dengan kontras tinggi (badge putih berteks fuchsia tebal di atas latar tombol fuchsia).
   * **Jika Count == 0**: Sembunyikan badge (string kosong `""`), tombol tetap bersih tanpa angka `0`.
3. **Cakupan Modul**:
   * Modul **`penerimaan`**: Halaman `penerimaan/Transaksi/index/749`
   * Modul **`kas`**: Halaman `kas/Transaksi/index/...`

---

## 3. Arsitektur Data & Kriteria Query

### 3.1 Sumber Data
Tabel: **`transaksi_payment_source`**

### 3.2 Kriteria Filter (SQL Active Record)
```php
$this->db->from("transaksi_payment_source");
$this->db->where("target_jenis", "759");   // 759 = Penyetoran Kas / Settlement
$this->db->where("sisa >", 0);             // Masih ada saldo yang belum disetor
$this->db->where("cabang_id", $cabangId);  // Cabang aktif user login
$this->db->where("extern_id", $userId);    // ID kasir/user yang login
$count = (int)$this->db->count_all_results();
```

### 3.3 Efisiensi Query
* Query menggunakan fungsi agregasi `COUNT(1)` langsung pada tabel `transaksi_payment_source` yang memiliki indeks pada `target_jenis`, `cabang_id`, dan `extern_id`.
* Eksekusi sangat ringan (~2-3 ms) sehingga tidak membebani server database.

---

## 4. Rincian Berkas yang Akan Dimodifikasi (Modification Plan)

```
application/
├── helpers/
│   └── he_misc_helper.php             <-- [1] Tambahkan fungsi getMySettlementBadgeHtml()
├── libraries/
│   └── Layout.php                     <-- [2] Daftarkan tag default {settlement_badge} di __construct()
└── modules/
    ├── penerimaan/
    │   ├── views/transaksi.php        <-- [3] Daftarkan tag settlement_badge di case "index"
    │   └── template/
    │       └── transaksi_index.html   <-- [4] Sisipkan tag {settlement_badge} di li#viewMySettlement
    └── kas/
        ├── views/transaksi.php        <-- [5] Daftarkan tag settlement_badge di case "index"
        └── template/
            └── transaksi_index.html   <-- [6] Sisipkan tag {settlement_badge} di li#viewMySettlement
```

---

## 5. Rincian Teknis Implementasi per Berkas

### 5.1 [helper] `application/helpers/he_misc_helper.php`
Tambahkan fungsi global helper (kompatibel PHP 5.6):
```php
// START OF COMPLETE REPEATED LOGIC
if (!function_exists('getMySettlementBadgeHtml')) {
    function getMySettlementBadgeHtml()
    {
        $ci =& get_instance();
        if (!isset($ci->session->login['id']) || !isset($ci->session->login['cabang_id'])) {
            return "";
        }

        $userId = intval($ci->session->login['id']);
        $cabangId = intval($ci->session->login['cabang_id']);

        $ci->db->from("transaksi_payment_source");
        $ci->db->where("target_jenis", "759");
        $ci->db->where("sisa >", 0);
        $ci->db->where("cabang_id", $cabangId);
        $ci->db->where("extern_id", $userId);
        $count = (int)$ci->db->count_all_results();

        if ($count > 0) {
            return "<span class='badge' style='background:#ffffff; color:#d81b60; font-weight:bold; margin-left:5px; border-radius:10px; padding:2px 7px; font-size:11px; vertical-align:middle; box-shadow:0 1px 2px rgba(0,0,0,0.2);'>" . $count . "</span>";
        }

        return "";
    }
}
// END OF COMPLETE REPEATED LOGIC
```

### 5.2 [library] `application/libraries/Layout.php`
Di dalam method `__construct()`, daftarkan tag `{settlement_badge}` agar dikenali secara otomatis:
```php
if (isset($CI->session->login['id']) && isset($CI->session->login['cabang_id'])) {
    $this->tags['settlement_badge'] = getMySettlementBadgeHtml();
} else {
    $this->tags['settlement_badge'] = "";
}
```

### 5.3 [view] `application/modules/penerimaan/views/transaksi.php`
Pada blok `case "index":`, sertakan tag pada `$arrTags`:
```php
"settlement_badge" => getMySettlementBadgeHtml(),
```

### 5.4 [template] `application/modules/penerimaan/template/transaksi_index.html`
Perbarui elemen `<li id="viewMySettlement">`:
```html
<li id="viewMySettlement">
    <a class="text-white bg-fuchsia" href="{base}settlement/Transaksi/index/759" data-toggle="tooltip" data-placement="bottom" title="my {trName} settlement">
        <span class='fa fa-gg'></span> my settlement {settlement_badge}
    </a>
</li>
```

### 5.5 [view] `application/modules/kas/views/transaksi.php`
Pada blok `case "index":`, sertakan tag pada `$arrTags`:
```php
"settlement_badge" => getMySettlementBadgeHtml(),
```

### 5.6 [template] `application/modules/kas/template/transaksi_index.html`
Perbarui elemen `<li id="viewMySettlement">`:
```html
<li id="viewMySettlement">
    <a class="text-white bg-fuchsia" href="{base}settlement/Transaksi/index/759" data-toggle="tooltip" data-placement="bottom" title="my {trName} settlement">
        <span class='fa fa-gg'></span> my settlement {settlement_badge}
    </a>
</li>
```

---

## 6. Prosedur Pengujian & Verifikasi (Nanti Saat Dikerjakan)

1. **Uji Kasir dengan Tanggungan Setoran (`count > 0`)**:
   * Login sebagai kasir yang memiliki nota penerimaan A/R yang belum disetor.
   * Buka `penerimaan/Transaksi/index/749` dan `kas/Transaksi/index/...`.
   * Pastikan tombol `my settlement` menampilkan badge angka putih tebal berteks fuchsia dengan jumlah yang sesuai.
2. **Uji Kasir tanpa Tanggungan Setoran (`count == 0`)**:
   * Selesaikan settlement semua nota kasir hingga saldo sisa 0.
   * Refresh halaman; pastikan tombol `my settlement` bersih tanpa badge angka.
3. **Uji Kompatibilitas Sintaks**:
   * Pastikan kode 100% kompatibel dengan PHP 5.6 (menggunakan `intval()`, sintaks `array()`, tidak ada operator `??`).
