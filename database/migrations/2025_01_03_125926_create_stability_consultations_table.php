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
        Schema::create('stability_consultations', function (Blueprint $table) {
            // Identificadores únicos e relacionamentos
            $table->id();
            $table->string('protocol_number')->unique()->comment('Número de protocolo único para a consulta');

            // Informações da Instituição
            $table->string('institution_name')->comment('Nome da instituição responsável');
            $table->string('cnpj', 18)->nullable()->comment('CNPJ da instituição, formatado como XX.XXX.XXX/XXXX-XX');

            // Informações de verificação e excursão de temperatura
            $table->timestamp('last_verification_at')->nullable()->comment('Última verificação antes da excursão de temperatura');
            $table->timestamp('excursion_verification_at')->nullable()->comment('Momento da verificação da excursão de temperatura');
            $table->integer('estimated_exposure_time')->nullable()->comment('Tempo estimado de exposição em minutos');
            $table->timestamp('returned_to_storage_at')->nullable()->comment('Momento em que o item retornou ao armazenamento');
            $table->decimal('max_exposed_temperature', 5, 2)->nullable()->comment('Temperatura máxima exposta durante a excursão');
            $table->decimal('min_exposed_temperature', 5, 2)->nullable()->comment('Temperatura mínima exposta durante a excursão');

            // Local da exposição
            $table->text('local_exposure')->nullable()->comment('Local onde ocorreu a excursão de temperatura');

            // Informações sobre os medicamentos (JSON para armazenar múltiplos itens)
            $table->json('medications')->nullable()->comment('Lista de medicamentos envolvidos na excursão');

            // Dados administrativos
            $table->string('manufacturer_id')->nullable();
            $table->string('order_number')->nullable()->comment('Número do pedido');
            $table->string('distribution_number')->nullable()->comment('Número da distribuição');
            $table->text('observations')->nullable()->comment('Observações adicionais');
            $table->string('file_monitor_temp')->nullable()->comment('Caminho do arquivo de monitoramento de temperatura');

            // Indicador booleano adicional
            $table->boolean('boolean_unit')->default(false)->comment('Indicador booleano para unidade');


            // Chave estrangeira do usuário que criou o registro
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Usuário que criou o registro');

            // Chave estrangeira do estabelecimento
            $table->foreignId('estabelecimento_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete()
                ->comment('Estabelecimento relacionado à consulta de estabilidade');

            $table->softDeletes();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stability_consultations');
    }
};
