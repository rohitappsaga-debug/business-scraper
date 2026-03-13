<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exports', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->enum('type', ['csv', 'excel', 'json'])->default('csv');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
