<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraping_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->string('location');
            $table->unsignedInteger('radius')->default(25);
            $table->string('source')->default('yelp');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->unsignedInteger('results_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraping_jobs');
    }
};
