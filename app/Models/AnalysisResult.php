<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalysisResult extends Model
{
    protected $fillable = [
        'sample_parameter_id',
        'result_value',
        'result_unit',
        'instrument',
        'method',
        'analyst_id',
        'analysis_date',
        'notes',
    ];

    protected $casts = [
        'analysis_date' => 'datetime',
    ];

    public function sampleParameter()
    {
        return $this->belongsTo(SampleParameter::class);
    }

    public function analyst()
    {
        return $this->belongsTo(User::class, 'analyst_id', 'user_id');
    }
}
