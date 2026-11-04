@extends('layouts.app')
@section('title', 'Voice to Chat')
@section('page-title', 'Aksesibilitas Suara')

@section('styles')
<style>
    .voice-page {
        padding: 30px;
        min-height: calc(100vh - var(--topbar-height) - 50px);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }
    .voice-header {
        margin-bottom: 26px;
    }
    .voice-header i {
        font-size: 48px;
        color: var(--primary);
        margin-bottom: 16px;
    }
    .voice-header p {
        color: var(--text-muted);
    }
    .voice-visualizer-large {
        margin-bottom: 28px;
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }
    .voice-visualizer-large .bar {
        display: none;
        width: 6px;
        border-radius: 3px;
        background: var(--primary);
    }
    .voice-visualizer-large.active .bar {
        display: block;
    }
    .voice-visualizer-large .bar:nth-child(1) { height: 20px; animation: sound 0.5s infinite alternate; }
    .voice-visualizer-large .bar:nth-child(2) { height: 40px; animation: sound 0.7s infinite alternate; }
    .voice-visualizer-large .bar:nth-child(3) { height: 30px; animation: sound 0.6s infinite alternate; }
    .voice-visualizer-large .bar:nth-child(4) { height: 50px; animation: sound 0.4s infinite alternate; }
    .voice-visualizer-large .bar:nth-child(5) { height: 25px; animation: sound 0.8s infinite alternate; }

    .voice-large-input {
        width: min(100%, 760px);
        height: 150px;
        font-size: 24px;
        padding: 20px;
        border-radius: 15px;
        border: 2px solid var(--glass-border);
        background: var(--surface-muted);
        color: var(--text);
        resize: none;
        text-align: center;
    }
    .voice-large-input:focus {
        outline: none;
        border-color: var(--primary);
    }
    .voice-controls {
        margin-top: 30px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
    }
    .voice-circle-btn {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        font-size: 32px;
        box-shadow: 0 10px 30px rgba(var(--primary-rgb), 0.35);
        justify-content: center;
    }
    #voiceSendBtn {
        box-shadow: 0 10px 30px rgba(var(--primary-rgb), 0.2);
    }
    .voice-status {
        margin-top: 20px;
        font-size: 18px;
        color: var(--text-muted);
        width: 100%;
        text-align: center;
    }

    @keyframes sound {
        0% { height: 10px; opacity: 0.5; }
        100% { height: 50px; opacity: 1; }
    }

    @media (max-width: 768px) {
        .voice-page {
            padding: 20px 16px;
        }
        .voice-large-input {
            height: 120px;
            font-size: 18px;
            padding: 14px;
        }
        .voice-circle-btn {
            width: 72px;
            height: 72px;
            font-size: 28px;
        }
        .voice-status {
            font-size: 16px;
        }
    }
</style>
@endsection

@section('content')
@include('partials.page-heading', ['heading'=>'Voice to Chat', 'description'=>'Gunakan suara untuk menulis pesan, lalu periksa sebelum mengirim.'])
<div class="glass-card voice-page">
    
    <div class="voice-header">
        <i class="fas fa-deaf"></i>
        <h2>Voice to Chat</h2>
        <p>{{ isset($chatRooms) ? 'Pilih percakapan masyarakat, lalu diktekan balasan Anda.' : 'Bicara untuk mengirim pesan ke PTSP satker Anda.' }}</p>
        @if(isset($chatRooms))
        <form method="GET" action="{{ route('chat.voice') }}" style="margin-top:16px;">
            <label for="voiceRoom">Percakapan Masyarakat</label>
            <select id="voiceRoom" name="room_id" class="form-control" onchange="this.form.submit()" {{ $chatRooms->isEmpty() ? 'disabled' : '' }}>
                @forelse($chatRooms as $room)
                <option value="{{ $room->id }}" {{ $chatRoom && $chatRoom->id == $room->id ? 'selected' : '' }}>{{ $room->user->name ?? 'Masyarakat' }} · {{ $room->service->name ?? 'Umum' }}</option>
                @empty<option>Belum ada percakapan masyarakat di satker Anda</option>@endforelse
            </select>
        </form>
        @endif
    </div>

    <!-- Voice Visualizer -->
    <div class="voice-visualizer-large">
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
        <div class="bar"></div>
    </div>

    <!-- Large Text Area -->
    <textarea aria-label="Pesan untuk percakapan PTSP" id="voiceLargeInput" class="voice-large-input" maxlength="5000" placeholder="Klik mikrofon dan mulai bicara, atau ketik pesan..." {{ !$chatRoom ? 'disabled' : '' }}></textarea>

    <!-- Controls -->
    <div class="voice-controls">
        <button id="voiceLargeBtn" aria-label="Dikte pesan" class="btn btn-primary voice-circle-btn" {{ !$chatRoom ? 'disabled' : '' }}>
            <i class="fas fa-microphone"></i>
        </button>
        
        <button id="voiceSendBtn" aria-label="Kirim pesan" onclick="sendVoiceMessage()" class="btn btn-primary voice-circle-btn" style="display: none;">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>

    <!-- Status -->
    <div id="voiceStatus" class="voice-status">
        {{ $chatRoom ? 'Tekan mikrofon untuk mulai, atau ketik pesan.' : 'Belum ada percakapan yang dapat dilayani.' }}
    </div>

