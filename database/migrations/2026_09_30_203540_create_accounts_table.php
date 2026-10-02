<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            // Dono da conta. Se o usuário for apagado, suas contas somem junto.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 'individual' (PF) ou 'company' (PJ), valores do enum AccountType.
            $table->string('type');

            // Nome da pessoa (PF) ou razão social (PJ).
            $table->string('name');

            // CPF ou CNPJ, só dígitos. Único no sistema (RN-05).
            $table->string('document')->unique();

            $table->timestamps();
        });

        // RN-02: no máximo uma conta PF por usuário, garantido pelo banco.
        DB::statement("CREATE UNIQUE INDEX one_pf_per_user ON accounts (user_id) WHERE type = 'individual'");
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
