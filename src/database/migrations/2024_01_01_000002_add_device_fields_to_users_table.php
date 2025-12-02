<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $usersTable = config('zego-calling.database.users_table', 'users');

        Schema::table($usersTable, function (Blueprint $table) {
            if (!Schema::hasColumn($usersTable, 'device_token')) {
                $table->text('device_token')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn($usersTable, 'device_platform')) {
                $table->enum('device_platform', ['ios', 'android', 'web'])->nullable()->after('device_token');
            }
            if (!Schema::hasColumn($usersTable, 'is_online')) {
                $table->boolean('is_online')->default(false)->after('device_platform');
            }
            if (!Schema::hasColumn($usersTable, 'last_seen')) {
                $table->timestamp('last_seen')->nullable()->after('is_online');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $usersTable = config('zego-calling.database.users_table', 'users');

        Schema::table($usersTable, function (Blueprint $table) use ($usersTable) {
            $columns = ['device_token', 'device_platform', 'is_online', 'last_seen'];
            foreach ($columns as $column) {
                if (Schema::hasColumn($usersTable, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
