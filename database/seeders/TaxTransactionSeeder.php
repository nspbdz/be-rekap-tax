<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TaxTransaction;
use App\Models\Taxpayer;
use App\Models\TaxCutter;
use App\Models\TaxDocument;
use App\Models\Project;
use Faker\Factory as Faker;

class TaxTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        $taxpayers = Taxpayer::all();
        $taxCutters = TaxCutter::all();
        $taxDocuments = TaxDocument::all();
        $projects = Project::all();

        foreach ($taxpayers as $taxpayer) {
            TaxTransaction::create([
                'taxpayer_id' => $taxpayer->id,
                'tax_cutter_id' => $taxCutters->random()->id,
                'tax_document_id' => $taxDocuments->random()->id ?? null,
                'project_id' => $projects->random()->id ?? null,
                'tax_period' => $faker->numberBetween(1, 12),
                'tax_year' => $faker->year(),
                'tax_object_code' => $faker->bothify('21-###-##'),
                'income' => $faker->randomFloat(2, 500000, 10000000),
                'deemed' => $faker->randomFloat(2, 50, 500),
                'rate' => $faker->randomFloat(2, 0.5, 5),
                'deduction_date' => $faker->date(),
            ]);
        }
    }
}
