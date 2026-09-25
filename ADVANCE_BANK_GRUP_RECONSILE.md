# Blueprint Arsitektur: Advance Bank Group Reconciliation
**File Identifier:** `ADVANCE_BANK_GRUP_RECONSILE.md`  
**Modul:** Settlement (`settlement/Transaksi/index/759` & `selectPaymentSrc`)  
**Status:** Selesai Diimplementasikan  
**Terakhir Diperbarui:** 2026-09-12  

---

## 1. Latar Belakang & Masalah (*Problem Statement*)

### 1.1 Masalah Tampilan UI Melebar
Pada halaman rekonsiliasi dan settlement transaksi, sistem menampilkan ringkasan rekening dalam bentuk *Card Widget* di atas tabel. Saat ini, setiap `cash_account` (yang bisa berupa EDC individual, QR Code, atau akun rekening tertentu) membuat kartu masing-masing:
- `MDRI DEBIT1640009779887`
- `MDRI KK 1640009779887`
- `QR MANDIRI CILEDUG`
- `BCA 3132 KK BCA`
- `BCA 3132 debit bank lain`
- `QR BCA ciledug`
- `tokped mandiri`
- `shopee bca`, dll.

Kondisi ini menyebabkan container card melebar terlalu panjang ke samping dan menyulitkan kasir untuk melihat gambaran umum penerimaan per induk bank (BCA, Mandiri, BNI, Tunai).

### 1.2 Mengapa Tidak Bisa Dipukul Rata Kolom `folders_nama`?
Tabel `bank` di database menggunakan relasi pohon bertingkat (*multi-level tree hierarchy*) hingga **3 tingkat**:

```
[Level 3: Channel / Mesin EDC / Payment Gateway]
  Contoh: ID 1177 (nama: "QR MANDIRI CILEDUG", jenis: "edc")
  └── folders = 1154, folders_nama = "1640009779887"  <-- (Ini NOMOR REKENING, BUKAN nama bank!)

[Level 2: Rekening Bank Penerimaan]
  Contoh: ID 1154 (nama: "1640009779887", jenis: "account_in")
  └── folders = 1002, folders_nama = "MANDIRI"        <-- (Di level ini baru nama INDUK BANK)

[Level 1: Induk Bank / Root Node]
  Contoh: ID 1002 (nama: "MANDIRI", jenis: "bank", folders = NULL)
```

**Temuan Lapangan:**
- Jika sistem hanya membaca `folders_nama` dari baris EDC (ID 1177), sistem akan mendapatkan `"1640009779887"` (Nomor Rekening), bukan `"MANDIRI"`.
- Jika ada EDC lain yang terhubung ke nomor rekening Mandiri yang berbeda, transaksi akan terpecah menjadi beberapa kartu nomor rekening, sehingga tujuan pengelompokan ke induk bank gagal tercapai.
- Untuk BCA, ID 1001 dan ID 1158 keduanya memiliki `nama = 'BCA'` dan `jenis = 'bank'`. Diperlukan normalisasi nama agar seluruh mutasi BCA tergabung ke dalam 1 grup BCA.

---

## 2. Struktur Data Tabel `bank`

Berdasarkan analisis skema database:
- **`id`**: Primary Key (int)
- **`nama`**: Label channel / nomor rekening / nama bank
- **`folders`**: Foreign key ke `bank.id` milik parent
- **`folders_nama`**: Cache nama parent
- **`alias`**: Keterangan tambahan (e.g. "PT. EVEREST ELECTRONIC", nama pemegang kartu)
- **`jenis`**: Klasifikasi tipe akun:
  - `bank`: Induk bank tingkat 1 (BCA, MANDIRI, BNI, dll.)
  - `account_in`: Akun rekening bank penerimaan (berisi nomor rekening)
  - `edc`: Channel EDC / QR / POS Payment
  - `creditcard`: Channel Kartu Kredit khusus
  - `account_cash`: Akun kasir tunai (e.g. ID 888, 999, 1000)
  - `pettycash`: Akun kas kecil

---

## 3. Desain Solusi Teknis: Algoritma Multi-Level Tree Traversal

### 3.1 Pemuatan Memory Map (Satu Kali Query Cepat)
Karena jumlah baris tabel `bank` relatif sedikit (master data internal), seluruh data akun aktif dimuat satu kali saja ke dalam memory map associative array:
```php
$allBanks = $this->db->select("id, nama, folders, folders_nama, alias, jenis")
                     ->get_where("bank", array("trash" => "0"))
                     ->result_array();

$bankMap = array();
foreach ($allBanks as $b) {
    $bankMap[$b['id']] = $b;
}
```

### 3.2 Fungsi Penelusuran Pohon ke Induk (*Tree Traversal Resolver*)
Untuk setiap `cash_account` pada `$items`, lakukan penelusuran berulang ke atas (*climb up parent chain*) sampai menemukan node dengan `jenis == 'bank'` atau node paling atas (`folders` kosong / 0):

