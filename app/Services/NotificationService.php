<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\FormPengujian;
use App\Enums\Role;

class NotificationService
{
    /**
     * Create a notification for a specific user
     */
    public function create($userId, $type, $title, $message, $data = null)
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Create notifications for users with specific role
     */
    public function createForRole($roleId, $type, $title, $message, $data = null)
    {
        $users = User::where('role_id', $roleId)->where('is_active', true)->get();
        
        foreach ($users as $user) {
            $this->create($user->user_id, $type, $title, $message, $data);
        }
    }

    /**
     * Notify form pending action
     */
    public function notifyFormPending(FormPengujian $form, $targetRoleId, $actionDescription)
    {
        $this->createForRole(
            $targetRoleId,
            'form_pending',
            'Form Menunggu Tindakan',
            "Form {$form->form_number} ({$form->customer_name}) memerlukan {$actionDescription}",
            ['form_id' => $form->id]
        );
    }

    /**
     * Notify for signature (TTD) request - distinct from general pending
     */
    public function notifyTtdRequest(FormPengujian $form, $targetRoleId)
    {
        $this->createForRole(
            $targetRoleId,
            'ttd_request',
            '✍️ Permintaan Tanda Tangan',
            "Form {$form->form_number} ({$form->customer_name}) siap untuk ditandatangani",
            ['form_id' => $form->id]
        );
    }

    /**
     * Notify specific user about assignment
     */
    public function notifyAssignment($userId, FormPengujian $form, $parameterName)
    {
        $this->create(
            $userId,
            'assignment',
            'Parameter Ditugaskan',
            "Anda ditugaskan untuk menguji parameter {$parameterName} pada form {$form->form_number}",
            ['form_id' => $form->id]
        );
    }

    /**
     * Notify form completed
     */
    public function notifyFormCompleted(FormPengujian $form)
    {
        $this->createForRole(
            Role::ADMIN,
            'form_completed',
            'Form Selesai',
            "Form {$form->form_number} ({$form->customer_name}) telah selesai",
            ['form_id' => $form->id]
        );
    }

    /**
     * Notify form rejected
     */
    public function notifyFormRejected(FormPengujian $form, $rejectedBy, $note)
    {
        // Notify admin
        $this->createForRole(
            Role::ADMIN,
            'form_rejected',
            'Form Ditolak',
            "Form {$form->form_number} ditolak: {$note}",
            ['form_id' => $form->id]
        );
    }

    /**
     * Create deadline notifications
     */
    public function checkDeadlines()
    {
        $today = now()->startOfDay();
        $threeDaysLater = now()->addDays(3)->startOfDay();
        $oneDayLater = now()->addDays(1)->startOfDay();

        // Forms with 3 days deadline
        $forms3Days = FormPengujian::whereNotIn('status', ['selesai', 'ditolak'])
            ->whereDate('deadline_date', $threeDaysLater)
            ->get();

        foreach ($forms3Days as $form) {
            $this->notifyDeadline($form, 3);
        }

        // Forms with 1 day deadline
        $forms1Day = FormPengujian::whereNotIn('status', ['selesai', 'ditolak'])
            ->whereDate('deadline_date', $oneDayLater)
            ->get();

        foreach ($forms1Day as $form) {
            $this->notifyDeadline($form, 1);
        }

        return count($forms3Days) + count($forms1Day);
    }

    /**
     * Send deadline notification to relevant users
     */
    private function notifyDeadline(FormPengujian $form, $daysLeft)
    {
        $type = $daysLeft === 1 ? 'deadline_1day' : 'deadline_3days';
        $urgency = $daysLeft === 1 ? '🚨 URGENT' : '⏰';
        
        $title = "{$urgency} Deadline {$daysLeft} Hari Lagi";
        $message = "Form {$form->form_number} ({$form->customer_name}) deadline dalam {$daysLeft} hari!";
        $data = ['form_id' => $form->id];

        // Notify Admin
        $this->createForRole(Role::ADMIN, $type, $title, $message, $data);

        // Notify based on current status
        switch ($form->status) {
            case 'verifikasi_upa_1':
            case 'ttd_upa':
                $this->createForRole(Role::KEPALA_UPA, $type, $title, $message, $data);
                break;
            case 'verifikasi_divisi':
            case 'verifikasi_hasil_divisi':
                $this->createForRole(Role::KEPALA_DIVISI, $type, $title, $message, $data);
                break;
            case 'dalam_pengujian':
                // Notify assigned analysts
                foreach ($form->samples as $sample) {
                    foreach ($sample->sampleParameters as $sp) {
                        if ($sp->assigned_analyst_id && $sp->status !== 'done') {
                            $this->create($sp->assigned_analyst_id, $type, $title, $message, $data);
                        }
                    }
                }
                break;
        }
    }
}
