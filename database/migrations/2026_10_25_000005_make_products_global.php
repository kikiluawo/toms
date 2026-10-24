<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Products are one group-wide catalogue, not a per-PT dictionary. The table is empty in
 * development so the column simply goes away; the guard keeps a fresh install, which
 * already builds the table without it, from failing on this file.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'company_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'company_id')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }
};
