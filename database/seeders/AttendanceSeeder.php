<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\Taxpayer;
use Faker\Factory as Faker;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('id_ID');

        $taxpayers = Taxpayer::all();

        foreach ($taxpayers as $taxpayer) {
            for ($i = 0; $i < 30; $i++) {
                Attendance::create([
                    'taxpayer_id' => $taxpayer->id,
                    'project_id' => rand(1, 3), // Menggunakan angka acak antara 1 - 4
                    'attendance_date' => now()->subDays($i),
                    'status' => $faker->randomElement(['1', '2', '3']),
                ]);
            }
        }
    }
}
