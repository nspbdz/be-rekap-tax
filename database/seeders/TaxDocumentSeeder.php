<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\TaxDocument;

class TaxDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        for ($i = 1; $i <= 5; $i++) {
            TaxDocument::create([
                'document_type' => $faker->randomElement(['Announcement', 'Invoice', 'Tax Report']),
                'document_number' => strtoupper($faker->bothify('ABC###')),
                'document_date' => $faker->date(),
            ]);
        }
    }
}
