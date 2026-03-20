<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE scraping_jobs MODIFY COLUMN status ENUM('pending', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE scraping_jobs MODIFY COLUMN status ENUM('pending', 'running', 'completed', 'failed') DEFAULT 'pending'");
    }
};
