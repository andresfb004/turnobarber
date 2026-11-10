<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfesionalResource;
use App\Repositories\ProfesionalRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfesionalController extends Controller
{
    public function index(ProfesionalRepository $profesionales): AnonymousResourceCollection
    {
        return ProfesionalResource::collection($profesionales->activos());
    }
}
