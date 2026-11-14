<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateIndicatorRequest;
use App\Models\Indicator;
use Illuminate\Http\RedirectResponse;

class IndicatorController extends Controller
{
    public function update(UpdateIndicatorRequest $request, Indicator $indicator): RedirectResponse
    {
        $indicator->update($request->validated());

        return back()->with('success', 'Nome do indicador atualizado.');
    }

    public function destroy(Indicator $indicator): RedirectResponse
    {
        $name = $indicator->name;
        $indicator->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', "Indicador {$name} excluído.");
    }
}
