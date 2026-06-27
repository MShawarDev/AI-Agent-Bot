<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('accent_color', 20)->nullable()->after('brand_color');
            $table->string('theme_mode', 10)->default('light')->after('accent_color');
            $table->string('bg_style', 10)->default('mesh')->after('theme_mode');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['accent_color', 'theme_mode', 'bg_style']);
        });
    }
};
