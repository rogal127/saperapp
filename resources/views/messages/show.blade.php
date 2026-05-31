@extends('layouts.app')
@section('title', 'Konwersacja')

@push('styles')
<style>
    #chat-messages {
        flex: 1; overflow-y: auto; padding: 1rem;
        display: flex; flex-direction: column; gap: 0.625rem;
    }
    .bubble {
        max-width: 78%; padding: 0.625rem 0.875rem;
        border-radius: 1.125rem; font-size: 0.875rem; line-height: 1.4;
        word-break: break-word;
    }
    .bubble-mine {
        background: #f59e0b; color: #1a1a2e; font-weight: 500;
        align-self: flex-end; border-bottom-right-radius: 0.25rem;
    }
    .bubble-other {
        background: #2a2a3e; color: #e2e8f0;
        align-self: flex-start; border-bottom-left-radius: 0.25rem;
    }
    .bubble-time { font-size: 0.6rem; color: #6b7280; margin-top: 2px; }
    #chat-input-bar {
        border-top: 1px solid #2a2a3e;
        padding: 0.75rem 1rem;
        display: flex; gap: 0.625rem; align-items: flex-end;
        background: #13131f; flex-shrink: 0;
    }
    #chat-input {
        flex: 1; background: #2a2a3e; border: 1px solid #404060;
        border-radius: 1.25rem; color: #e2e8f0;
        padding: 0.625rem 0.875rem; font-size: 0.875rem;
        resize: none; outline: none; font-family: inherit;
        max-height: 100px; overflow-y: auto; line-height: 1.4;
    }
    #chat-input:focus { border-color: #f59e0b; }
    #send-btn {
        width: 42px; height: 42px; border-radius: 50%;
        background: #f59e0b; color: #1a1a2e; border: none;
        font-size: 1.1rem; cursor: pointer; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        transition: opacity 0.15s;
    }
    #send-btn:disabled { opacity: 0.4; cursor: not-allowed; }
</style>
@endpush

@section('content')
<div class="flex flex-col h-full safe-top">

    {{-- Header --}}
    @php
        $other = $conversation['other_user'] ?? [];
        $initials = strtoupper(substr($other['name'] ?? '?', 0, 1));
    @endphp
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <a href="{{ route('messages.index') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</a>
        <div class="w-10 h-10 rounded-full bg-surface-card flex items-center justify-center font-bold text-amber-400 flex-shrink-0 overflow-hidden">
            @if(!empty($other['avatar_url']))
                <img src="{{ $other['avatar_url'] }}" alt="" style="width:100%;height:100%;object-fit:cover">
            @else
                {{ $initials }}
            @endif
        </div>
        <div class="font-bold text-white truncate">{{ $other['name'] ?? 'Użytkownik' }}</div>
    </div>

    {{-- Wiadomości --}}
    <div id="chat-messages">
        @php $myId = session('api_user.id') ?? session('api_user')['id'] ?? null; @endphp
        @forelse($messages as $msg)
        @php $isMine = (string)($msg['sender_id'] ?? '') === (string)$myId; @endphp
        <div style="display:flex;flex-direction:column;align-items:{{ $isMine ? 'flex-end' : 'flex-start' }}">
            @if(!empty($msg['finding']))
            <a href="{{ route('findings.show', $msg['finding']['id']) }}"
               style="display:flex;align-items:center;gap:0.5rem;background:#2a2a3e;border:1px solid #f59e0b33;border-radius:0.875rem;padding:0.5rem 0.75rem;margin-bottom:0.25rem;text-decoration:none;max-width:78%">
                <span style="font-size:1rem">🪙</span>
                <span style="font-size:0.75rem;font-weight:700;color:#f59e0b;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $msg['finding']['name'] }}</span>
                <span style="font-size:0.7rem;color:#6b7280;flex-shrink:0">›</span>
            </a>
            @endif
            <div class="bubble {{ $isMine ? 'bubble-mine' : 'bubble-other' }}">
                {{ $msg['body'] ?? '' }}
            </div>
            <div class="bubble-time">
                {{ \Carbon\Carbon::parse($msg['created_at'])->format('d.m H:i') }}
            </div>
        </div>
        @empty
        <div style="text-align:center;color:#6b7280;font-size:0.8rem;margin-top:2rem">
            Brak wiadomości. Napisz pierwszą!
        </div>
        @endforelse
    </div>

    {{-- Pole wpisywania --}}
    <div id="chat-input-bar" class="safe-bottom">
        <textarea id="chat-input" rows="1" placeholder="Napisz wiadomość…"></textarea>
        <button id="send-btn" disabled>➤</button>
    </div>

</div>

@push('scripts')
<script>
const SEND_URL   = "{{ route('messages.send', $conversation['id']) }}";
const CSRF_TOKEN = '{{ csrf_token() }}';

const input   = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-btn');
const msgList = document.getElementById('chat-messages');

msgList.scrollTop = msgList.scrollHeight;

input.addEventListener('input', () => {
    sendBtn.disabled = input.value.trim().length === 0;
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 100) + 'px';
});

input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

sendBtn.addEventListener('click', sendMessage);

function sendMessage() {
    const body = input.value.trim();
    if (!body) return;

    sendBtn.disabled = true;
    input.value = '';
    input.style.height = 'auto';

    appendBubble(body, true, new Date().toISOString());

    fetch(SEND_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ body }),
    })
    .then(r => r.ok ? r.json() : Promise.reject())
    .catch(() => appendSystemMsg('Nie udało się wysłać wiadomości.'));
}

function appendBubble(text, isMine, iso) {
    const wrapper = document.createElement('div');
    wrapper.style.cssText = `display:flex;flex-direction:column;align-items:${isMine ? 'flex-end' : 'flex-start'}`;
    const d = new Date(iso);
    const time = d.toLocaleDateString('pl', {day:'2-digit',month:'2-digit'}) + ' ' + d.toLocaleTimeString('pl', {hour:'2-digit',minute:'2-digit'});
    wrapper.innerHTML = `
        <div class="bubble ${isMine ? 'bubble-mine' : 'bubble-other'}">${escHtml(text)}</div>
        <div class="bubble-time">${time}</div>`;
    msgList.appendChild(wrapper);
    msgList.scrollTop = msgList.scrollHeight;
    sendBtn.disabled = false;
}

function appendSystemMsg(text) {
    const el = document.createElement('div');
    el.style.cssText = 'text-align:center;font-size:0.72rem;color:#f87171;padding:0.25rem 0';
    el.textContent = text;
    msgList.appendChild(el);
    msgList.scrollTop = msgList.scrollHeight;
    sendBtn.disabled = false;
}

function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush
@endsection
