<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->enum('name', ['Adequate', 'Procan'])->default('Procan');
            $table->string('image_path')->nullable();
            $table->double('tva_number')->nullable();
            $table->text('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->enum('country', ['France', 'Tunisia'])->default('Tunisia');
            $table->string('rib_bank')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('phone_number', 15)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
