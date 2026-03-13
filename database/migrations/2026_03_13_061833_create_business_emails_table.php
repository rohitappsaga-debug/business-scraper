<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('email');
            $table->boolean('verified')->default(false);
            $table->timestamps();

            $table->unique(['business_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_emails');
    }
};
