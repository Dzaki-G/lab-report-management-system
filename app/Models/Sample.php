<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sample extends Model
{
    protected $fillable = [
        'form_pengujian_id',
        'sample_code',
        'sample_name',
        'notes', // Renamed from description
    ];

    public function form()
    {
        return $this->belongsTo(FormPengujian::class, 'form_pengujian_id');
    }

    public function sampleParameters()
    {
        return $this->hasMany(SampleParameter::class);
    }

    public function parameters()
    {
        return $this->belongsToMany(Parameter::class, 'sample_parameters')
                    ->withPivot('method', 'status', 'assigned_analyst_id')
                    ->withTimestamps();
    }
}
