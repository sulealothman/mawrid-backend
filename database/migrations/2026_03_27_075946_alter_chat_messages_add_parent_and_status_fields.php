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
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('chat_id');
            $table->foreign('parent_id')
                ->references('id')
                ->on('chat_messages')
                ->nullOnDelete();

            $table->enum('status', ['completed', 'cancelled', 'failed'])
                ->nullable()
                ->after('role');

            $table->text('status_message')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);

            $table->dropColumn([
                'parent_id',
                'status',
                'status_message',
            ]);
        });
    }
};
