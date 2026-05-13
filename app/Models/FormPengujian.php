<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormPengujian extends Model
{
    protected $table = 'form_pengujian';

    protected $fillable = [
        'form_number',
        'no_spu',
        'no_terima_sampel',
        'received_date',
        'deadline_date',
        'status',
        'customer_name',
        'customer_phone',
        'customer_institution',
        'customer_position',
        'admin_id',
        'assigned_analyst_id',
        // SPU document tracking
        'google_doc_id',
        'spu_generated_at',
        'spu_file_path',
        'spu_unsigned_doc_id',
        'spu_unsigned_doc_url',
        'spu_signed_doc_id',
        'spu_signed_doc_url',
        'spu_signed_at',
        'spu_signed_divisi_at',
        // LCP/LHP tracking
        'lcp_link',
        'lhp_link',
        'lhp_google_file_id',
        'lhp_google_file_url',
        'lhp_uploaded_at',
        'lhp_signed_divisi_at',
        'lhp_signed_upa_at',
        // Rejection
        'rejection_note',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'rejected_at' => 'datetime',
        'spu_generated_at' => 'datetime',
        'spu_signed_at' => 'datetime',
        'spu_signed_divisi_at' => 'datetime',
        'lhp_uploaded_at' => 'datetime',
        'lhp_signed_divisi_at' => 'datetime',
        'lhp_signed_upa_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id', 'user_id');
    }

    public function assignedAnalyst()
    {
        return $this->belongsTo(User::class, 'assigned_analyst_id', 'user_id');
    }

    public function samples()
    {
        return $this->hasMany(Sample::class, 'form_pengujian_id');
    }

    public function verifications()
    {
        return $this->hasMany(FormVerification::class, 'form_pengujian_id');
    }

    public function sp3Documents()
    {
        return $this->hasMany(Sp3Document::class, 'form_pengujian_id');
    }

    public function rejectedByUser()
    {
        return $this->belongsTo(User::class, 'rejected_by', 'user_id');
    }

    /**
     * Get human-readable status label
     */
    public function getStatusLabelAttribute()
    {
        return FormVerification::getStatusLabel($this->status);
    }

    /**
     * Get status step number (1-9)
     */
    public function getStatusStepAttribute()
    {
        return match($this->status) {
            'draft' => 1,
            'verifikasi_upa_1' => 2,
            'verifikasi_divisi' => 3,
            'dalam_pengujian' => 4,
            'verifikasi_hasil_divisi' => 5,
            'input_lhp' => 6,
            'ttd_divisi_lhp' => 7,
            'ttd_upa' => 8,
            'kirim_customer' => 9,
            'selesai' => 10,
            default => 0,
        };
    }

    /**
     * Check if all samples have completed analysis
     */
    public function allSamplesAnalyzed()
    {
        foreach ($this->samples as $sample) {
            foreach ($sample->sampleParameters as $sp) {
                if ($sp->status !== 'done') {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get the name of the user who verified/completed a specific step
     * Now returns static role names as requested
     */
    public function getVerifierName($stepStatus)
    {
        return match($stepStatus) {
            'verifikasi_upa_1' => 'Kepala UPA',
            'verifikasi_divisi' => 'Kepala Divisi',
            'dalam_pengujian' => 'Analis', 
            'verifikasi_hasil_divisi' => 'Kepala Divisi',
            'input_lhp' => 'Admin',
            'ttd_divisi_lhp' => 'Kepala Divisi',
            'ttd_upa' => 'Kepala UPA',
            'kirim_customer' => 'Admin',
            default => null,
        };
    }
}
