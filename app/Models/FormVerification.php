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
            'submit' => 'Form disubmit',
            'approve' => 'Disetujui',
            'reject' => 'Ditolak',
            'input_hasil' => 'Input hasil pengujian',
            default => ucfirst($this->action),
        };
    }

    /**
     * Get human-readable status label
     */
    public static function getStatusLabel($status)
    {
        return match($status) {
            'draft' => 'Draft',
            'verifikasi_upa_1' => 'Menunggu Verifikasi Kepala UPA',
            'verifikasi_divisi' => 'Menunggu Verifikasi Kepala Divisi',
            'dalam_pengujian' => 'Dalam Pengujian',
            'verifikasi_hasil_divisi' => 'Menunggu Verifikasi Hasil - Kepala Divisi',
            'input_lhp' => 'Input LHP oleh Admin',
            'ttd_divisi_lhp' => 'Menunggu TTD Kepala Divisi (LHP)',
            'ttd_upa' => 'Menunggu TTD Kepala UPA (LHP)',
            'kirim_customer' => 'Menunggu Pengiriman ke Customer',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
