<?php

namespace App\Http\Controllers;

use App\Models\FormPengujian;
use App\Models\Sample;
use Illuminate\Http\Request;

class SampleController extends Controller
{
    public function index(FormPengujian $form)
    {
        return view('samples.index', [
            'form' => $form,
            'samples' => $form->samples,
        ]);
    }

    public function store(Request $request, FormPengujian $form)
    {
        $validated = $request->validate([
            'sample_code' => 'required',
            'sample_name' => 'required',
            'description' => 'nullable',
        ]);

        Sample::create([
            'form_pengujian_id' => $form->id,
            'sample_code' => $validated['sample_code'],
            'sample_name' => $validated['sample_name'],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Sample added');
    }
    public function create(FormPengujian $form)
    {
        return view('samples.create', compact('form'));
    }

    public function edit(Sample $sample)
    {
        return view('samples.edit', compact('sample'));
    }

    public function update(Request $request, Sample $sample)
    {
        $validated = $request->validate([
            'sample_name'     => 'required|string',
            'sample_quantity' => 'required|integer|min:1',
        ]);

        $sample->update($validated);

        return redirect()->route('form.show', $sample->form_pengujian_id)
            ->with('success', 'Sample updated');
    }

    public function destroy(Sample $sample)
    {
        $formId = $sample->form_pengujian_id;
        $sample->delete();

        return redirect()->route('form.show', $formId)
            ->with('success', 'Sample deleted');
    }

}
