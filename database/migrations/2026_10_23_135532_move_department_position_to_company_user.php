<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('company_user', function (Blueprint $table) {
            $table->string('department', 20)->nullable()->after('role');
            $table->string('position', 50)->nullable()->after('department');
        });

        if (Schema::hasColumn('users', 'department')) {
            $users = DB::table('users')->get(['id', 'department', 'position'])->keyBy('id');

            foreach (DB::table('company_user')->get(['id', 'user_id']) as $membership) {
                $user = $users->get($membership->user_id);

                if (! $user) {
                    continue;
                }

                DB::table('company_user')->where('id', $membership->id)->update([
                    'department' => $user->department,
                    'position' => $user->position,
                ]);
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['department', 'position']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('department', 20)->nullable()->after('email');
            $table->string('position', 50)->nullable()->after('department');
        });

        $memberships = DB::table('company_user')
            ->orderBy('id')
            ->get(['user_id', 'department', 'position'])
            ->keyBy('user_id');

        foreach ($memberships as $userId => $membership) {
            DB::table('users')->where('id', $userId)->update([
                'department' => $membership->department,
                'position' => $membership->position,
            ]);
        }

        Schema::table('company_user', function (Blueprint $table) {
            $table->dropColumn(['department', 'position']);
        });
    }
};
