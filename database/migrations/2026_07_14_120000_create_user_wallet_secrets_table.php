<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_wallet_secrets')) {
            return;
        }

        Schema::create('user_wallet_secrets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('provider', 32)->default('google');
            $table->string('kind', 64);
            $table->string('network', 32);
            // Encrypted Eloquent casts are variable-length and must use TEXT/LONGTEXT.
            $table->longText('encrypted_payload');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'kind', 'network'], 'user_wallet_secrets_owner_kind_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_wallet_secrets');
    }
};
