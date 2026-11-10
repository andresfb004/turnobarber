<form method="POST" action="{{ $ruta }}" class="d-inline" onsubmit="return confirm(@js($pregunta))">
    @csrf
    @method('DELETE')
    <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
</form>