</div>

<!-- Hidden Chat ID for script -->
<input type="hidden" id="chatRoomId" value="{{ $chatRoom->id ?? '' }}">
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const micBtn = document.getElementById('voiceLargeBtn');
    const sendBtn = document.getElementById('voiceSendBtn');
    const input = document.getElementById('voiceLargeInput');
    const visualizer = document.querySelector('.voice-visualizer-large');
    const status = document.getElementById('voiceStatus');
    let recognition;
    let microphoneError = '';
    input.addEventListener('input', () => { sendBtn.style.display = input.value.trim() ? 'inline-flex' : 'none'; });
    if (!document.getElementById('chatRoomId').value) return;

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = 'id-ID';

        recognition.onstart = () => {
            microphoneError = '';
            micBtn.classList.add('active');
            micBtn.style.backgroundColor = 'var(--danger)';
            micBtn.innerHTML = '<i class="fas fa-stop"></i>';
            visualizer.classList.add('active');
            status.textContent = "Mendengarkan...";
            status.style.color = 'var(--danger)';
        };

        recognition.onend = () => {
            micBtn.classList.remove('active');
            micBtn.style.backgroundColor = '';
            micBtn.innerHTML = '<i class="fas fa-microphone"></i>';
            visualizer.classList.remove('active');
            status.textContent = microphoneError || "Selesai mendengarkan";
            status.style.color = 'var(--text-muted)';
            if (input.value.trim().length > 0) {
                sendBtn.style.display = 'inline-flex';
            }
        };

        recognition.onerror = (event) => {
            microphoneError = event.error === 'not-allowed'
                ? 'Izinkan akses mikrofon di browser, atau ketik pesan Anda.'
                : 'Suara belum dapat dikenali. Coba lagi atau ketik pesan Anda.';
            status.textContent = microphoneError;
        };

        recognition.onresult = (event) => {
            let final = '';
            let interim = '';
            for (let i = 0; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    final += event.results[i][0].transcript;
                } else {
                    interim += event.results[i][0].transcript;
                }
            }
            if (final || interim) {
                input.value = (final + ' ' + interim).trim();
                sendBtn.style.display = 'inline-flex';
            }
        };

        micBtn.addEventListener('click', () => {
            if (micBtn.classList.contains('active')) {
                recognition.stop();
            } else {
                input.value = ''; // Clear previous
                try { recognition.start(); } catch (error) { status.textContent = 'Mikrofon belum dapat digunakan. Silakan ketik pesan.'; }
            }
        });

    } else {
        micBtn.disabled = true;
        status.textContent = "Browser belum mendukung dikte suara. Anda tetap dapat mengetik pesan.";
    }
});

function sendVoiceMessage() {
    const msg = document.getElementById('voiceLargeInput').value;
    const roomId = document.getElementById('chatRoomId').value;
    
    if (!msg || !roomId) return;
    const button = document.getElementById('voiceSendBtn');
    if (button.disabled) return;
    button.disabled = true;

    // Send via AJAX
    ajaxRequest('{{ route("chat.store") }}', 'POST', {
        chat_room_id: roomId,
        message: msg,
        type: 'text' // Sent as text, originated from voice
    }, function(err, data) {
        button.disabled = false;
        if (!err) {
            // Show success animation or redirect?
            // Redirect to chat room to see reply
            window.location.href = @json($chatRoom ? route('chat.show', $chatRoom->id) : route('chat.index'));
        } else {
            document.getElementById('voiceStatus').textContent = 'Pesan belum terkirim. Silakan coba lagi.';
        }
    });
}
</script>
@endsection
