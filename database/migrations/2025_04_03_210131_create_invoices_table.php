<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['facture', 'devis']);
            $table->date('creation_date');
            $table->string('number')->unique();
            $table->enum('additional_date_type', ['Date of sale', 'Expiry date', 'Withdrawal date until'])->nullable();
            $table->date('additional_date')->nullable();
            $table->enum('company_name', ['procan', 'adequate']);
            $table->foreignId('company_id')->constrained('companies')->default(1);
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('clients');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('invoices');
    }
};