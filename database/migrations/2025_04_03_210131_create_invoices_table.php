<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['facture', 'devis','facture_avoir','facture_avoir_partiel']);
            $table->date('creation_date');
            $table->string('number')->unique();
            $table->enum('additional_date_type', ['Date of sale', 'Expiry date', 'Withdrawal date until'])->nullable();
            $table->date('additional_date')->nullable();
            $table->enum('company_name', ['procan', 'adequate']);
            $table->foreignId('company_id')->constrained('companies')->default(1);
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');;
            $table->enum('payment_mode', [
                'bank transfer',
                'credit card',
                'cash',
                'paypal',
                'cheque',
                'other'
            ])->nullable();

            $table->string('due_date')->nullable();
            $table->enum('payment_status', [
                'paid',
                'partially paid',
                'unpaid'
            ])->default('paid')->nullable();
            $table->double('amount_paid')->nullable();
            $table->double('unpaid_amount')->nullable();
            $table->double('total_ttc')->nullable();
            $table->double('total_tva')->nullable();
            $table->double('total_ht')->nullable();
            $table->unsignedBigInteger('original_invoice_id')->nullable(); // Peut être nul si c'est une facture originale
        $table->foreign('original_invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
