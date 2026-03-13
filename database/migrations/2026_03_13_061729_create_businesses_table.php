<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scraping_job_id')->nullable()->constrained('scraping_jobs')->nullOnDelete();
            $table->string('name', 191);
            $table->string('category')->nullable();
            $table->string('address', 191)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable()->default('US');
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->unsignedInteger('reviews_count')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('source')->default('yelp');
            $table->string('dedup_hash', 32)->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
