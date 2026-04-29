<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    /**
     * Ambil value setting berdasarkan key, dengan cache.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting_{$key}", now()->addHours(24), function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set / update value setting dan hapus cache lama.
     */
    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("setting_{$key}");
    }

    /**
     * Ambil semua setting sekaligus dalam satu query (untuk form settings).
     */
    public function all(): \Illuminate\Support\Collection
    {
        return Setting::all()->keyBy('key');
    }

    /**
     * Ambil persentase DP (default 50%).
     */
    public function dpPercentage(): float
    {
        return (float) $this->get('dp_percentage', 50);
    }

    /**
     * Ambil jumlah menu best seller.
     */
    public function bestSellerCount(): int
    {
        return (int) $this->get('best_seller_count', 1);
    }

    /**
     * Ambil informasi rekening bank.
     */
    public function bankInfo(): array
    {
        return [
            'bank_name'      => $this->get('bank_name', ''),
            'account_number' => $this->get('account_number', ''),
            'account_name'   => $this->get('account_name', ''),
        ];
    }
}
