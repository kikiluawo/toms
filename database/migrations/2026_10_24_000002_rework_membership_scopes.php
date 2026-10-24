<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->boolean('is_head_office')->default(false)->after('code');
        });

        Schema::table('company_user', function (Blueprint $table) {
            $table->string('data_scope', 10)->default('branch')->after('role');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete()->after('data_scope');
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete()->after('department_id');
        });

        Schema::table('company_user', function (Blueprint $table) {
            $table->dropColumn(['all_branches', 'is_head_office', 'department', 'position']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->after('email');
        });

        Schema::table('company_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('position_id');
            $table->dropColumn('data_scope');
            $table->boolean('all_branches')->default(true)->after('role');
            $table->boolean('is_head_office')->default(false)->after('all_branches');
            $table->string('department', 20)->nullable()->after('role');
            $table->string('position', 50)->nullable()->after('department');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('is_head_office');
        });
    }
};
