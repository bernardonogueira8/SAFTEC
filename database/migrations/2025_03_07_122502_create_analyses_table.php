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
        // Tabela principal de análises
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();

            // Informações laboratoriais
            $table->boolean('lab_responsible')->default(false)->comment('Indica se há um responsável pelo laboratório');
            $table->longText('lab_notes')->nullable()->comment('Notas do laboratório');
            $table->longText('unit_notes')->nullable()->comment('Notas da unidade');
            $table->unsignedBigInteger('stability_consultation_id');
            $table->string('protocol_number');

            // Lista de medicamentos analisados
            $table->json('medications')->nullable()->comment('Lista de medicamentos envolvidos na análise');

            // Usuário que criou a análise
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->default(1)
                ->comment('Usuário que criou a análise');

            // Controle de registros
            $table->timestamps();
            $table->softDeletes()->comment('Marca o registro como excluído sem removê-lo definitivamente');
        });

        // Tabela intermediária para registrar contribuições de usuários
        Schema::create('analysis_contributions', function (Blueprint $table) {
            $table->id();

            // Relacionamento com análises
            $table->foreignId('analysis_id')
                ->constrained('analyses')
                ->onDelete('cascade')
                ->comment('Análise à qual essa contribuição pertence');

            // Relacionamento com usuários
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('Usuário que contribuiu para a análise');

            // Tipo de contribuição (exemplo: revisão, aprovação, comentário)
            $table->string('role')->nullable()->comment('Tipo de contribuição do usuário (ex: revisão, aprovação)');

            // Timestamp da contribuição
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
