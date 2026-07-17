<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agence;

use App\Http\Controllers\Controller;
use App\Models\Agence;
use App\Models\AgenceUser;
use Illuminate\Http\Request;

abstract class AgenceApiController extends Controller
{
    protected function agenceUser(Request $request): AgenceUser
    {
        /** @var AgenceUser $user */
        $user = $request->user();

        return $user;
    }

    protected function agence(Request $request): Agence
    {
        return $this->agenceUser($request)->agence;
    }
}
