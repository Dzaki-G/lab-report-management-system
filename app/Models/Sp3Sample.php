<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sp3Sample extends Model
{
    protected $fillable = [
        'sp3_document_id',
        'sample_id',
        'paraf_date',
    ];

    protected $casts = [
        'paraf_date' => 'date',
    ];

    public function sp3Document()
    {
        return $this->belongsTo(Sp3Document::class, 'sp3_document_id');
    }

    public function sample()
    {
        return $this->belongsTo(Sample::class);
    }
}
