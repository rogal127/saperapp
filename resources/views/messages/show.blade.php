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
    .bubble-photo {
        max-width: 220px; max-height: 220px; display: block;
        border-radius: 0.75rem; cursor: pointer; object-fit: cover;
    }
    .bubble-photo + .bubble-text { margin-top: 0.375rem; }
    .bubble-audio { width: 220px; height: 36px; display: block; }
    .bubble-audio + .bubble-text { margin-top: 0.375rem; }
    #photo-preview-bar, #audio-preview-bar {
        display: none; align-items: center; gap: 0.625rem;
        padding: 0.625rem 1rem 0; background: #13131f; flex-shrink: 0;
    }
    #photo-preview-bar.active, #audio-preview-bar.active { display: flex; }
    #photo-preview-bar img {
        width: 52px; height: 52px; object-fit: cover; border-radius: 0.625rem;
    }
    #audio-preview-bar audio { flex: 1; height: 36px; }
    #photo-preview-remove, #audio-preview-remove {
        background: #2a2a3e; color: #e2e8f0; border: none; border-radius: 50%;
        width: 26px; height: 26px; font-size: 0.9rem; cursor: pointer; flex-shrink: 0;
    }
    #recording-bar {
        display: none; align-items: center; gap: 0.625rem;
        padding: 0.625rem 1rem 0; background: #13131f; flex-shrink: 0;
        color: #f87171; font-size: 0.8rem;
    }
    #recording-bar.active { display: flex; }
    #recording-dot {
        width: 8px; height: 8px; border-radius: 50%; background: #ef4444;
        animation: recording-pulse 1s infinite; flex-shrink: 0;
    }
    @keyframes recording-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
    #recording-cancel {
        background: #2a2a3e; color: #e2e8f0; border: none; border-radius: 50%;
        width: 26px; height: 26px; font-size: 0.9rem; cursor: pointer; margin-left: auto; flex-shrink: 0;
    }
    #chat-input-bar {
        border-top: 1px solid #2a2a3e;
        padding: 0.75rem 1rem;
        display: flex; gap: 0.625rem; align-items: flex-end;
        background: #13131f; flex-shrink: 0;
    }
    #attach-btn, #record-btn {
        width: 42px; height: 42px; border-radius: 50%;
        background: #2a2a3e; color: #e2e8f0; border: none;
        font-size: 1.2rem; cursor: pointer; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.15s;
    }
    #record-btn.recording { background: #ef4444; color: #fff; }
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
        <a href="{{ route('users.show', $other['id']) }}" class="flex items-center gap-3 flex-1 min-w-0">
            <div class="w-10 h-10 rounded-full bg-surface-card flex items-center justify-center font-bold text-amber-400 flex-shrink-0 overflow-hidden">
                @if(!empty($other['avatar_url']))
                    <img src="{{ $other['avatar_url'] }}" alt="" style="width:100%;height:100%;object-fit:cover">
                @else
                    {{ $initials }}
                @endif
            </div>
            <div>
                <div class="font-bold text-white truncate">{{ $other['name'] ?? 'Użytkownik' }}</div>
                @if(!empty($other['role_label']))
                    <div class="text-xs text-gray-500 leading-tight">{{ $other['role_label'] }}</div>
                @endif
            </div>
        </a>
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
                @if(!empty($msg['photo_url']))
                <img src="{{ $msg['photo_url'] }}" class="bubble-photo" onclick="openLightbox(this.src)">
                @endif
                @if(!empty($msg['audio_url']))
                <audio controls src="{{ $msg['audio_url'] }}" class="bubble-audio"></audio>
                @endif
                @if(!empty($msg['body']))
                <div class="bubble-text">{{ $msg['body'] }}</div>
                @endif
            </div>
            <div class="bubble-time">
                {{ \Carbon\Carbon::parse($msg['created_at'])->setTimezone('Europe/Warsaw')->format('d.m H:i') }}
            </div>
        </div>
        @empty
        <div style="text-align:center;color:#6b7280;font-size:0.8rem;margin-top:2rem">
            Brak wiadomości. Napisz pierwszą!
        </div>
        @endforelse
    </div>

    {{-- Podgląd wybranego zdjęcia --}}
    <div id="photo-preview-bar">
        <img id="photo-preview-img" src="" alt="">
        <button type="button" id="photo-preview-remove">✕</button>
    </div>

    {{-- Trwające nagrywanie głosu --}}
    <div id="recording-bar">
        <span id="recording-dot"></span>
        <span>Nagrywanie… <span id="recording-timer">00:00</span></span>
        <button type="button" id="recording-cancel">✕</button>
    </div>

    {{-- Podgląd nagranej wiadomości głosowej --}}
    <div id="audio-preview-bar">
        <audio id="audio-preview-player" controls></audio>
        <button type="button" id="audio-preview-remove">✕</button>
    </div>

    {{-- Pole wpisywania --}}
    <div id="chat-input-bar" class="safe-bottom">
        <input type="file" id="photo-input" accept="image/*" style="display:none">
        <button type="button" id="attach-btn">📷</button>
        <button type="button" id="record-btn">🎤</button>
        <textarea id="chat-input" rows="1" placeholder="Napisz wiadomość…"></textarea>
        <button id="send-btn" disabled>➤</button>
    </div>

