<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

abstract class ClientApiController extends Controller
{
    protected function client(Request $request): Client
    {
        return $request->user()->client;
    }
}
