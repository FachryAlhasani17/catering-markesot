<?php

namespace App\Services;

class QrisService
{
    /**
     * Konversi static QRIS menjadi dynamic dengan nominal tertentu.
     *
     * Langkah:
     *  1. trim() — hapus whitespace/newline dari copy-paste
     *  2. Validasi: harus static QRIS (010211)
     *  3. Ubah indikator: 010211 → 010212
     *  4. Hapus CRC lama (6304 + 4 hex char di ujung)
     *  5. Sisipkan tag 54 (Transaction Amount) sebelum tag 5802 (Country Code)
     *  6. Hitung CRC baru (CRC-16/CCITT-FALSE)
     */
    public function generateDynamic(?string $qris, float $amount): string
    {
        if (empty($qris)) {
            return '';
        }

        // 1. Trim whitespace — penyebab paling umum CRC gagal saat copy-paste
        $qris = trim($qris);

        // 2. Ubah static (11) → dynamic (12)
        //    Kalau sudah 12, tetap lanjut — tapi tag 54 mungkin sudah ada
        $qris = str_replace('010211', '010212', $qris);

        // 3. Hapus CRC lama — regex spesifik: hanya 4 hex char di akhir string
        $qris = preg_replace('/6304[0-9A-Fa-f]{4}$/', '', $qris);

        // 4. Format nominal: angka desimal, separator titik
        //    Contoh: 50000 → "50000.00"
        $amountStr = number_format($amount, 2, '.', '');
        $tag54     = '54' . sprintf('%02d', strlen($amountStr)) . $amountStr;

        // 5. Sisipkan tag 54 tepat sebelum tag 5802 (Country Code Indonesia)
        //    Posisi ini sudah sesuai urutan tag EMVCo: 53 (currency) → 54 (amount) → 58 (country)
        if (str_contains($qris, '5802ID')) {
            $qris = str_replace('5802ID', $tag54 . '5802ID', $qris);
        }

        // 6. Hitung CRC baru (CRC-16/CCITT-FALSE)
        $crc = strtoupper(dechex($this->crc_ccitt($qris . '6304')));
        $crc = str_pad($crc, 4, '0', STR_PAD_LEFT);

        return $qris . '6304' . $crc;
    }

    /**
     * CRC-16/CCITT-FALSE sesuai standar EMVCo QRIS.
     *   Init   : 0xFFFF
     *   Poly   : 0x1021
     *   XorOut : 0x0000
     *   RefIn  : false (MSB first)
     *   RefOut : false
     */
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
     * Generate URL gambar QR Code via api.qrserver.com.
     *
     * PENTING: Gunakan rawurlencode() — bukan urlencode().
     * urlencode() mengubah spasi menjadi '+', yang bisa masuk ke QR sebagai
     * karakter '+' literal sehingga QRIS string di dalam QR menjadi invalid.
     * rawurlencode() mengubah spasi menjadi '%20' yang selalu aman.
     *
     * ecc=M  : error correction 15% — cukup untuk frame overlay tetap bisa di-scan
     * size=500: cukup besar untuk kamera mobile banking
     */
    public function generateQrImage(string $qrisString): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&ecc=M&data='
            . rawurlencode($qrisString);
    }
}
