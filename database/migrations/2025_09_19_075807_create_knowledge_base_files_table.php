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
        Schema::create('knowledge_base_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('knowledge_base_id');
            $table->foreign('knowledge_base_id')
                ->references('id')
                ->on('knowledge_bases')
                ->cascadeOnDelete();

            $table->foreignId('file_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unique(['knowledge_base_id', 'file_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_files');
    }
};