</div>

@push('scripts')
<script>
const SEND_URL   = "{{ route('messages.send', $conversation['id']) }}";
const CSRF_TOKEN = '{{ csrf_token() }}';

const input       = document.getElementById('chat-input');
const sendBtn     = document.getElementById('send-btn');
const msgList     = document.getElementById('chat-messages');
const attachBtn   = document.getElementById('attach-btn');
const photoInput  = document.getElementById('photo-input');
const previewBar  = document.getElementById('photo-preview-bar');
const previewImg  = document.getElementById('photo-preview-img');
const previewRemove = document.getElementById('photo-preview-remove');
const recordBtn   = document.getElementById('record-btn');
const recordingBar = document.getElementById('recording-bar');
const recordingTimerEl = document.getElementById('recording-timer');
const recordingCancel = document.getElementById('recording-cancel');
const audioPreviewBar = document.getElementById('audio-preview-bar');
const audioPreviewPlayer = document.getElementById('audio-preview-player');
const audioPreviewRemove = document.getElementById('audio-preview-remove');

let selectedPhoto = null;
let selectedAudio = null;
let mediaRecorder = null;
let recordedChunks = [];
let recordingCancelled = false;
let recordingStartedAt = 0;
let recordingTimerInterval = null;

const AUDIO_MIME_CANDIDATES = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4'];

function pickAudioMimeType() {
    return AUDIO_MIME_CANDIDATES.find(type => window.MediaRecorder && MediaRecorder.isTypeSupported(type)) || '';
}

recordBtn.addEventListener('click', () => {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        return;
    }
    startRecording();
});

async function startRecording() {
    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
        appendSystemMsg('Nagrywanie głosu nie jest wspierane w tej przeglądarce.');
        return;
    }

    let stream;
    try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch (err) {
        appendSystemMsg('Brak dostępu do mikrofonu.');
        return;
    }

    const mimeType = pickAudioMimeType();
    mediaRecorder = mimeType ? new MediaRecorder(stream, { mimeType }) : new MediaRecorder(stream);
    recordedChunks = [];
    recordingCancelled = false;

    mediaRecorder.addEventListener('dataavailable', e => {
        if (e.data.size > 0) { recordedChunks.push(e.data); }
    });

    mediaRecorder.addEventListener('stop', () => {
        stream.getTracks().forEach(track => track.stop());
        clearInterval(recordingTimerInterval);
        recordingBar.classList.remove('active');
        recordBtn.classList.remove('recording');

        if (recordingCancelled) {
            recordingCancelled = false;
            return;
        }

        const blobType = mediaRecorder.mimeType || 'audio/webm';
        const extension = blobType.includes('mp4') ? 'm4a' : (blobType.includes('ogg') ? 'ogg' : 'webm');
        const blob = new Blob(recordedChunks, { type: blobType });
        if (blob.size === 0) { return; }

        selectedAudio = new File([blob], `voice-message.${extension}`, { type: blobType });
        audioPreviewPlayer.src = URL.createObjectURL(selectedAudio);
        audioPreviewBar.classList.add('active');
        updateSendButtonState();
    });

    mediaRecorder.start();
    recordBtn.classList.add('recording');
    recordingStartedAt = Date.now();
    recordingBar.classList.add('active');
    updateRecordingTimer();
    recordingTimerInterval = setInterval(updateRecordingTimer, 250);
}

