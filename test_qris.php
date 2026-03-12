<?php

function crc_ccitt($data) {
    $crc = 0xFFFF;
    for ($i = 0; $i < strlen($data); $i++) {
        $crc ^= (ord($data[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if ($crc & 0x8000) {
                $crc = ($crc << 1) ^ 0x1021;
            } else {
                $crc <<= 1;
            }
            $crc &= 0xFFFF;
        }
    }
    return $crc;
}

function generateDynamicQris($qris, $amount)
{
    // ubah static -> dynamic
    $qris = preg_replace('/010211/', '010212', $qris);

    // hapus CRC lama
    $qris = preg_replace('/6304....$/', '', $qris);

    // format nominal
    $amount = number_format($amount, 2, '.', '');

    // sisipkan tag 54 sebelum tag 58
    $qris = preg_replace('/5802ID/', 
        '54' . sprintf("%02d", strlen($amount)) . $amount . '5802ID', 
        $qris
    );

    // hitung CRC baru
    $crc = strtoupper(dechex(crc_ccitt($qris . '6304')));
    $crc = str_pad($crc, 4, '0', STR_PAD_LEFT);

    return $qris . '6304' . $crc;
}

$rawQris = '00020101021126670014ID.CO.QRIS.WWW0118936009153002613149021415300261314930303UMI51440014ID.CO.QRIS.WWW0215ID10200210086810303UMI5204581253033605802ID5918Markesot Kantin UNEJ6006Jember6105681216304CA1F';
echo generateDynamicQris($rawQris, 100000);
