@include('errors.layout', ['codigo' => 403, 'titulo' => 'Acceso denegado', 'mensaje' => $exception->getMessage() ?: 'No tienes permiso para ver esta página.'])
