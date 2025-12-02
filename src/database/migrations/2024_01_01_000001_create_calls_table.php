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
        $tableName = config('zego-calling.database.calls_table', 'calls');
        $usersTable = config('zego-calling.database.users_table', 'users');

        Schema::create($tableName, function (Blueprint $table) use ($usersTable) {
            $table->id();
            $table->foreignId('caller_id')->constrained($usersTable)->onDelete('cascade');
            $table->foreignId('receiver_id')->constrained($usersTable)->onDelete('cascade');
            $table->string('room_id')->unique();
            $table->enum('call_type', ['audio', 'video'])->default('video');
            $table->enum('status', ['initiated', 'ringing', 'accepted', 'rejected', 'ended', 'missed'])->default('initiated');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration')->nullable()->comment('Duration in seconds');
            $table->text('metadata')->nullable()->comment('Additional call metadata in JSON format');
            $table->timestamps();

            $table->index(['caller_id', 'receiver_id']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('zego-calling.database.calls_table', 'calls');
        Schema::dropIfExists($tableName);
    }
};
