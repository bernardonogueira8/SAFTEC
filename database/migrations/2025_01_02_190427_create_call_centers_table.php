<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('call_centers', function (Blueprint $table) {
            $table->id();

            // Informações principais
            $table->string('protocolo')->comment('Número do protocolo da solicitação');
            $table->string('setor')->comment('Setor responsável pelo atendimento');
            $table->string('demandante')->nullable()->comment('Nome do demandante');
            $table->boolean('dado_sigiloso')->default(false)->comment('Indica se os dados da solicitação são sigilosos');
            $table->string('unidade')->nullable()->comment('Unidade relacionada à solicitação');
            $table->string('resp_aquisicao')->nullable()->comment('Responsável pela aquisição');
            $table->longText('observation')->nullable()->comment('Observação');

            // Datas importantes
            $table->date('dispensation_date')->nullable()->comment('Data da dispensação do medicamento');
            $table->date('response_date')->nullable()->comment('Data da resposta à solicitação');

            // Dados dos medicamentos
            $table->json('medicaments')->nullable()->comment('Lista de medicamentos envolvidos');

            // Arquivos e anexos
            $table->text('mirror_file')->nullable()->comment('Arquivo de espelho vinculado');
            $table->json('attachments')->nullable()->comment('Lista de anexos associados');

            // Relacionamentos
            $table->foreignId('author_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Usuário responsável pelo atendimento');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->default(1)
                ->comment('Usuário que criou a solicitação');

            $table->foreignId('estabelecimento_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Estabelecimento associado à solicitação');

            // Controle de registros
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_centers');
    }
};
