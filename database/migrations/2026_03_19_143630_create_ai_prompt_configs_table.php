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
        Schema::create('ai_prompt_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('agent_type');
            $table->string('model')->default('gpt-4-turbo-preview');
            $table->decimal('temperature', 4, 2)->default(0.70);
            $table->integer('max_tokens')->default(2000);
            $table->text('system_prompt');
            $table->text('user_prompt_template');
            $table->json('response_format')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('key');
            $table->index('agent_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_prompt_configs');
    }
};
