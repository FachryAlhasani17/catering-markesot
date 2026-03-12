<?php

namespace App\Services;

class QrisService
{
    /**
     * Generate dynamic QRIS string with specific nominal
     */
    public function generateDynamic(?string $qris, float $amount): string
    {
        if (empty($qris)) {
            return '';
        }

        // ubah static -> dynamic
        $qris = preg_replace('/010211/', '010212', $qris);

        // hapus CRC lama
        $qris = preg_replace('/6304....$/', '', $qris);

        // format nominal
        $amountStr = number_format($amount, 2, '.', '');

        // sisipkan tag 54 sebelum tag 58
        $qris = preg_replace('/5802ID/', 
            '54' . sprintf("%02d", strlen($amountStr)) . $amountStr . '5802ID', 
            $qris
        );

        // hitung CRC baru
        $crc = strtoupper(dechex($this->crc_ccitt($qris . '6304')));
        $crc = str_pad($crc, 4, '0', STR_PAD_LEFT);

        return $qris . '6304' . $crc;
    }

    private function crc_ccitt(string $data): int
    {
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

    /**
     * Generate QR Image URL
     */
    public function generateQrImage(string $qrisString): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrisString);
    }
}
