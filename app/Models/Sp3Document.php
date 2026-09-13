<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sp3Document extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_RESUBMITTED = 'resubmitted';
    const STATUS_APPROVED = 'approved';

    protected $attributes = [
        'review_status' => self::STATUS_PENDING,
    ];

    protected $fillable = [
        'form_pengujian_id',
        'parameter_id',
        'sp3_number',
        'no_sppp',
        'ik',
        'google_doc_id',
        'google_doc_url',
        'status',
        // Review / rejection tracking (per-SP3, replaces old needs_revision/revision_note)
        'review_status', // pending | rejected | resubmitted | approved
        'rejection_note',
        'rejected_at',
        'rejected_by',
        // LCP — optional, non-blocking, addable any time
        'lcp_google_file_id',
        'lcp_google_file_url',
        'lcp_uploaded_at',
        // Async generation tracking
        'doc_generation_status',
        'doc_generation_error',
    ];

    protected $casts = [
        'lcp_uploaded_at' => 'datetime',
        'rejected_at' => 'datetime',
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

    public function rejectedByUser()
    {
        return $this->belongsTo(User::class, 'rejected_by', 'user_id');
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
     * Check if every sample_parameter under this SP3 has a result entered.
     * This is what makes an SP3 eligible for Divisi's review.
     */
    public function allResultsFilled(): bool
    {
        $sampleIds = $this->samples()->pluck('samples.id');

        return SampleParameter::whereIn('sample_id', $sampleIds)
            ->where('parameter_id', $this->parameter_id)
            ->where('status', '!=', 'done')
            ->doesntExist();
    }

    /**
     * Reject this SP3 with a note. Notifies whichever analyst(s) filled results in it.
     */
    public function markRejected(string $note, int $rejectedByUserId): void
    {
        $this->update([
            'review_status' => self::STATUS_REJECTED,
            'rejection_note' => $note,
            'rejected_at' => now(),
            'rejected_by' => $rejectedByUserId,
        ]);
    }

    /**
     * Mark this SP3 as resubmitted — called automatically the instant an analyst
     * edits a result on an SP3 that was previously rejected.
     */
    public function markResubmitted(): void
    {
        if ($this->review_status === self::STATUS_REJECTED) {
            $this->update(['review_status' => self::STATUS_RESUBMITTED]);
        }
    }

    /**
     * Approve this SP3 — clears the rejection note per the confirmed lightweight approach.
     */
    public function markApproved(): void
    {
        $this->update([
            'review_status' => self::STATUS_APPROVED,
            'rejection_note' => null,
            'rejected_at' => null,
            'rejected_by' => null,
        ]);
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

        preg_match('/SP3-(\d+)/', $lastDoc->sp3_number, $matches);
        $nextNumber = isset($matches[1]) ? intval($matches[1]) + 1 : 1;

        return 'SP3-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public static function generateNextSpppSeq(): int
    {
        $month   = now()->format('m');
        $year    = now()->format('Y');
        $pattern = "%/SPPP/NK/{$month}/{$year}";
        return self::where('no_sppp', 'like', $pattern)->count() + 1;
    }

    public static function spppSuffix(): string
    {
        return '/SPPP/NK/' . now()->format('m') . '/' . now()->format('Y');
    }
}