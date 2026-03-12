<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Makanan Utama',  'description' => 'Hidangan utama seperti nasi, lauk pauk, dan sajian pokok',       'sort_order' => 1],
            ['name' => 'Makanan Ringan', 'description' => 'Snack, camilan, dan kudapan pelengkap',                           'sort_order' => 2],
            ['name' => 'Minuman',        'description' => 'Berbagai pilihan minuman segar dan hangat',                       'sort_order' => 3],
            ['name' => 'Dessert',        'description' => 'Hidangan penutup seperti kue, puding, dan buah-buahan',           'sort_order' => 4],
            ['name' => 'Paket Catering', 'description' => 'Paket lengkap catering untuk berbagai acara dan jumlah porsi',   'sort_order' => 5],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                array_merge($cat, ['is_active' => true])
            );
        }
    }
}
