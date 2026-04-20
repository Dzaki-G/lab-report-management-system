<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
    protected $fillable = [
        'name',
        'category',
        'default_method',
        'default_unit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sampleParameters()
    {
        return $this->hasMany(SampleParameter::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