function updateRecordingTimer() {
    const seconds = Math.floor((Date.now() - recordingStartedAt) / 1000);
    recordingTimerEl.textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
}

recordingCancel.addEventListener('click', () => {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        recordingCancelled = true;
        mediaRecorder.stop();
    }
});

audioPreviewRemove.addEventListener('click', () => {
    selectedAudio = null;
    audioPreviewPlayer.src = '';
    audioPreviewBar.classList.remove('active');
    updateSendButtonState();
});

msgList.scrollTop = msgList.scrollHeight;

input.addEventListener('input', () => {
    updateSendButtonState();
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 100) + 'px';
});

input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

sendBtn.addEventListener('click', sendMessage);

attachBtn.addEventListener('click', () => photoInput.click());

photoInput.addEventListener('change', () => {
    const file = photoInput.files[0];
    if (!file) return;

    selectedPhoto = file;
    previewImg.src = URL.createObjectURL(file);
    previewBar.classList.add('active');
    updateSendButtonState();
});

previewRemove.addEventListener('click', () => {
    selectedPhoto = null;
    photoInput.value = '';
    previewBar.classList.remove('active');
    updateSendButtonState();
});

function updateSendButtonState() {
    sendBtn.disabled = input.value.trim().length === 0 && !selectedPhoto && !selectedAudio;
}

function sendMessage() {
    const body = input.value.trim();
    const photo = selectedPhoto;
    const audio = selectedAudio;
    if (!body && !photo && !audio) return;

    sendBtn.disabled = true;
    input.value = '';
    input.style.height = 'auto';
    selectedPhoto = null;
    selectedAudio = null;
    photoInput.value = '';
    previewBar.classList.remove('active');
    audioPreviewBar.classList.remove('active');
    audioPreviewPlayer.src = '';

    appendBubble(
        body,
        photo ? URL.createObjectURL(photo) : null,
        audio ? URL.createObjectURL(audio) : null,
        true,
        new Date().toISOString(),
    );

    const formData = new FormData();
    if (body) { formData.append('body', body); }
    if (photo) { formData.append('photo', photo); }
    if (audio) { formData.append('audio', audio); }

    fetch(SEND_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: formData,
    })
    .then(r => r.ok ? r.json() : Promise.reject())
    .catch(() => appendSystemMsg('Nie udało się wysłać wiadomości.'));
}

function appendBubble(text, photoUrl, audioUrl, isMine, iso) {
    const wrapper = document.createElement('div');
    wrapper.style.cssText = `display:flex;flex-direction:column;align-items:${isMine ? 'flex-end' : 'flex-start'}`;
    const d = new Date(iso);
    const time = d.toLocaleDateString('pl', {day:'2-digit',month:'2-digit'}) + ' ' + d.toLocaleTimeString('pl', {hour:'2-digit',minute:'2-digit'});
    const photoHtml = photoUrl ? `<img src="${photoUrl}" class="bubble-photo" onclick="openLightbox(this.src)">` : '';
    const audioHtml = audioUrl ? `<audio controls src="${audioUrl}" class="bubble-audio"></audio>` : '';
    const bodyHtml = text ? `<div class="bubble-text">${escHtml(text)}</div>` : '';
    wrapper.innerHTML = `
        <div class="bubble ${isMine ? 'bubble-mine' : 'bubble-other'}">${photoHtml}${audioHtml}${bodyHtml}</div>
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
