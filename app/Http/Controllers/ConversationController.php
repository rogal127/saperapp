<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ConversationController extends Controller
{
    private function apiToken(Request $request): string
    {
        return $request->session()->get('api_token', '');
    }

    public function index(Request $request)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url') . '/conversations');

        $conversations = $response->successful() ? $response->json('data', []) : [];

        return view('messages.index', compact('conversations'));
    }

    public function show(Request $request, int $id)
    {
        $response = Http::withToken($this->apiToken($request))
            ->get(config('services.api.url') . "/conversations/{$id}");

        if ($response->failed()) {
            return redirect()->route('messages.index');
        }

        $data         = $response->json('data');
        $conversation = $data;
        $messages     = $data['messages'] ?? [];

        return view('messages.show', compact('conversation', 'messages'));
    }

    public function send(Request $request, int $id)
    {
        $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ]);

        $response = Http::withToken($this->apiToken($request))
            ->post(config('services.api.url') . "/conversations/{$id}/messages", [
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
            ->get(config('services.api.url') . '/conversations/unread-count');

        $count = $response->successful() ? $response->json('data.unread_count', 0) : 0;

        return response()->json(['count' => $count]);
    }
}
