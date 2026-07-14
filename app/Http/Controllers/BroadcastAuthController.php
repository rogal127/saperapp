<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class BroadcastAuthController extends Controller
{
    public function authorize(Request $request): Response
    {
        $response = Http::withToken($request->session()->get('api_token', ''))
            ->asForm()
            ->post(config('services.api.url').'/broadcasting/auth', $request->only(['channel_name', 'socket_id']));

        return response($response->body(), $response->status())
            ->header('Content-Type', 'application/json');
    }
}
