<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Taxpayer;
use Faker\Factory as Faker;

class TaxpayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('id_ID');

        for ($i = 1; $i <= 10; $i++) {
            $imagePath = public_path('images/sample_ktp.jpg'); // Pastikan ada gambar di folder ini
            $ktpPhotoBase64 = file_exists($imagePath) ? base64_encode(file_get_contents($imagePath)) : null;

            TaxPayer::create([
                'nik' => $faker->numerify('################'),
                'tku_id' => $faker->uuid(),
                'name' => $faker->name(),
                'project_id' => rand(1, 3), // Menggunakan angka acak antara 1 - 4
                'ktp_photo' => $ktpPhotoBase64, // Simpan dalam format Base64
                'status_ptkp' => $faker->randomElement(['K/0', 'TK/0', 'K/1']),
                'facility' => $faker->randomElement(['N/A', 'Special', 'Regular']),
            ]);
        }
    }
}
