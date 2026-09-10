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
        'filled_by_analyst_id', // set automatically to whoever submits a result — attribution, not assignment
    ];

    public function sample()
    {
        return $this->belongsTo(Sample::class);
    }

    public function parameter()
    {
        return $this->belongsTo(Parameter::class);
    }

    public function filledByAnalyst()
    {
        return $this->belongsTo(User::class, 'filled_by_analyst_id', 'user_id');
    }

    public function analysisResult()
    {
        return $this->hasOne(AnalysisResult::class);
    }
}