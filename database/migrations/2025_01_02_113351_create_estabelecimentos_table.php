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
        Schema::create('estabelecimentos', function (Blueprint $table) {
            $table->id();

            // Identificadores do estabelecimento
            $table->string('cnes', 7)->comment('Código CNES do estabelecimento de saúde');
            $table->string('cnpj', 18)->nullable()->comment('CNPJ do estabelecimento, se aplicável');

            // Informações do estabelecimento
            $table->string('name')->comment('Nome do estabelecimento');
            $table->string('macrorregiao')->nullable()->comment('Macrorregião à qual o estabelecimento pertence');

            // Campos de controle
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estabelecimentos');
    }
};
