@php
    $isMine = (string) ($msg['user_id'] ?? '') === (string) $myId;
    $senderName = $msg['user']['name'] ?? 'Użytkownik';
    $avatarUrl = $msg['user']['avatar_thumb_url'] ?? $msg['user']['avatar_url'] ?? null;
    $initials = strtoupper(substr($senderName, 0, 1));
    $replyTo = $msg['reply_to'] ?? null;
    $replyPreview = $replyTo
        ? (filled($replyTo['body'] ?? null)
            ? \Illuminate\Support\Str::limit($replyTo['body'], 80)
            : (($replyTo['has_photo'] ?? false) ? '📷 Zdjęcie' : (($replyTo['has_audio'] ?? false) ? '🎤 Wiadomość głosowa' : 'Wiadomość')))
        : null;
@endphp
<div class="msg-line {{ $isMine ? 'mine' : '' }}" data-msg-id="{{ $msg['id'] }}" data-sender="{{ $senderName }}">
    @unless($isMine)
    <a href="{{ route('users.show', $msg['user_id']) }}" class="msg-avatar">
        @if($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="">
        @else
            {{ $initials }}
        @endif
    </a>
    @endunless
    <div class="bubble-row {{ $isMine ? 'mine' : 'other' }}">
        @unless($isMine)
        <a href="{{ route('users.show', $msg['user_id']) }}" class="bubble-sender">{{ $senderName }}</a>
        @endunless
        @if($replyTo)
        <div class="reply-quote" data-reply-to="{{ $replyTo['id'] }}">
            <div class="reply-quote-author">↩ {{ $replyTo['user_name'] }}</div>
            <div class="reply-quote-body">{{ $replyPreview }}</div>
        </div>
        @endif
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
            <img src="{{ $msg['photo_thumb_url'] ?? $msg['photo_url'] }}" class="bubble-photo" onclick="openLightbox('{{ $msg['photo_url'] }}')">
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
</div>
