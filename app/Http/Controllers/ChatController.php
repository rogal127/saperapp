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
        $hasMore = $response->successful() ? $response->json('has_more', false) : false;

        return view('chat.show', compact('messages', 'hasMore'));
    }

    public function messages(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url').'/chat/messages', [
                'after_id' => $request->query('after_id'),
                'before_id' => $request->query('before_id'),
            ]);

        $messages = $response->successful() ? $response->json('data', []) : [];
        $hasMore = $response->successful() ? $response->json('has_more', false) : false;

        return response()->json(['data' => $messages, 'has_more' => $hasMore]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'body' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:10240'],
            'audio' => ['nullable', 'file', 'max:10240', function ($attribute, $value, $fail) {
                if (! in_array(strtolower($value->getClientOriginalExtension()), ['webm', 'ogg', 'oga', 'm4a', 'mp4', 'mp3', 'wav'])) {
                    $fail('Pole audio musi być plikiem audio (webm, ogg, m4a, mp4, mp3 lub wav).');
                }
            }],
        ]);

        if (! $request->filled('body') && ! $request->hasFile('photo') && ! $request->hasFile('audio')) {
            return response()->json(['message' => 'Wiadomość musi zawierać tekst, zdjęcie lub nagranie głosowe.'], 422);
        }

        $httpRequest = Http::withToken($this->apiToken($request));

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $httpRequest = $httpRequest->attach('photo', file_get_contents($photo->getRealPath()), $photo->getClientOriginalName());
        }

        if ($request->hasFile('audio')) {
            $audio = $request->file('audio');
            $httpRequest = $httpRequest->attach('audio', file_get_contents($audio->getRealPath()), $audio->getClientOriginalName());
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
