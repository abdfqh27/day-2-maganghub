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
        Schema::table('kak_submissions', function (Blueprint $table) {
            $table->decimal('total_anggaran', 15, 2)->nullable()->after('output_format')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kak_submissions', function (Blueprint $table) {
            $table->dropColumn('total_anggaran');
        });
    }
};
