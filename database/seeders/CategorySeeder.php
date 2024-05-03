<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Fashion dan Busana',
            'Elektronik dan Gadget',
            'Perlengkapan Rumah Tangga',
            'Kesehatan dan Kecantikan',
            'Makanan dan Minuman',
            'Mainan dan Hobi',
            'Perlengkapan Bayi dan Anak',
            'Buku dan Alat Tulis',
            'Olahraga dan Outdoor',
            'Perhiasan dan Aksesoris',
            'Barang Antik dan Koleksi',
            'Barang Elektronik Rumah Tangga',
            'Alat Musik dan Perlengkapan Studio',
            'Peralatan dan Perlengkapan Otomotif',
            'Barang-barang Kerajinan Tangan dan DIY',
            'Lainnya',
        ];

        $data = [];
        foreach ($categories as $category) {
            $data[] = [
                'name' => $category,
                'description' => $category,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('categories')->insert($data);
    }
}