```php
function resolveRootBank($cashAccountId, $bankMap) {
    $result = array(
        'root_id'       => $cashAccountId,
        'root_name'     => 'LAINNYA',
        'rek_nomor'     => '',
        'channel_name'  => '',
        'is_tunai'      => false
    );

    if (!isset($bankMap[$cashAccountId])) {
        return $result;
    }

    $currId = $cashAccountId;
    $depth = 0; // Proteksi maksimal 5 iterasi untuk cegah infinite circular loop

    while (isset($bankMap[$currId]) && $depth < 5) {
        $node = $bankMap[$currId];

        // Catat nama channel EDC / Gateway
        if ($node['jenis'] == 'edc' || $node['jenis'] == 'creditcard') {
            if (empty($result['channel_name'])) {
                $result['channel_name'] = $node['nama'];
            }
        }
        // Catat nomor rekening penerimaan
        elseif ($node['jenis'] == 'account_in') {
            if (empty($result['rek_nomor'])) {
                $result['rek_nomor'] = $node['nama'];
            }
        }
        // Kas Tunai
        elseif ($node['jenis'] == 'account_cash' || stripos($node['nama'], 'tunai') !== false) {
            $result['is_tunai'] = true;
            $result['root_name'] = 'TUNAI';
            $result['root_id'] = 1000;
            return $result;
        }

        // Cek apakah node ini adalah Induk Bank (Root)
        if ($node['jenis'] == 'bank' || empty($node['folders']) || $node['folders'] == '0') {
            $rootName = strtoupper(trim($node['nama']));
            // Normalisasi varian penamaan bank
            if (stripos($rootName, 'BCA') !== false) {
                $rootName = 'BCA';
            } elseif (stripos($rootName, 'MANDIRI') !== false) {
                $rootName = 'MANDIRI';
            } elseif (stripos($rootName, 'BNI') !== false) {
                $rootName = 'BNI';
            } elseif (stripos($rootName, 'BRI') !== false) {
                $rootName = 'BRI';
            }

            $result['root_name'] = $rootName;
            $result['root_id'] = $node['id'];
            break;
        }

        // Naik ke tingkat parent berikutnya
        $currId = $node['folders'];
        $depth++;
    }

    return $result;
}
```

---

## 4. Desain Tampilan Antarmuka (UX/UI)

### 4.1 Ringkasan Baris Atas (*Card Grouping*)
Hanya menampilkan **3 s.d. 5 Kartu Utama**:
1. **`SEMUA REKENING`**: Total akumulasi seluruh transaksi (Nota dipilih / Total nota).
2. **`KAS TUNAI`**: Total nominal fisik tunai di kasir, tombol *[Setor Kas ke Bank (757)]*.
3. **`BCA`**: Akumulasi seluruh EDC BCA, QR BCA, dan Transfer Rekening BCA.
4. **`MANDIRI`**: Akumulasi seluruh EDC Mandiri, QR Mandiri, Tokopedia Mandiri, dll.
5. **`BANK LAIN / BNI`**: Akumulasi bank mitra lainnya (jika ada data transaksi).

### 4.2 Modal Rekonsiliasi Mutasi Bank per Induk
- Ketika kasir mengklik tombol **[Cocokkan Mutasi Rekening Bank]** pada card **MANDIRI**:
  - Modal terbuka dengan judul: `Rekonsiliasi Mutasi Bank: MANDIRI`
  - Tabel modal menyajikan seluruh transaksi Mandiri dari berbagai channel.
  - Untuk mempermudah kasir membandingkan dengan rekening koran, ditambahkan kolom/badge **"Channel / Sub-Akun"** (misal: `<span class='badge bg-navy'>QR Ciledug</span>` atau `<span class='badge bg-purple'>MDRI DEBIT</span>`).

---

## 5. Rencana File yang Akan Diubah pada Tahap Implementasi

1. **`application/modules/settlement/views/transaksi.php`**:
   - Memasukkan fungsi resolusi hierarki pohon bank (`resolveRootBank`).
   - Mengubah loop pengelompokan `$cardsAccData` dari berbasis `cash_account` individu ke `root_bank_slug` (`bca`, `mandiri`, `tunai`, dll.).
   - Menyimpan daftar `transaksi_ids` dan detail `items` di bawah masing-masing grup bank induk.
   - Memperbarui trigger modal rekonsiliasi agar membaca data transaksi per grup bank induk.
   
2. **`application/modules/settlement/controllers/Transaksi.php`**:
   - Mengirimkan master tabel `bank` (atau helper lookup) jika diputuskan diproses di level controller.

---

## 6. Checklist Verifikasi Saat Dilanjutkan

- [ ] Pastikan transaksi dari ID 1177 (QR Mandiri) dan ID 1166 (Mandiri Debit) masuk ke dalam kartu **MANDIRI**.
- [ ] Pastikan transaksi dari ID 1168 (EDC BCA) dan transfer BCA masuk ke dalam kartu **BCA**.
- [ ] Pastikan transaksi Tunai (ID 888, 999, 1000) masuk ke dalam kartu **TUNAI**.
- [ ] Pastikan modal rekonsiliasi per induk bank menampilkan semua nota yang sesuai dengan sub-channel masing-masing.
- [ ] Pastikan kompatibilitas PHP 5.6 terjaga penuh (tanpa `??`, short array `[]`, atau closure arrow function).
