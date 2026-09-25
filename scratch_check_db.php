<?php
$db = new mysqli('192.168.5.14', 'app_master', 'sparrowhawk', 'run_everest_modul');
$trID = 949295;
$masterID = 949199;

echo "=== CHECKING TRANSAKSI 949199 & 949295 ===\n";
$resReg2 = $db->query("SELECT items FROM transaksi_data_registry WHERE transaksi_id = $trID LIMIT 1");
if ($resReg2 && $reg2 = $resReg2->fetch_assoc()) {
    $items2 = unserialize(base64_decode($reg2['items']));
    echo "=== ITEMS IN 949295 REGISTRY (" . count($items2) . ") ===\n";
    foreach ($items2 as $k => $it) {
        echo "KEY $k:\n";
        foreach ($it as $f => $v) {
            if (!is_array($v)) echo "  $f => $v\n";
        }
        break;
    }
} else {
    echo "NO REGISTRY FOR 949295\n";
}

echo "=== CHECKING TRANSAKSI_EXTSTEP ===\n";
$resExt = $db->query("SELECT * FROM transaksi_extstep WHERE transaksi_id IN ($masterID, $trID)");
while ($resExt && $row = $resExt->fetch_assoc()) {
    print_r($row);
}

echo "=== CHECKING TRANSAKSI_SIGN ===\n";
$resSign = $db->query("SELECT * FROM transaksi_sign WHERE id_master IN ($masterID, $trID)");
if ($resSign) {
    while ($row = $resSign->fetch_assoc()) {
        print_r($row);
    }
}
