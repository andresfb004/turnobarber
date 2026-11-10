<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ClienteRequest;
use App\Models\Cliente;
use App\Repositories\ClienteRepository;
use App\Services\ClienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function __construct(
        private ClienteService $servicio,
        private ClienteRepository $clientes,
    ) {}

    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('q'));
        $estado = $request->query('estado', '');

        $clientes = Cliente::withCount('citas')
            ->when($busqueda, fn ($q) => $q->where(fn ($q) => $q
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhere('telefono', 'like', "%{$busqueda}%")))
            ->when($estado === 'bloqueados', fn ($q) => $q->where('bloqueado', true))
            ->when($estado === 'telegram', fn ($q) => $q->whereNotNull('telegram_id'))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('clientes.index', compact('clientes', 'busqueda'));
    }

    public function show(Cliente $cliente): View
    {
        $citas = $cliente->citas()->with('profesional')->orderByDesc('inicio')->paginate(10);

        return view('clientes.show', compact('cliente', 'citas'));
    }

    public function create(): View
    {
        return view('clientes.form', ['cliente' => new Cliente]);
    }

    public function store(ClienteRequest $request): RedirectResponse
    {
        $cliente = $this->clientes->crear($request->validated());

        return redirect()->route('clientes.show', $cliente)
            ->with('exito', 'Cliente registrado. Cuando escriba al bot y comparta este número, su cuenta de Telegram quedará vinculada.');
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.form', compact('cliente'));
    }

    public function update(ClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $this->clientes->actualizar($cliente, $request->validated());

        return redirect()->route('clientes.show', $cliente)->with('exito', 'Cliente actualizado.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $this->servicio->eliminar($cliente);

        return redirect()->route('clientes.index')->with('exito', 'Cliente eliminado.');
    }

    public function desbloquear(Cliente $cliente): RedirectResponse
    {
        $this->servicio->desbloquear($cliente);

        return back()->with('exito', "{$cliente->nombre} fue desbloqueado y puede volver a agendar.");
    }
}
