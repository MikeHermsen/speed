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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('parent_email')->nullable();
            $table->string('parent_phone')->nullable();
            $table->boolean('notify_student_email')->default(true);
            $table->boolean('notify_parent_email')->default(false);
            $table->boolean('notify_student_phone')->default(true);
            $table->boolean('notify_parent_phone')->default(false);
            $table->string('package')->nullable();
            $table->string('vehicle')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
