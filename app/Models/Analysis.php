<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Foundation\Auth\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Analysis extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'stability_consultation_id',  // Coluna para armazenar o ID da consulta
        'protocol_number',  // Coluna para armazenar o número do protocolo
        'lab_responsible',  // Responsável pelo laboratório
        'lab_notes',        // Notas do laboratório
        'unit_notes',       // Notas da unidade
        'medications',      // Lista de medicamentos
        'created_by',       // Usuário que criou a análise
        'analysis_id',      // ID da análise
        'user_id',          // ID do usuário que contribui
        'role',             // Tipo de contribuição
    ];

    // Tipos de dados a serem convertidos automaticamente
    protected $casts = [
        'medications' => 'array',
    ];

    // Configuração para logar as alterações
    public function getActivitylogOptions(): LogOptions
    {
        // Registra apenas os campos definidos no $fillable
        return LogOptions::defaults()->logOnly($this->fillable);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    // Defina a relação com StabilityConsultation, se necessário
    public function stabilityConsultation()
    {
        return $this->belongsTo(StabilityConsultation::class, 'analysis_id');
    }
}
