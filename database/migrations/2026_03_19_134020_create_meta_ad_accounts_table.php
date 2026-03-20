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
        Schema::create('meta_ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('meta_account_id')->unique();
            $table->string('business_name')->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('timezone_name')->nullable();
            $table->string('status', 50)->nullable();
            $table->string('access_token_reference')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload_json')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_ad_accounts');
    }
};
