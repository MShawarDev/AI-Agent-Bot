<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);

            // Bot branding & behaviour
            $table->string('bot_name')->default('Sales Assistant');
            $table->text('system_prompt')->nullable();
            $table->string('currency', 10)->default('AED');
            $table->string('brand_color', 20)->nullable();
            $table->string('logo_path')->nullable();
            $table->json('starter_prompts')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
