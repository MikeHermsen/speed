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
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('has_guardian')->default(false)->after('parent_phone');
            $table->string('guardian_email')->nullable()->after('has_guardian');
            $table->string('guardian_phone')->nullable()->after('guardian_email');
            $table->boolean('notify_guardian_email')->default(false)->after('guardian_phone');
            $table->boolean('notify_guardian_phone')->default(false)->after('notify_guardian_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'has_guardian',
                'guardian_email',
                'guardian_phone',
                'notify_guardian_email',
                'notify_guardian_phone',
            ]);
        });
    }
};
