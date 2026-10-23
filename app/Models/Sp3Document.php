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
        'assigned_analyst_id',
        'assigned_at',
        'first_viewed_at',
        'last_viewed_at',
        'sp3_number',
        'no_sppp',
        'ik',
        'google_doc_id',
        'google_doc_url',
        'status',
        'review_status',
        'submitted_at',
        'submitted_by',
        'rejection_note',
        'rejected_at',
        'rejected_by',
        'lcp_google_file_id',
        'lcp_google_file_url',
        'lcp_uploaded_at',
        'photo_link',
        'photo_link_updated_at',
        'doc_generation_status',
        'doc_generation_error',
    ];

    protected $casts = [
        'lcp_uploaded_at' => 'datetime',
        'photo_link_updated_at' => 'datetime',
        'rejected_at'     => 'datetime',
        'submitted_at'    => 'datetime',
        'assigned_at'     => 'datetime',
        'first_viewed_at' => 'datetime',
        'last_viewed_at'  => 'datetime',
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

    public function rejectedByUser()
    {
        return $this->belongsTo(User::class, 'rejected_by', 'user_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by', 'user_id');
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
    /**
     * Query for the sample_parameter rows this SP3 covers
     * (its samples x its parameter).
     */
    public function sampleParametersQuery()
    {
        return SampleParameter::whereIn('sample_id', $this->samples()->pluck('samples.id'))
            ->where('parameter_id', $this->parameter_id);
    }

    public function allResultsFilled(): bool
    {
        return $this->sampleParametersQuery()
            ->where('status', '!=', 'done')
            ->doesntExist();
    }

    public function hasAnyResult(): bool
    {
        return $this->sampleParametersQuery()->where('status', 'done')->exists();
    }

    /**
     * The photo link can be set anytime by the holder, or by whoever sent the SP3.
     */
    public function canSetPhotoLink($userId): bool
    {
        return $this->isOwnedBy($userId)
            || ($this->submitted_by !== null && (int) $this->submitted_by === (int) $userId);
    }

    public function isOwnedBy($userId): bool
    {
        return $this->assigned_analyst_id !== null
            && (int) $this->assigned_analyst_id === (int) $userId;
    }

    /**
     * The owner may still change results: not approved, and either not yet
     * sent to Kepala Divisi or sent back (rejected) for fixing.
     */
    public function isEditable(): bool
    {
        return $this->review_status !== self::STATUS_APPROVED
            && (is_null($this->submitted_at) || $this->review_status === self::STATUS_REJECTED);
    }

    public function isWaitingReview(): bool
    {
        return !is_null($this->submitted_at)
            && in_array($this->review_status, [self::STATUS_PENDING, self::STATUS_RESUBMITTED]);
    }

    /**
     * Owner sends the SP3 to Kepala Divisi. A previously rejected SP3 becomes 'resubmitted'.
     */
    public function markSubmitted(int $userId): void
    {
        $this->update([
            'submitted_at' => now(),
            'submitted_by' => $userId,
        ]);
        $this->markResubmitted();
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
     * Mark this SP3 as resubmitted — called from markSubmitted() when the
     * owner sends a previously rejected SP3 back to Kepala Divisi.
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