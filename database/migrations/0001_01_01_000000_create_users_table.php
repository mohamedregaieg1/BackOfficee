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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('avatar_path')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('gender', ['female', 'male'])->default('male');
            $table->string('phone', 12)->nullable();
            $table->string('address')->nullable();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('company', ['adequat', 'procan'])->default('procan');
            $table->enum('role', ['admin', 'employee', 'hr','accountat'])->default('employee');
            $table->string('job_description');
            $table->date('start_date');
            $table->double('leave_balance', 8, 2)->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
