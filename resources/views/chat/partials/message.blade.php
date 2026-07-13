@php
    $isMine = (string) ($msg['user_id'] ?? '') === (string) $myId;
    $senderName = $msg['user']['name'] ?? 'Użytkownik';
    $avatarUrl = $msg['user']['avatar_url'] ?? null;
    $initials = strtoupper(substr($senderName, 0, 1));
@endphp
<div class="msg-line {{ $isMine ? 'mine' : '' }}" data-msg-id="{{ $msg['id'] }}">
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
</div>
