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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['examen', 'les', 'proefles', 'ziek'])->default('les');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('vehicle')->nullable();
            $table->string('package')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('parent_email')->nullable();
            $table->string('parent_phone')->nullable();
            $table->boolean('notify_student_email')->default(true);
            $table->boolean('notify_parent_email')->default(false);
            $table->boolean('notify_student_phone')->default(true);
            $table->boolean('notify_parent_phone')->default(false);
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
