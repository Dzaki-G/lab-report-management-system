<?php

namespace Tests\Feature;

use App\Models\FormPengujian;
use App\Models\Parameter;
use App\Models\SampleParameter;
use App\Models\Sp3Document;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Base for the lab workflow tests: fresh in-memory DB with roles seeded,
 * no Vite, and Bus::fake() so GenerateSp3Job / GenerateLhpJob never call Google.
 */
abstract class LabTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $analisA;
    protected User $analisB;
    protected User $kepalaDivisi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->withoutVite();
        Bus::fake();

        $this->admin        = User::factory()->admin()->create(['full_name' => 'Admin Lab']);
        $this->analisA      = User::factory()->analis()->create(['full_name' => 'Analis A']);
        $this->analisB      = User::factory()->analis()->create(['full_name' => 'Analis B']);
        $this->kepalaDivisi = User::factory()->kepalaDivisi()->create(['full_name' => 'Kadiv']);
    }

    protected function makeParameter(string $name): Parameter
    {
        return Parameter::create(['name' => $name, 'default_unit' => 'mg/L', 'default_method' => 'SNI']);
    }

    /**
     * Create a form through the real admin route. Every sample gets every parameter,
     * so there is one SP3 per parameter covering all samples.
     */
    protected function createForm(array $parameters, int $sampleCount = 1): FormPengujian
    {
        $samples = [];
        for ($i = 1; $i <= $sampleCount; $i++) {
            $samples[] = [
                'sample_code' => sprintf('%03d/TEST', $i),
                'sample_name' => "Sampel {$i}",
                'parameters'  => collect($parameters)->pluck('id')->all(),
            ];
        }

        $this->actingAs($this->admin)->post(route('form.store'), [
            'received_date' => now()->toDateString(),
            'customer_name' => 'PT Uji',
            'samples'       => $samples,
        ])->assertRedirect(route('form.index'));

        return FormPengujian::latest('id')->firstOrFail();
    }

    protected function sp3For(FormPengujian $form, Parameter $parameter): Sp3Document
    {
        return Sp3Document::where('form_pengujian_id', $form->id)
            ->where('parameter_id', $parameter->id)
            ->firstOrFail();
    }

    /** Fill every sample row of an SP3 as the given analyst. */
    protected function fillSp3(Sp3Document $sp3, User $analyst, string $value = '7.1'): void
    {
        $sp3->sampleParametersQuery()->get()->each(function (SampleParameter $sp) use ($analyst, $value) {
            $this->actingAs($analyst)->post(route('analis.store', $sp), [
                'result_value'  => $value,
                'result_unit'   => 'mg/L',
                'instrument'    => 'pH meter',
                'method'        => 'SNI',
                'analysis_date' => now()->toDateString(),
            ])->assertSessionHasNoErrors();
        });
    }
}
