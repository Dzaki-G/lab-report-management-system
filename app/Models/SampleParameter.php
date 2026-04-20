<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SampleParameter extends Model
{
    protected $fillable = [
        'sample_id',
        'parameter_id',
        'method',
        'status',
        'assigned_analyst_id',
    ];

    public function sample()
    {
        return $this->belongsTo(Sample::class);
    }

    public function parameter()
    {
        return $this->belongsTo(Parameter::class);
    }

    public function assignedAnalyst()
    {
        return $this->belongsTo(User::class, 'assigned_analyst_id', 'user_id');
    }

    public function analysisResult()
    {
        return $this->hasOne(AnalysisResult::class);
    }
}
