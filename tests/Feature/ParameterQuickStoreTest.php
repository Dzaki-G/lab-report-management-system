<?php

namespace Tests\Feature;

use App\Models\Parameter;

class ParameterQuickStoreTest extends LabTestCase
{
    public function test_create_form_page_has_the_quick_add_button_and_modal(): void
    {
        $this->makeParameter('pH');

        $this->actingAs($this->admin)->get(route('form.create'))
            ->assertOk()
            ->assertSee('add-parameter-btn', false)
            ->assertSee('id="parameterModal"', false)
            // The JS gets the URL via @json(), which escapes slashes
            ->assertSee(json_encode(route('parameters.quick-store')), false);
    }

    public function test_admin_can_quick_add_a_parameter_as_json(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('parameters.quick-store'), ['name' => 'Kadar Air', 'default_unit' => '%'])
            ->assertCreated()
            ->assertJsonStructure(['id', 'name'])
            ->assertJson(['name' => 'Kadar Air']);

        $parameter = Parameter::where('name', 'Kadar Air')->first();
        $this->assertNotNull($parameter);
        $this->assertTrue($parameter->is_active);
        $this->assertSame('%', $parameter->default_unit);
    }

    public function test_duplicate_name_returns_a_validation_error(): void
    {
        $this->makeParameter('pH');

        $this->actingAs($this->admin)
            ->postJson(route('parameters.quick-store'), ['name' => 'pH'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->assertSame(1, Parameter::where('name', 'pH')->count());
    }

    public function test_quick_added_parameter_can_be_used_in_a_new_form(): void
    {
        $id = $this->actingAs($this->admin)
            ->postJson(route('parameters.quick-store'), ['name' => 'Kadar Air'])
            ->json('id');

        $form = $this->createForm([Parameter::find($id)]);

        $this->assertSame([$id], $form->sp3Documents->pluck('parameter_id')->all());
    }

    public function test_non_admin_cannot_quick_add(): void
    {
        $this->actingAs($this->analisA)
            ->postJson(route('parameters.quick-store'), ['name' => 'X'])
            ->assertForbidden();
    }
}
