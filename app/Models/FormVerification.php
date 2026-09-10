<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormVerification extends Model
{
    protected $fillable = [
        'form_pengujian_id',
        'action',
        'from_status',
        'to_status',
        'verified_by',
        'note',
    ];

    public function form()
    {
        return $this->belongsTo(FormPengujian::class, 'form_pengujian_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by', 'user_id');
    }

    /**
     * Get human-readable action label
     */
    public function getActionLabelAttribute()
    {
        return match($this->action) {
            'buat_form'        => 'Form dibuat',
            'submit'           => 'Form disubmit',
            'approve'          => 'Disetujui',
            'reject'           => 'Ditolak',
            'selesai_pengujian' => 'Semua pengujian selesai',
            'sp3_approve'      => 'SP3 disetujui',
            'sp3_reject'       => 'SP3 ditolak',
            'sp3_resubmit'     => 'SP3 diperbaiki dan dikirim ulang',
            'sign_lhp'         => 'LHP ditandatangani',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get human-readable status label.
     * New (shorter) state machine: dalam_pengujian -> menunggu_review_divisi -> ttd_upa -> selesai
     * Old status strings kept in the match as a fallback in case any historical
     * FormVerification rows still reference them — safe to remove once old data is gone.
     */
    public static function getStatusLabel($status)
    {
        return match($status) {
            'dalam_pengujian' => 'Dalam Pengujian',
            'menunggu_review_divisi' => 'Menunggu Review Kepala Divisi',
            'ttd_upa' => 'Menunggu TTD Kepala UPA (LHP)',
            'selesai' => 'Selesai',

            // Legacy statuses — historical records only, no longer reachable going forward
            'draft' => 'Draft',
            'verifikasi_upa_1' => 'Menunggu Verifikasi Kepala UPA',
            'verifikasi_divisi' => 'Menunggu Verifikasi Kepala Divisi',
            'verifikasi_hasil_divisi' => 'Menunggu Verifikasi Hasil - Kepala Divisi',
            'input_lhp' => 'Input LHP oleh Admin',
            'ttd_divisi_lhp' => 'Menunggu TTD Kepala Divisi (LHP)',
            'kirim_customer' => 'Menunggu Pengiriman ke Customer',
            'ditolak' => 'Ditolak',

            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}