<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sp3Document extends Model
{
    protected $fillable = [
        'form_pengujian_id',
        'parameter_id',
        'sp3_number',
        'google_doc_id',
        'google_doc_url',
        'no_sppp',
        'ik',
        'assigned_analyst_id',
        'status',
        'signed_at',
        'lcp_google_file_id',
        'lcp_google_file_url',
        'lcp_uploaded_at',
        'needs_revision',
        'revision_note',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'lcp_uploaded_at' => 'datetime',
        'needs_revision' => 'boolean',
    ];

    public function formPengujian()
    {
        return $this->belongsTo(FormPengujian::class, 'form_pengujian_id');
    }
    
    // Alias for easier access
    public function form()
    {
        return $this->belongsTo(FormPengujian::class, 'form_pengujian_id');
    }

    public function parameter()
    {
        return $this->belongsTo(Parameter::class);
    }

    public function assignedAnalyst()
    {
        return $this->belongsTo(User::class, 'assigned_analyst_id', 'user_id');
    }

    public function sp3Samples()
    {
        return $this->hasMany(Sp3Sample::class, 'sp3_document_id');
    }

    public function samples()
    {
        return $this->belongsToMany(Sample::class, 'sp3_samples', 'sp3_document_id', 'sample_id')
                    ->withPivot('paraf_date')
                    ->withTimestamps();
    }

    /**
     * Generate next SP3 number
     */
    public static function generateNextNumber(): string
    {
        $lastDoc = self::orderBy('id', 'desc')->first();
        if (!$lastDoc) {
            return 'SP3-001';
        }
        
        // Extract number from SP3-XXX
        preg_match('/SP3-(\d+)/', $lastDoc->sp3_number, $matches);
        $nextNumber = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        
        return 'SP3-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }
}
