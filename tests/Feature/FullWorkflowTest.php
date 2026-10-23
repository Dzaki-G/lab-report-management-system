<?php

namespace Tests\Feature;

use App\Jobs\GenerateLhpJob;
use App\Jobs\GenerateSp3Job;
use App\Models\Notification;
use App\Models\Sp3Document;
use Illuminate\Support\Facades\Bus;

/**
 * SP3 ownership workflow: Ambil -> input -> Kirim -> (Tolak -> perbaiki -> Kirim Ulang) -> Setujui -> LHP.
 * One analyst holds one SP3 (one parameter x all its samples).
 */
class FullWorkflowTest extends LabTestCase
{
    public function test_admin_creating_a_form_creates_one_sp3_per_parameter_and_queues_docs(): void
    {
        $ph = $this->makeParameter('pH');
        $cod = $this->makeParameter('COD');

        $form = $this->createForm([$ph, $cod], sampleCount: 2);

        $this->assertSame('dalam_pengujian', $form->status);
        $this->assertCount(2, $form->sp3Documents);
        $this->assertCount(2, $this->sp3For($form, $ph)->samples);
        Bus::assertDispatchedTimes(GenerateSp3Job::class, 2);
    }

    public function test_analyst_must_take_an_sp3_before_inputting(): void
    {
        $ph = $this->makeParameter('pH');
        $sp3 = $this->sp3For($this->createForm([$ph]), $ph);
        $row = $sp3->sampleParametersQuery()->first();

        $this->actingAs($this->analisA)->get(route('analis.input', $row))
            ->assertRedirect(route('analis.dashboard'))
            ->assertSessionHas('error', 'Ambil SP3 ini terlebih dahulu sebelum menginput hasil.');

        $this->actingAs($this->analisA)->post(route('analis.sp3.take', $sp3))
            ->assertSessionHas('success');

        $this->assertTrue($sp3->fresh()->isOwnedBy($this->analisA->user_id));
        $this->actingAs($this->analisA)->get(route('analis.input', $row))->assertOk();
    }

    public function test_other_analyst_cannot_take_or_input_on_a_taken_sp3(): void
    {
        $ph = $this->makeParameter('pH');
        $sp3 = $this->sp3For($this->createForm([$ph]), $ph);
        $this->actingAs($this->analisA)->post(route('analis.sp3.take', $sp3));

        $this->actingAs($this->analisB)->post(route('analis.sp3.take', $sp3))
            ->assertSessionHas('error');
        $this->assertTrue($sp3->fresh()->isOwnedBy($this->analisA->user_id));

        $row = $sp3->sampleParametersQuery()->first();
        $this->actingAs($this->analisB)->post(route('analis.store', $row), [
            'result_value' => '1', 'instrument' => 'x', 'method' => 'x', 'analysis_date' => now()->toDateString(),
        ])->assertSessionHas('error', 'SP3 ini dipegang analis lain.');
        $this->assertSame('pending', $row->fresh()->status);
    }

    public function test_semua_tugas_shows_everyone_and_tugas_saya_shows_only_mine(): void
    {
        $ph = $this->makeParameter('pH');
        $cod = $this->makeParameter('COD');
        $form = $this->createForm([$ph, $cod]);
        $phSp3 = $this->sp3For($form, $ph);
        $this->actingAs($this->analisA)->post(route('analis.sp3.take', $phSp3));

        $this->actingAs($this->analisB)->get(route('analis.dashboard'))
            ->assertOk()
            ->assertViewHas('allSp3s', fn ($all) => $all->count() === 2)
            ->assertViewHas('mySp3s', fn ($mine) => $mine->isEmpty())
            ->assertSee('Diambil oleh Analis A');

        $this->actingAs($this->analisA)->get(route('analis.dashboard'))
            ->assertViewHas('mySp3s', fn ($mine) => $mine->pluck('id')->all() === [$phSp3->id])
            ->assertViewHas('activeTab', 'saya');
    }

