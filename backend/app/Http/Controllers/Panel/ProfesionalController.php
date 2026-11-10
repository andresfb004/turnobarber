<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ProfesionalRequest;
use App\Models\Profesional;
use App\Models\Role;
use App\Services\ProfesionalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfesionalController extends Controller
{
    public function __construct(private ProfesionalService $profesionales) {}

    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('q'));

        $profesionales = Profesional::with(['user.role', 'horarios'])
            ->withCount('citas')
            ->when($busqueda, fn ($q) => $q->where(fn ($q) => $q
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$busqueda}%"))))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('profesionales.index', compact('profesionales', 'busqueda'));
    }

    public function create(): View
    {
        return view('profesionales.form', ['profesional' => new Profesional(['activo' => true]), 'roles' => $this->roles()]);
    }

    public function store(ProfesionalRequest $request): RedirectResponse
    {
        $this->profesionales->crear($request->datos());

        return redirect()->route('profesionales.index')->with('exito', 'Profesional creado.');
    }

    public function edit(Profesional $profesional): View
    {
        return view('profesionales.form', ['profesional' => $profesional->load('user.role', 'horarios'), 'roles' => $this->roles()]);
    }

    public function update(ProfesionalRequest $request, Profesional $profesional): RedirectResponse
    {
        $this->profesionales->actualizar($profesional, $request->datos());

        return redirect()->route('profesionales.index')->with('exito', 'Profesional actualizado.');
    }

    public function destroy(Request $request, Profesional $profesional): RedirectResponse
    {
        $this->profesionales->eliminar($profesional, $request->user());

        return redirect()->route('profesionales.index')->with('exito', 'Profesional eliminado.');
    }

    private function roles(): array
    {
        return [Role::ADMINISTRADOR, Role::PROFESIONAL];
    }
}
