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
        Schema::table('scraping_jobs', function (Blueprint $table) {
            $table->integer('limit')->unsigned()->default(100)->after('radius');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scraping_jobs', function (Blueprint $table) {
            $table->dropColumn('limit');
        });
    }
};
