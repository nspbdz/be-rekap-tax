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
        Schema::create('tax_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('taxpayer_id');
            $table->unsignedBigInteger('tax_cutter_id');
            $table->unsignedBigInteger('tax_document_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();

            $table->integer('tax_period'); // 1-12
            $table->year('tax_year');
            $table->string('tax_object_code', 15);
            $table->decimal('income', 15, 2);
            $table->decimal('deemed', 15, 2)->default(0);
            $table->decimal('rate', 5, 2);
            $table->date('deduction_date');

            $table->timestamps();

            // Foreign Keys
            $table->foreign('taxpayer_id')->references('id')->on('tax_payers')->onDelete('cascade');
            $table->foreign('tax_cutter_id')->references('id')->on('tax_cutters')->onDelete('cascade');
            $table->foreign('tax_document_id')->references('id')->on('tax_documents')->onDelete('set null');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tax_transactions');

    }
};
