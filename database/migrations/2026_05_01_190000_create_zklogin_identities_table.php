<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zklogin_identities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 32)->default('google');
            $table->string('issuer', 191);
            $table->string('subject', 191);
            $table->string('audience', 191);
            $table->string('salt', 128);
            $table->string('wallet_address', 80)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'issuer', 'subject'], 'zklogin_provider_issuer_subject_unique');
            $table->index(['user_id', 'provider'], 'zklogin_user_provider_index');
            $table->index('wallet_address', 'zklogin_wallet_address_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zklogin_identities');
    }
};
