<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->uuid('knowledge_base_id')->after('owner_id');

            $table->foreign('knowledge_base_id')
                ->references('id')
                ->on('knowledge_bases')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropForeign(['knowledge_base_id']);
            $table->dropColumn('knowledge_base_id');
        });
    }
};
