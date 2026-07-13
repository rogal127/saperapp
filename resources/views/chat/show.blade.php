@extends('layouts.app')
@section('title', 'Czat ogólny')

@push('styles')
<style>
    #chat-messages {
        flex: 1; overflow-y: auto; padding: 1rem;
        display: flex; flex-direction: column; gap: 0.625rem;
    }
    .bubble-row { display: flex; flex-direction: column; max-width: 78%; }
    .bubble-row.mine { align-self: flex-end; align-items: flex-end; }
    .bubble-row.other { align-self: flex-start; align-items: flex-start; }
    .bubble-sender { font-size: 0.7rem; font-weight: 700; color: #f59e0b; margin-bottom: 2px; padding: 0 0.25rem; }
    .bubble {
        padding: 0.625rem 0.875rem;
        border-radius: 1.125rem; font-size: 0.875rem; line-height: 1.4;
        word-break: break-word;
    }
    .bubble-mine {
        background: #f59e0b; color: #1a1a2e; font-weight: 500;
        border-bottom-right-radius: 0.25rem;
    }
    .bubble-other {
        background: #2a2a3e; color: #e2e8f0;
        border-bottom-left-radius: 0.25rem;
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
    <div class="flex items-center gap-3 px-4 pt-4 pb-3 border-b border-surface-card flex-shrink-0">
        <a href="{{ route('messages.index') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-surface-card text-gray-300 text-xl flex-shrink-0">‹</a>
        <div class="w-10 h-10 rounded-xl bg-surface-card flex items-center justify-center text-xl flex-shrink-0">💬</div>
        <div>
            <div class="font-bold text-white">Czat ogólny</div>
            <div class="text-xs text-gray-500 leading-tight">Widoczny dla wszystkich zalogowanych</div>
        </div>
    </div>

    {{-- Wiadomości --}}
    <div id="chat-messages">
        @php $myId = session('api_user.id') ?? session('api_user')['id'] ?? null; @endphp
        @forelse($messages as $msg)
            @include('chat.partials.message', ['msg' => $msg, 'myId' => $myId])
        @empty
        <div style="text-align:center;color:#6b7280;font-size:0.8rem;margin-top:2rem">
            Brak wiadomości. Napisz pierwszą!
        </div>
        @endforelse
    </div>

    {{-- Pole wpisywania --}}
    <div id="chat-input-bar" class="safe-bottom">
        <textarea id="chat-input" rows="1" placeholder="Napisz wiadomość…" maxlength="1000"></textarea>
        <button id="send-btn" disabled>➤</button>
    </div>

</div>

@push('scripts')
<script>
const SEND_URL = "{{ route('chat.send') }}";
const POLL_URL = "{{ route('chat.messages') }}";
const CSRF_TOKEN = '{{ csrf_token() }}';
const MY_ID = {{ $myId !== null ? (int) $myId : 'null' }};

const input   = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-btn');
const msgList = document.getElementById('chat-messages');

let lastId = 0;
document.querySelectorAll('[data-msg-id]').forEach(el => {
    lastId = Math.max(lastId, parseInt(el.dataset.msgId, 10));
});

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

    fetch(SEND_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify({ body }),
    })
    .then(r => r.ok ? r.json() : Promise.reject())
    .then(data => appendMessage(data))
    .catch(() => appendSystemMsg('Nie udało się wysłać wiadomości.'))
    .finally(() => { sendBtn.disabled = input.value.trim().length === 0; });
}

function poll() {
    fetch(POLL_URL + '?after_id=' + lastId)
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(data => (data.data || []).forEach(appendMessage))
        .catch(() => {});
}

function appendMessage(msg) {
    if (!msg || !msg.id || msg.id <= lastId) return;
    lastId = msg.id;

    const isMine = MY_ID !== null && String(msg.user_id) === String(MY_ID);
    const wrapper = document.createElement('div');
    wrapper.className = 'bubble-row ' + (isMine ? 'mine' : 'other');
    wrapper.dataset.msgId = msg.id;

    const d = new Date(msg.created_at);
    const time = d.toLocaleDateString('pl', {day:'2-digit',month:'2-digit'}) + ' ' + d.toLocaleTimeString('pl', {hour:'2-digit',minute:'2-digit'});
    const senderName = msg.user?.name ?? 'Użytkownik';

    wrapper.innerHTML = `
        ${!isMine ? `<div class="bubble-sender">${escHtml(senderName)}</div>` : ''}
        <div class="bubble ${isMine ? 'bubble-mine' : 'bubble-other'}">${escHtml(msg.body)}</div>
        <div class="bubble-time">${time}</div>`;

    msgList.appendChild(wrapper);
    msgList.scrollTop = msgList.scrollHeight;
}

function appendSystemMsg(text) {
    const el = document.createElement('div');
    el.style.cssText = 'text-align:center;font-size:0.72rem;color:#f87171;padding:0.25rem 0';
    el.textContent = text;
    msgList.appendChild(el);
    msgList.scrollTop = msgList.scrollHeight;
}

function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

const pollTimer = setInterval(poll, 4000);
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) { poll(); }
});
</script>
@endpush
@endsection
