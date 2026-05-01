<?php
/**
 * QRIS Final Debug - verifikasi rawurlencode vs urlencode
 */
function crc16($data) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= (ord($data[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) $crc = ($crc << 1) ^ 0x1021;
            else                $crc <<= 1;
            $crc &= 0xFFFF;
        }
    }
    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

function verifyCRC($qris) {
    $crcVal = substr($qris, -4);
    $payload = substr($qris, 0, -4);
    $computed = crc16($payload);
    return ['valid' => $computed === strtoupper($crcVal), 'computed' => $computed, 'inString' => strtoupper($crcVal)];
}

function generateDynamic($qris, $amount) {
    $qris = trim($qris);
    $qris = str_replace('010211', '010212', $qris);
    $qris = preg_replace('/6304[0-9A-Fa-f]{4}$/', '', $qris);
    $amountStr = number_format($amount, 2, '.', '');
    $tag54 = '54' . sprintf('%02d', strlen($amountStr)) . $amountStr;
    if (str_contains($qris, '5802ID')) {
        $qris = str_replace('5802ID', $tag54 . '5802ID', $qris);
    }
    $crc = strtoupper(dechex(crc16_int($qris . '6304')));
    $crc = str_pad($crc, 4, '0', STR_PAD_LEFT);
    return $qris . '6304' . $crc;
}

function crc16_int($data) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= (ord($data[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) $crc = ($crc << 1) ^ 0x1021;
            else                $crc <<= 1;
            $crc &= 0xFFFF;
        }
    }
    return $crc;
}

$rawQris = '00020101021126670014ID.CO.QRIS.WWW0118936009153002613149021415300261314930303UMI51440014ID.CO.QRIS.WWW0215ID10200210086810303UMI5204581253033605802ID5918Markesot Kantin UNEJ6006Jember6105681216304CA1F';
$amount  = 50000; // contoh DP

echo "=== QRIS Final Debug ===\n\n";

$dynamic = generateDynamic($rawQris, $amount);
echo "Dynamic QRIS:\n$dynamic\n\n";

$v = verifyCRC($dynamic);
echo "CRC Valid: " . ($v['valid'] ? "YES ✓" : "NO ✗") . "  (computed: {$v['computed']}, in-string: {$v['inString']})\n\n";

echo "=== URL Encoding Comparison ===\n";
$urlEncode    = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&ecc=M&data=' . urlencode($dynamic);
$rawUrlEncode = 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&ecc=M&data=' . rawurlencode($dynamic);

// Check apakah ada '+' di urlencode (spasi → '+')
$hasPlus = str_contains($urlEncode, '+');
echo "urlencode() → mengandung '+': " . ($hasPlus ? "YA ✗ (spasi jadi '+', bisa rusak QRIS)" : "TIDAK") . "\n";
echo "rawurlencode() → mengandung '+': " . (str_contains($rawUrlEncode, '+') ? "YA" : "TIDAK ✓ (spasi jadi '%20')") . "\n\n";

echo "URL dengan rawurlencode (CORRECT):\n$rawUrlEncode\n\n";

echo "=== Instructi untuk scan debug ===\n";
echo "1. Buka browser, paste URL di atas\n";
echo "2. Scan QR code yang tampil dengan e-wallet / m-banking\n";
echo "3. Jika gagal, copy dynamic QRIS string ke:\n";
echo "   https://www.qrserver.com/qr-code/ → dan decode QR\n";
echo "   lalu paste QRIS string ke validator online\n\n";
echo "Dynamic QRIS string untuk di-test:\n$dynamic\n";
