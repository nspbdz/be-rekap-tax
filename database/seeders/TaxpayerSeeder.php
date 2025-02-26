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
            TaxPayer::create([
                'npwp' => $faker->numerify('################'),
                'nik' => $faker->numerify('################'),
                'tku_id' => $faker->uuid(),
                'name' => $faker->name(),
                'project_id' => rand(1, 3), // Menggunakan angka acak antara 1 - 4
                'ktp_photo' => $faker->imageUrl(200, 300, 'people'),
                'status_ptkp' => $faker->randomElement(['K/0', 'TK/0', 'K/1']),
                'facility' => $faker->randomElement(['N/A', 'Special', 'Regular']),
            ]);
        }
    }
}
