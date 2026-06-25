<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_reports', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete()->after('id');

            // Drop the old global unique on source_file; uniqueness will be per-client
            $table->dropUnique(['source_file']);
            $table->unique(['client_id', 'source_file']);
        });
    }

    public function down(): void
    {
        Schema::table('sales_reports', function (Blueprint $table) {
            $table->dropUnique(['client_id', 'source_file']);
            $table->unique(['source_file']);
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
