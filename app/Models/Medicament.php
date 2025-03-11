<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicament extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'simpas',
        'text_bula',
    ];

    public function medicaments()
    {
        return $this->belongsToMany(Medicament::class, 'medicament_user', 'user_id', 'medicament_id');
    }


    public function callCenters()
    {
        return $this->belongsToMany(CallCenter::class, 'call_center_medicament', 'medicament_id', 'call_center_id');
    }
}
