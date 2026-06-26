<?php

namespace App\Http\Controllers\Api\Agence;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use Illuminate\Http\Request;

abstract class AgenceApiController extends Controller
{
    protected function agence(Request $request): Agence
    {
        return $request->user()->agence;
    }
}
