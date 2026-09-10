<?php

namespace App\Http\Controllers;

use App\Models\Parameter;
use Illuminate\Http\Request;

class ParameterController extends Controller
{
    public function index()
    {
        $parameters = Parameter::orderBy('name')->get();
        return view('parameters.index', compact('parameters'));
    }

    public function create()
    {
        return view('parameters.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|unique:parameters,name',
            'instrument'     => 'nullable|string|max:500',
            'category'       => 'nullable|string',
            'default_method' => 'nullable|string',
            'default_unit'   => 'nullable|string',
        ]);

        Parameter::create($validated);

        return redirect()->route('parameters.index')
            ->with('success', 'Parameter berhasil ditambahkan');
    }

    public function edit(Parameter $parameter)
    {
        return view('parameters.edit', compact('parameter'));
    }

    public function update(Request $request, Parameter $parameter)
    {
        $validated = $request->validate([
            'name'           => 'required|string|unique:parameters,name,' . $parameter->id,
            'instrument'     => 'nullable|string|max:500',
            'category'       => 'nullable|string',
            'default_method' => 'nullable|string',
            'default_unit'   => 'nullable|string',
        ]);

        $parameter->update($validated);

        return redirect()->route('parameters.index')
            ->with('success', 'Parameter berhasil diupdate');
    }

    public function toggle(Parameter $parameter)
    {
        $parameter->update(['is_active' => !$parameter->is_active]);

        $status = $parameter->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('parameters.index')
            ->with('success', "Parameter berhasil {$status}");
    }

    public function destroy(Parameter $parameter)
    {
        // Check if parameter is being used
        if ($parameter->sampleParameters()->exists()) {
            return redirect()->route('parameters.index')
                ->with('error', 'Parameter tidak bisa dihapus karena sedang digunakan');
        }

        $parameter->delete();

        return redirect()->route('parameters.index')
            ->with('success', 'Parameter berhasil dihapus');
    }
}
