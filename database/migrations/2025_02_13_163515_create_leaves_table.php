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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->double('leave_days_requested')->check('leave_days_requested >= 0');
            $table->double('effective_leave_days')->default(0)->check('effective_leave_days >= 0');
            $table->enum('reason', ['vacation', 'travel_leave', 'paternity_leave', 'maternity_leave', 'sick_leave', 'other']);
            $table->string('other_reason')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
