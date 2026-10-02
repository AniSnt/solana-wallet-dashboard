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

        Schema::create('account_wallet', function (Blueprint $table) {
            $table->id();

            // Conta dona do vínculo. Se a conta for apagada, seus vínculos somem.
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();

            // Carteira vinculada. A carteira em si é única por endereço (RN-07).
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();

            // Rótulo opcional, particular de cada vínculo (não da carteira).
            $table->string('label')->nullable();

            $table->timestamps();

            // RN-07: a mesma carteira não pode ser vinculada duas vezes à mesma conta.
            $table->unique(['account_id', 'wallet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_wallet');
    }
};
