<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tax_payers', function (Blueprint $table) {
            $table->id();
            $table->string('npwp', 20)->unique();
            $table->string('nik', 16)->unique();
            $table->string('tku_id', 105)->unique();
            $table->string('name', 100);
            $table->string('ktp_photo')->nullable(); // URL Foto KTP
            $table->string('status_ptkp', 10);
            $table->string('facility', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tax_payers');

    }
};