    public function test_release_is_only_allowed_before_any_result(): void
    {
        $ph = $this->makeParameter('pH');
        $sp3 = $this->sp3For($this->createForm([$ph], sampleCount: 2), $ph);
        $this->actingAs($this->analisA)->post(route('analis.sp3.take', $sp3));

        $this->actingAs($this->analisB)->post(route('analis.sp3.release', $sp3))
            ->assertSessionHas('error', 'Anda bukan pemegang SP3 ini.');

        $this->actingAs($this->analisA)->post(route('analis.sp3.release', $sp3))
            ->assertSessionHas('success');
        $this->assertNull($sp3->fresh()->assigned_analyst_id);

        $this->actingAs($this->analisA)->post(route('analis.sp3.take', $sp3));
        $row = $sp3->sampleParametersQuery()->first();
        $this->actingAs($this->analisA)->post(route('analis.store', $row), [
            'result_value' => '1', 'instrument' => 'x', 'method' => 'x', 'analysis_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->analisA)->post(route('analis.sp3.release', $sp3->fresh()))
            ->assertSessionHas('error');
        $this->assertTrue($sp3->fresh()->isOwnedBy($this->analisA->user_id));
    }

    public function test_saving_results_does_not_send_anything_and_kirim_needs_all_rows(): void
    {
        $ph = $this->makeParameter('pH');
        $form = $this->createForm([$ph], sampleCount: 2);
        $sp3 = $this->sp3For($form, $ph);
        $this->actingAs($this->analisA)->post(route('analis.sp3.take', $sp3));

        $first = $sp3->sampleParametersQuery()->first();
        $this->actingAs($this->analisA)->post(route('analis.store', $first), [
            'result_value' => '1', 'instrument' => 'x', 'method' => 'x', 'analysis_date' => now()->toDateString(),
        ]);
        $this->actingAs($this->analisA)->post(route('analis.sp3.submit', $sp3->fresh()))
            ->assertSessionHas('error');
        $this->assertNull($sp3->fresh()->submitted_at);

        $this->fillSp3($sp3, $this->analisA);
        // Every row filled, but nothing is sent until the analyst clicks Kirim
        $this->assertSame('dalam_pengujian', $form->fresh()->status);
        $this->assertNull($sp3->fresh()->submitted_at);
    }

    public function test_form_goes_to_divisi_only_after_every_sp3_is_sent(): void
    {
        $ph = $this->makeParameter('pH');
        $cod = $this->makeParameter('COD');
        $form = $this->createForm([$ph, $cod]);
        $phSp3 = $this->sp3For($form, $ph);
        $codSp3 = $this->sp3For($form, $cod);

        $this->actingAs($this->analisA)->post(route('analis.sp3.take', $phSp3));
        $this->fillSp3($phSp3, $this->analisA);
        $this->actingAs($this->analisA)->post(route('analis.sp3.submit', $phSp3->fresh()))->assertSessionHas('success');

        $phSp3->refresh();
        $this->assertSame($this->analisA->user_id, (int) $phSp3->submitted_by);
        $this->assertNotNull($phSp3->submitted_at);
        $this->assertSame('dalam_pengujian', $form->fresh()->status);
        $this->assertSame(0, Notification::where('user_id', $this->kepalaDivisi->user_id)->count());

        // Sent SP3 is locked while waiting for review
        $row = $phSp3->sampleParametersQuery()->first();
        $this->actingAs($this->analisA)->get(route('analis.input', $row))
            ->assertSessionHas('error', 'SP3 ini sudah dikirim dan sedang menunggu review Kepala Divisi.');

        $this->actingAs($this->analisB)->post(route('analis.sp3.take', $codSp3));
        $this->fillSp3($codSp3, $this->analisB);
        $this->actingAs($this->analisB)->post(route('analis.sp3.submit', $codSp3->fresh()));

        $this->assertSame('menunggu_review_divisi', $form->fresh()->status);
        $this->assertSame(1, Notification::where('user_id', $this->kepalaDivisi->user_id)->count());

        $this->actingAs($this->kepalaDivisi)->get(route('kepala-divisi.review-lhp', $form))
            ->assertOk()
            ->assertSee('Dikirim oleh')
            ->assertSee('Analis A')
            ->assertSee('Analis B');
    }

    public function test_divisi_cannot_approve_or_reject_an_sp3_that_was_not_sent(): void
    {
        $ph = $this->makeParameter('pH');
        $sp3 = $this->sp3For($this->createForm([$ph]), $ph);

        $this->actingAs($this->kepalaDivisi)->post(route('kepala-divisi.sp3.approve', $sp3))
            ->assertSessionHas('error');
        $this->actingAs($this->kepalaDivisi)->post(route('kepala-divisi.sp3.reject', $sp3), ['note' => 'x'])
            ->assertSessionHas('error');
        $this->assertSame(Sp3Document::STATUS_PENDING, $sp3->fresh()->review_status);
    }

    /**
     * The original bug: after Kepala Divisi rejected an SP3 it disappeared for every analyst.
     */
    public function test_rejected_sp3_comes_back_to_its_analyst_with_a_notification(): void
    {
        $ph = $this->makeParameter('pH');
        $form = $this->createForm([$ph]);
        $sp3 = $this->sp3For($form, $ph);
        $this->actingAs($this->analisA)->post(route('analis.sp3.take', $sp3));
        $this->fillSp3($sp3, $this->analisA, '7.1');
        $this->actingAs($this->analisA)->post(route('analis.sp3.submit', $sp3->fresh()));
        $this->assertSame('menunggu_review_divisi', $form->fresh()->status);

        $this->actingAs($this->kepalaDivisi)
            ->post(route('kepala-divisi.sp3.reject', $sp3->fresh()), ['note' => 'Nilai pH tidak masuk akal'])
            ->assertSessionHas('success');

        $notif = Notification::where('user_id', $this->analisA->user_id)->where('type', 'form_rejected')->first();
        $this->assertNotNull($notif);
        $this->assertStringContainsString('Nilai pH tidak masuk akal', $notif->message);
        $this->assertSame($sp3->id, $notif->data['sp3_id']);

        // Clicking the notification lands on Tugas Saya
        $this->actingAs($this->analisA)->post(route('notifications.read', $notif->id))
            ->assertRedirect(route('analis.dashboard', ['tab' => 'saya']));

        // Still visible (form is in Divisi's queue) and editable again
        $this->actingAs($this->analisA)->get(route('analis.dashboard'))
            ->assertViewHas('mySp3s', fn ($mine) => $mine->contains('id', $sp3->id))
            ->assertViewHas('allSp3s', fn ($all) => $all->contains('id', $sp3->id))
            ->assertSee('DITOLAK')
            ->assertSee('Nilai pH tidak masuk akal');

        $row = $sp3->sampleParametersQuery()->first();
        $this->actingAs($this->analisA)->get(route('analis.input', $row))->assertOk();
    }

    public function test_resend_after_reject_notifies_divisi_and_approval_triggers_lhp(): void
    {
        $ph = $this->makeParameter('pH');
        $form = $this->createForm([$ph]);
        $sp3 = $this->sp3For($form, $ph);
        $this->actingAs($this->analisA)->post(route('analis.sp3.take', $sp3));
        $this->fillSp3($sp3, $this->analisA, '99');
        $this->actingAs($this->analisA)->post(route('analis.sp3.submit', $sp3->fresh()));
        $this->actingAs($this->kepalaDivisi)->post(route('kepala-divisi.sp3.reject', $sp3->fresh()), ['note' => 'ulang']);

        $this->fillSp3($sp3->fresh(), $this->analisA, '7.1');
        $this->assertSame(Sp3Document::STATUS_REJECTED, $sp3->fresh()->review_status, 'Saving a fix must not auto-resend');

        $this->actingAs($this->analisA)->post(route('analis.sp3.submit', $sp3->fresh()))
            ->assertSessionHas('success');
        $this->assertSame(Sp3Document::STATUS_RESUBMITTED, $sp3->fresh()->review_status);
        $this->assertTrue(
            Notification::where('user_id', $this->kepalaDivisi->user_id)->where('title', "SP3 {$sp3->sp3_number} dikirim ulang")->exists()
        );

        $this->actingAs($this->kepalaDivisi)->post(route('kepala-divisi.sp3.approve', $sp3->fresh()))
            ->assertSessionHas('success');
        $this->assertSame(Sp3Document::STATUS_APPROVED, $sp3->fresh()->review_status);
        Bus::assertDispatched(GenerateLhpJob::class);

        // Approved SP3 moves to Riwayat and is locked
        $this->actingAs($this->analisA)->get(route('analis.dashboard'))
            ->assertViewHas('mySp3s', fn ($mine) => $mine->isEmpty())
            ->assertViewHas('historySp3s', fn ($h) => $h->contains('id', $sp3->id));
    }

    public function test_lcp_link_can_be_saved_from_the_form_detail_page_field_name(): void
    {
        $ph = $this->makeParameter('pH');
        $form = $this->createForm([$ph]);
        $sp3 = $this->sp3For($form, $ph);

        // form-detail.blade.php posts the field as lcp_link (was lcp_url, which always failed)
        $this->actingAs($this->analisA)->get(route('analis.form.show', $form))
            ->assertOk()
            ->assertSee('name="lcp_link"', false);

        $this->actingAs($this->analisA)->post(route('analis.sp3.submit-lcp', $sp3), ['lcp_link' => 'https://drive.google.com/lcp'])
            ->assertSessionHas('success');
        $this->assertSame('https://drive.google.com/lcp', $sp3->fresh()->lcp_google_file_url);
    }

    public function test_admin_assign_puts_the_sp3_in_that_analysts_tugas_saya(): void
    {
        $ph = $this->makeParameter('pH');
        $sp3 = $this->sp3For($this->createForm([$ph]), $ph);

        $this->actingAs($this->admin)->post(route('admin.assign-analyst', $sp3), ['analyst_id' => $this->analisB->user_id])
            ->assertSessionHas('success');

        $this->actingAs($this->analisB)->get(route('analis.dashboard'))
            ->assertViewHas('mySp3s', fn ($mine) => $mine->contains('id', $sp3->id));
    }
}
