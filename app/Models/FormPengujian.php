<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sp3Document;

class FormPengujian extends Model
{
    protected $table = 'form_pengujian';

    protected $fillable = [
        'form_number',
        'lhp_number',
        'no_terima_sampel',
        'received_date',
        'deadline_date',
        'status',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_institution',
        'customer_position',
        'admin_id',
        'sample_type',
        'sample_matrix',
        'sample_name_label',
        'sample_form',
        'sample_packing',
        'sample_count',
        'contact_person',
        // LHP tracking
        'lhp_google_file_id',
        'lhp_uploaded_at',
        'lhp_signed_divisi_at',
        'lhp_signed_upa_at',
    ];

    protected $casts = [
        'lhp_uploaded_at' => 'datetime',
        'lhp_signed_divisi_at' => 'datetime',
        'lhp_signed_upa_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id', 'user_id');
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

    /**
     * Get human-readable status label
     */
    public function getStatusLabelAttribute()
    {
        return FormVerification::getStatusLabel($this->status);
    }

    /**
     * Get status step number
     * New, shorter state machine: dalam_pengujian -> menunggu_review_divisi -> ttd_upa -> selesai
     */
    public function getStatusStepAttribute()
    {
        return match($this->status) {
            'dalam_pengujian' => 1,
            'menunggu_review_divisi' => 2,
            'ttd_upa' => 3,
            'selesai' => 4,
            default => 0,
        };
    }

    /**
     * Check if all samples have completed analysis (all sample_parameters marked 'done')
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
     * Check if every SP3 belonging to this form has been approved by Kepala Divisi.
     * This is the trigger condition for generating the LHP document.
     */
    public function allSp3Approved()
    {
        return $this->sp3Documents->isNotEmpty()
            && $this->sp3Documents->every(fn ($sp3) => $sp3->review_status === Sp3Document::STATUS_APPROVED);
    }

    /**
     * Auto-generate an LHP number in the format {seq}/LHP/NK/{month}/{year}.
     * Counts existing LHP numbers in the same month/year to determine the sequence.
     */
    public static function generateFormNumber(): string
    {
        $month = now()->format('m');
        $year  = now()->format('Y');
        $pattern = "%/NK/{$month}/{$year}";
        $count = self::where('form_number', 'like', $pattern)->count();
        $seq   = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        return "{$seq}/NK/{$month}/{$year}";
    }

    public static function generateLhpNumber(): string
    {
        $month = now()->format('m');
        $year  = now()->format('Y');
        $pattern = "%/LHP/NK/{$month}/{$year}";

        $count = self::where('lhp_number', 'like', $pattern)->count();
        $seq   = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        return "{$seq}/LHP/NK/{$month}/{$year}";
    }

    /**
     * Get the name of the role responsible for a given step in the new workflow
     */
    public function getVerifierName($stepStatus)
    {
        return match($stepStatus) {
            'dalam_pengujian' => 'Analis',
            'menunggu_review_divisi' => 'Kepala Divisi',
            'ttd_upa' => 'Kepala UPA',
            'selesai' => 'Admin',
            default => null,
        };
    }
}