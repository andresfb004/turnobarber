<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\CierreRequest;
use App\Models\Cierre;
use App\Models\Profesional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CierreController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('q'));
        $periodo = $request->query('periodo', 'proximos');
        $hoy = Carbon::today()->toDateString();

        $cierres = Cierre::with('profesionales')
            ->when($busqueda, fn ($q) => $q->where('motivo', 'like', "%{$busqueda}%"))
            ->when($periodo === 'proximos', fn ($q) => $q->whereDate('fecha', '>=', $hoy)->orderBy('fecha'))
            ->when($periodo === 'pasados', fn ($q) => $q->whereDate('fecha', '<', $hoy)->orderByDesc('fecha'))
            ->when($periodo === 'todos', fn ($q) => $q->orderByDesc('fecha'))
            ->paginate(10)
            ->withQueryString();

        return view('cierres.index', compact('cierres', 'busqueda'));
    }

    public function create(): View
    {
        return view('cierres.form', [
            'cierre' => new Cierre(['fecha' => Carbon::tomorrow()]),
            'profesionales' => Profesional::orderBy('id')->get(),
        ]);
    }

    public function store(CierreRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            Cierre::create($request->datosCierre())->profesionales()->sync($request->validated('profesionales'));
        });

        return redirect()->route('cierres.index')->with('exito', 'Cierre creado. El bot ya no ofrece esos turnos.');
    }

    public function edit(Cierre $cierre): View
    {
        return view('cierres.form', [
            'cierre' => $cierre->load('profesionales'),
            'profesionales' => Profesional::orderBy('id')->get(),
        ]);
    }

    public function update(CierreRequest $request, Cierre $cierre): RedirectResponse
    {
        DB::transaction(function () use ($request, $cierre) {
            $cierre->update($request->datosCierre());
            $cierre->profesionales()->sync($request->validated('profesionales'));
        });

        return redirect()->route('cierres.index')->with('exito', 'Cierre actualizado.');
    }

    public function destroy(Cierre $cierre): RedirectResponse
    {
        $cierre->delete();

        return redirect()->route('cierres.index')->with('exito', 'Cierre eliminado.');
    }
}
