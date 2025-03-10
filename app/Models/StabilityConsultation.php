<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Estabelecimento;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StabilityConsultation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'protocol_number',
        'institution_name',
        'cnpj',
        'last_verification_at',
        'excursion_verification_at',
        'estimated_exposure_time',
        'returned_to_storage_at',
        'max_exposed_temperature',
        'min_exposed_temperature',
        'local_exposure',
        'medications',
        'order_number',
        'distribution_number',
        'boolean_unit',
        'observations',
        'file_monitor_temp',
        'created_by',
        'estabelecimento_id'
    ];
    protected $casts = [
        'medications' => 'array',
    ];

    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function boot()
    {
        parent::boot();

        // Gerar número de protocolo automaticamente
        static::creating(function ($model) {
            do {
                $protocolNumber = now()->format('Ymd') . strtoupper(Str::random(6));
            } while (self::where('protocol_number', $protocolNumber)->exists());

            $model->protocol_number = $protocolNumber;
        });
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->estabelecimento_id = auth()->user()->estabelecimento_id;
            }
        });
    }
    protected static function booted()
    {
        static::deleting(function (self $stabilityConsultation) {
            // Verifica se há arquivos anexados no campo `file_monitor_temp`
            if ($stabilityConsultation->file_monitor_temp) {
                Storage::disk('s3')->delete($stabilityConsultation->file_monitor_temp);
            }
        });
        static::saving(function ($model) {
            $medications = $model->medications ?? []; // Garante que seja um array

            foreach ($medications as &$medication) {
                $medication['total_value'] = ($medication['medicament_quantity'] ?? 0) * ($medication['unit_value'] ?? 0);
            }

            $model->medications = $medications; // Agora funciona corretamente!
        });
    }

    public function estabelecimento()
    {
        return $this->belongsTo(Estabelecimento::class, 'estabelecimento_id');
    }

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function medicaments()
    {
        return $this->hasMany(Medicament::class);
    }
}
