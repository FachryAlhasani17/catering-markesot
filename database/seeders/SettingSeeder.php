<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // DP
            [
                'key'         => 'dp_percentage',
                'value'       => '50',
                'type'        => 'number',
                'label'       => 'Persentase DP (%)',
                'description' => 'Persentase Down Payment yang harus dibayar saat pemesanan',
            ],
            // Info Perusahaan
            [
                'key'         => 'company_name',
                'value'       => 'Catering Markesot',
                'type'        => 'text',
                'label'       => 'Nama Perusahaan',
                'description' => 'Nama catering yang ditampilkan di sistem',
            ],
            [
                'key'         => 'company_phone',
                'value'       => '081234567890',
                'type'        => 'text',
                'label'       => 'No. Telepon',
                'description' => 'Nomor telepon / WhatsApp yang bisa dihubungi',
            ],
            [
                'key'         => 'company_address',
                'value'       => 'Jl. Contoh No. 1, Kota',
                'type'        => 'text',
                'label'       => 'Alamat',
                'description' => 'Alamat lengkap perusahaan catering',
            ],
            // Rekening Bank
            [
                'key'         => 'bank_name',
                'value'       => 'BCA',
                'type'        => 'text',
                'label'       => 'Nama Bank',
                'description' => 'Nama bank untuk pembayaran transfer',
            ],
            [
                'key'         => 'account_number',
                'value'       => '1234567890',
                'type'        => 'text',
                'label'       => 'Nomor Rekening',
                'description' => 'Nomor rekening bank tujuan transfer',
            ],
            [
                'key'         => 'account_name',
                'value'       => 'Markesot Catering',
                'type'        => 'text',
                'label'       => 'Nama Pemilik Rekening',
                'description' => 'Nama pemilik rekening bank',
            ],
            // QR Payment
            [
                'key'         => 'qr_payment_image',
                'value'       => '',
                'type'        => 'image',
                'label'       => 'QR Code Pembayaran',
                'description' => 'Gambar QR Code untuk pembayaran (QRIS, dll)',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
