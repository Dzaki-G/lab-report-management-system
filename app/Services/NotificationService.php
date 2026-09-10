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
        $forms3Days = FormPengujian::where('status', '!=', 'selesai')
            ->whereDate('deadline_date', $threeDaysLater)
            ->get();

        foreach ($forms3Days as $form) {
            $this->notifyDeadline($form, 3);
        }

        // Forms with 1 day deadline
        $forms1Day = FormPengujian::where('status', '!=', 'selesai')
            ->whereDate('deadline_date', $oneDayLater)
            ->get();

        foreach ($forms1Day as $form) {
            $this->notifyDeadline($form, 1);
        }

        return count($forms3Days) + count($forms1Day);
    }

    /**
     * Send deadline notification to relevant users.
     * Updated for the new (shorter) state machine:
     * dalam_pengujian -> menunggu_review_divisi -> ttd_upa -> selesai
     */
    private function notifyDeadline(FormPengujian $form, $daysLeft)
    {
        $type = $daysLeft === 1 ? 'deadline_1day' : 'deadline_3days';
        $urgency = $daysLeft === 1 ? '🚨 URGENT' : '⏰';

        $title = "{$urgency} Deadline {$daysLeft} Hari Lagi";
        $message = "Form {$form->form_number} ({$form->customer_name}) deadline dalam {$daysLeft} hari!";
        $data = ['form_id' => $form->id];

        // Notify Admin regardless of stage
        $this->createForRole(Role::ADMIN, $type, $title, $message, $data);

        switch ($form->status) {
            case 'dalam_pengujian':
                // No per-parameter assignment anymore — nudge all analysts collectively,
                // same audience that was notified when the form was created.
                $this->createForRole(Role::ANALIS, $type, $title, $message, $data);
                break;
            case 'menunggu_review_divisi':
                $this->createForRole(Role::KEPALA_DIVISI, $type, $title, $message, $data);
                break;
            case 'ttd_upa':
                $this->createForRole(Role::KEPALA_UPA, $type, $title, $message, $data);
                break;
        }
    }
}