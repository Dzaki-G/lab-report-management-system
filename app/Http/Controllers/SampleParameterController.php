<?php

namespace App\Http\Controllers;

use App\Models\SampleParameter;
use App\Models\User;
use App\Enums\Role;
use Illuminate\Http\Request;

class SampleParameterController extends Controller
{
    /**
     * Show form to assign analis to a sample parameter
     */
    public function assignForm(SampleParameter $sampleParameter)
    {
        $sampleParameter->load(['sample.form', 'parameter', 'assignedAnalyst']);

        // Get all active analis users
        $analysts = User::where('role_id', Role::ANALIS)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        return view('sample-parameters.assign', compact('sampleParameter', 'analysts'));
    }

    /**
     * Assign analis to a sample parameter
     */
    public function assign(Request $request, SampleParameter $sampleParameter)
    {
        $validated = $request->validate([
            'assigned_analyst_id' => 'required|exists:users,user_id',
        ]);

        $sampleParameter->update([
            'assigned_analyst_id' => $validated['assigned_analyst_id'],
        ]);

        return redirect()->route('form.show', $sampleParameter->sample->form_pengujian_id)
            ->with('success', 'Analis berhasil ditugaskan');
    }
}
