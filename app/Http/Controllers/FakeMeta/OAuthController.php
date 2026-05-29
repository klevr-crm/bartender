<?php

declare(strict_types=1);

namespace App\Http\Controllers\FakeMeta;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OAuthController extends Controller
{
    public function authorize(Request $request): View
    {
        $redirectUri = (string) $request->query('redirect_uri', '');
        $state = (string) $request->query('state', '');
        $code = 'fake_code_'.uniqid();

        $redirectUrl = $redirectUri !== ''
            ? $redirectUri.'?code='.$code.($state !== '' ? '&state='.$state : '')
            : '';

        return view('fake-meta.consent', [
            'redirectUrl' => $redirectUrl,
            'state' => $state,
        ]);
    }

    public function accessToken(Request $request): JsonResponse
    {
        return response()->json([
            'access_token' => config('bartender.fake_meta.access_token'),
            'token_type' => 'bearer',
            'expires_in' => 5_184_000,
        ]);
    }
}
