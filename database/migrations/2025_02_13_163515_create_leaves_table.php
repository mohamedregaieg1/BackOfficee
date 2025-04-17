<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('fixed_leaves', function (Blueprint $table) {
            $table->id();
            $table->string('leave_type', 100)->unique();
            $table->integer('max_days')->default(0);
            $table->timestamps();
        });

        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->enum('leave_type', ['paternity_leave', 'maternity_leave', 'sick_leave','personal_leave']);
            $table->string('other_type')->nullable();
            $table->double('leave_days_requested');
            $table->double('effective_leave_days')->default(0);
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['approved', 'rejected', 'on_hold'])->default('on_hold');
            $table->timestamps();
        });

        Schema::create('leaves_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->double('leave_day_limit');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropForeign(['leave_type']);
        });
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('fixed_leaves');
        Schema::dropIfExists('leaves_balances');
    }
};
