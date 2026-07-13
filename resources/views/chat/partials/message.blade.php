@php
    $isMine = (string) ($msg['user_id'] ?? '') === (string) $myId;
    $senderName = $msg['user']['name'] ?? 'Użytkownik';
@endphp
<div class="bubble-row {{ $isMine ? 'mine' : 'other' }}" data-msg-id="{{ $msg['id'] }}">
    @unless($isMine)
    <div class="bubble-sender">{{ $senderName }}</div>
    @endunless
    <div class="bubble {{ $isMine ? 'bubble-mine' : 'bubble-other' }}">
        {{ $msg['body'] ?? '' }}
    </div>
    <div class="bubble-time">
        {{ \Carbon\Carbon::parse($msg['created_at'])->format('d.m H:i') }}
    </div>
</div>
