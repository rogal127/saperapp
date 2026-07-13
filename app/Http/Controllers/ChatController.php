<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    private function apiToken(Request $request): string
    {
        return $request->session()->get('api_token', '');
    }

    public function show(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url').'/chat/messages');

        $messages = $response->successful() ? $response->json('data', []) : [];

        return view('chat.show', compact('messages'));
    }

    public function messages(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url').'/chat/messages', [
                'after_id' => $request->query('after_id'),
            ]);

        $messages = $response->successful() ? $response->json('data', []) : [];

        return response()->json(['data' => $messages]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'body' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:10240'],
        ]);

        if (! $request->filled('body') && ! $request->hasFile('photo')) {
            return response()->json(['message' => 'Wiadomość musi zawierać tekst lub zdjęcie.'], 422);
        }

        $httpRequest = Http::withToken($this->apiToken($request));

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $httpRequest = $httpRequest->attach('photo', file_get_contents($photo->getRealPath()), $photo->getClientOriginalName());
        }

        $response = $httpRequest->post(config('services.api.url').'/chat/messages', [
            'body' => $request->body,
        ]);

        if ($response->failed()) {
            return response()->json(['message' => 'Błąd wysyłania wiadomości.'], 502);
        }

        return response()->json($response->json('data'), 201);
    }

    public function unreadCount(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url').'/chat/unread-count');

        $count = $response->successful() ? $response->json('data.unread_count', 0) : 0;

        return response()->json(['count' => $count]);
    }
}
