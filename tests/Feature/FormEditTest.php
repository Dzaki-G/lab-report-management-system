<?php

namespace Tests\Feature;

use App\Jobs\GenerateSp3Job;
use App\Models\FormPengujian;
use App\Models\FormVerification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;

class FormEditTest extends LabTestCase
{
    private function payload(FormPengujian $form, array $overrides = [], ?callable $samples = null): array
    {
        $rows = $form->samples->values()->map(fn ($s) => [
            'id' => $s->id, 'sample_code' => $s->sample_code, 'sample_name' => $s->sample_name, 'notes' => $s->notes,
        ])->all();

        if ($samples) {
            $rows = $samples($rows);
        }

        return array_merge([
            'received_date' => Carbon::parse($form->received_date)->toDateString(),
            'customer_name' => $form->customer_name,
            'samples'       => $rows,
        ], $overrides);
    }

    public function test_admin_can_edit_header_fields_and_deadline_is_recalculated(): void
    {
        $form = $this->createForm([$this->makeParameter('pH')]);

        $this->actingAs($this->admin)->get(route('form.edit', $form))->assertOk()->assertSee('PT Uji');

        $this->actingAs($this->admin)->put(route('form.update', $form), $this->payload($form, [
            'customer_name' => 'PT Baru',
            'received_date' => '2026-10-05', // Monday
        ]))->assertRedirect(route('form.show', $form));

        $form->refresh();
        $this->assertSame('PT Baru', $form->customer_name);
        $this->assertSame('2026-10-21', Carbon::parse($form->deadline_date)->toDateString()); // +12 weekdays
        $this->assertTrue(FormVerification::where('form_pengujian_id', $form->id)->where('action', 'edit_form')->exists());
    }

    public function test_changing_a_sample_code_regenerates_only_the_sp3s_containing_it(): void
    {
        $ph = $this->makeParameter('pH');
        $cod = $this->makeParameter('COD');
        $form = $this->createForm([$ph, $cod], sampleCount: 2);
        // Make sample 2 belong only to the COD SP3
        $sample2 = $form->samples[1];
        $this->sp3For($form, $ph)->samples()->detach($sample2->id);

        Bus::fake(); // reset the dispatches from form creation

        $this->actingAs($this->admin)->put(route('form.update', $form), $this->payload($form, [], function ($rows) {
            $rows[1]['sample_code'] = '999/EDIT';
            return $rows;
        }))->assertSessionHas('success');

        $this->assertSame('999/EDIT', $sample2->fresh()->sample_code);
        Bus::assertDispatchedTimes(GenerateSp3Job::class, 1);
        $this->assertSame('queued', $this->sp3For($form, $cod)->doc_generation_status);
    }

    public function test_changing_only_notes_does_not_regenerate_sp3s(): void
    {
        $form = $this->createForm([$this->makeParameter('pH')]);
        Bus::fake();

        $this->actingAs($this->admin)->put(route('form.update', $form), $this->payload($form, [], function ($rows) {
            $rows[0]['notes'] = 'Sampel agak keruh';
            return $rows;
        }))->assertSessionHas('success');

        $this->assertSame('Sampel agak keruh', $form->samples[0]->fresh()->notes);
        Bus::assertNotDispatched(GenerateSp3Job::class);
    }

    public function test_finished_forms_cannot_be_edited(): void
    {
        $form = $this->createForm([$this->makeParameter('pH')]);
        $form->update(['status' => 'selesai']);

        $this->actingAs($this->admin)->get(route('form.edit', $form))
            ->assertRedirect(route('form.show', $form))->assertSessionHas('error');
        $this->actingAs($this->admin)->put(route('form.update', $form), $this->payload($form, ['customer_name' => 'X']))
            ->assertSessionHas('error');
        $this->assertSame('PT Uji', $form->fresh()->customer_name);

        $this->actingAs($this->admin)->get(route('form.show', $form))->assertDontSee('Edit Form');
    }

    public function test_samples_from_another_form_are_rejected(): void
    {
        $ph = $this->makeParameter('pH');
        $form = $this->createForm([$ph]);
        $other = $this->createForm([$ph]);
        $foreignSample = $other->samples[0];

        $this->actingAs($this->admin)->put(route('form.update', $form), $this->payload($form, [], function ($rows) use ($foreignSample) {
            $rows[0]['id'] = $foreignSample->id;
            $rows[0]['sample_code'] = 'HACKED';
            return $rows;
        }))->assertStatus(422);

        $this->assertNotSame('HACKED', $foreignSample->fresh()->sample_code);
    }

    public function test_only_admin_can_edit(): void
    {
        $form = $this->createForm([$this->makeParameter('pH')]);

        $this->actingAs($this->kepalaDivisi)->get(route('form.edit', $form))->assertForbidden();
        $this->actingAs($this->analisA)->put(route('form.update', $form), $this->payload($form))->assertForbidden();
    }
}
