<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuDummySeeder extends Seeder
{
    public function run(): void
    {
        $foods = [
            // Makanan Utama (category_id: 1)
            ['category_id' => 1, 'name' => 'Nasi Goreng Spesial',   'price' => 15000, 'description' => 'Nasi goreng dengan telur mata sapi, ayam suwir, dan kerupuk renyah.', 'notes' => '{"emoji":"🍳","tags":["Gurih","Pedas"]}'],
            ['category_id' => 1, 'name' => 'Mie Goreng Jawa',       'price' => 13000, 'description' => 'Mie goreng khas Jawa dengan bumbu kecap manis dan sayuran segar.', 'notes' => '{"emoji":"🍜","tags":["Manis","Gurih"]}'],
            ['category_id' => 1, 'name' => 'Ayam Geprek Sambal',    'price' => 18000, 'description' => 'Ayam geprek crispy dengan sambal bawang merah yang pedas menggoda.', 'notes' => '{"emoji":"🍗","tags":["Pedas","Crispy"]}'],
            ['category_id' => 1, 'name' => 'Soto Ayam Lamongan',    'price' => 14000, 'description' => 'Kuah bening kaya rempah dengan suwiran ayam dan bihun.', 'notes' => '{"emoji":"🥣","tags":["Hangat","Segar"]}'],
            ['category_id' => 1, 'name' => 'Nasi Rendang Padang',   'price' => 20000, 'description' => 'Rendang sapi empuk dengan santan kental dan bumbu rempah pilihan.', 'notes' => '{"emoji":"🥩","tags":["Gurih","Premium"]}'],
            ['category_id' => 1, 'name' => 'Bakso Urat Jumbo',      'price' => 16000, 'description' => 'Bakso daging sapi besar isi urat kenyal dalam kuah kaldu hangat.', 'notes' => '{"emoji":"🍲","tags":["Hangat","Kenyang"]}'],
            ['category_id' => 1, 'name' => 'Rawon Surabaya',        'price' => 19000, 'description' => 'Sup daging sapi hitam khas Surabaya dengan kluwek asli.', 'notes' => '{"emoji":"🥘","tags":["Tradisional","Hangat"]}'],

            // Makanan Ringan (category_id: 2)
            ['category_id' => 2, 'name' => 'Tahu Crispy Pedas',     'price' => 8000,  'description' => 'Tahu goreng garing dengan taburan cabai dan bumbu rempah.', 'notes' => '{"emoji":"🧈","tags":["Crispy","Pedas"]}'],
            ['category_id' => 2, 'name' => 'Pisang Goreng Keju',    'price' => 10000, 'description' => 'Pisang goreng renyah dengan lelehan keju mozzarella.', 'notes' => '{"emoji":"🍌","tags":["Manis","Keju"]}'],
            ['category_id' => 2, 'name' => 'Dimsum Ayam',           'price' => 12000, 'description' => 'Dimsum kukus isi ayam cincang dengan saus sambal kecap.', 'notes' => '{"emoji":"🥟","tags":["Lembut","Gurih"]}'],
        ];

        $drinks = [
            // Minuman (category_id: 3)
            ['category_id' => 3, 'name' => 'Es Teh Manis',          'price' => 5000,  'description' => 'Teh manis dingin segar khas warung kampus.', 'notes' => '{"emoji":"🍵","tags":["Segar","Murah"]}'],
            ['category_id' => 3, 'name' => 'Es Jeruk Peras',        'price' => 7000,  'description' => 'Jeruk peras segar langsung dari buah asli.', 'notes' => '{"emoji":"🍊","tags":["Segar","Vitamin C"]}'],
            ['category_id' => 3, 'name' => 'Kopi Susu Gula Aren',   'price' => 12000, 'description' => 'Espresso shot dengan susu segar dan gula aren pilihan.', 'notes' => '{"emoji":"☕","tags":["Kekinian","Manis"]}'],
            ['category_id' => 3, 'name' => 'Jus Alpukat',           'price' => 10000, 'description' => 'Alpukat blended lembut dengan susu coklat dan madu.', 'notes' => '{"emoji":"🥑","tags":["Creamy","Sehat"]}'],
            ['category_id' => 3, 'name' => 'Es Cendol Dawet',       'price' => 8000,  'description' => 'Cendol pandan dengan santan kelapa dan gula merah.', 'notes' => '{"emoji":"🧋","tags":["Tradisional","Manis"]}'],
            ['category_id' => 3, 'name' => 'Lemon Tea',             'price' => 8000,  'description' => 'Teh hitam segar dengan perasan lemon dan madu alami.', 'notes' => '{"emoji":"🍋","tags":["Segar","Asam Manis"]}'],
            ['category_id' => 3, 'name' => 'Milkshake Coklat',      'price' => 13000, 'description' => 'Susu coklat blended dengan es krim vanilla premium.', 'notes' => '{"emoji":"🥛","tags":["Creamy","Manis"]}'],
            ['category_id' => 3, 'name' => 'Matcha Latte',          'price' => 14000, 'description' => 'Green tea matcha premium dengan susu segar.', 'notes' => '{"emoji":"🍃","tags":["Kekinian","Sehat"]}'],
            ['category_id' => 3, 'name' => 'Es Kelapa Muda',        'price' => 9000,  'description' => 'Air kelapa muda asli ditambah daging kelapa dan es batu.', 'notes' => '{"emoji":"🥥","tags":["Alami","Segar"]}'],
            ['category_id' => 3, 'name' => 'Thai Tea',              'price' => 10000, 'description' => 'Teh Thailand oranye creamy dengan susu kental manis.', 'notes' => '{"emoji":"🧡","tags":["Kekinian","Manis"]}'],
        ];

        foreach (array_merge($foods, $drinks) as $item) {
            MenuItem::updateOrCreate(
                ['name' => $item['name']],
                [
                    'category_id'   => $item['category_id'],
                    'price'         => $item['price'],
                    'description'   => $item['description'],
                    'notes'         => $item['notes'],
                    'unit'          => 'porsi',
                    'is_available'  => true,
                    'is_featured'   => false,
                    'min_order_qty' => 1,
                ]
            );
        }
    }
}
