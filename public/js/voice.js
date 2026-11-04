// ============================================
// PTSP Online - Voice to Text (Web Speech API)
// ============================================

class VoiceToText {
    constructor() {
        this.recognition = null;
        this.isRecording = false;
        this.targetInput = null;
        this.init();
    }

    init() {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            console.warn('Web Speech API tidak didukung di browser ini.');
            this.bindButtons();
            return;
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.recognition = new SpeechRecognition();
        this.recognition.lang = 'id-ID';
        this.recognition.continuous = false;
        this.recognition.interimResults = true;

        this.recognition.onresult = (event) => {
            let finalTranscript = '';
            let interimTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTranscript += transcript;
                } else {
                    interimTranscript += transcript;
                }
            }

            if (this.targetInput) {
                this.targetInput.value = finalTranscript || interimTranscript;
                this.targetInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            // Show interim in voice indicator
            const indicator = document.querySelector('.voice-text-preview');
            if (indicator) {
                indicator.textContent = interimTranscript || finalTranscript || 'Mendengarkan...';
            }
        };

        this.recognition.onend = () => {
            this.stopRecording();
        };

        this.recognition.onerror = (event) => {
            console.error('Speech recognition error:', event.error);
            this.stopRecording();
            if (window.PTSPUI) window.PTSPUI.status(event.error === 'not-allowed' ? 'Akses mikrofon belum diizinkan. Izinkan melalui pengaturan browser atau ketik pesan Anda.' : 'Dikte belum berhasil. Coba lagi atau ketik pesan Anda.');
        };

        this.bindButtons();
    }

    bindButtons() {
        // Bind all voice buttons
        document.querySelectorAll('[data-voice-target]').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.dataset.voiceTarget;
                const targetInput = document.getElementById(targetId);
                if (targetInput) {
                    this.toggle(targetInput, btn);
                }
            });
        });

        // Global voice search button
        const globalVoiceBtn = document.getElementById('globalVoiceSearch');
        if (globalVoiceBtn) {
            globalVoiceBtn.addEventListener('click', () => {
                const searchInput = document.getElementById('globalSearch');
                if (searchInput) {
                    this.toggle(searchInput, globalVoiceBtn);
                }
            });
        }
    }

    toggle(inputElement, btn) {
        if (this.isRecording) {
            this.stopRecording();
        } else {
            this.startRecording(inputElement, btn);
        }
    }

    startRecording(inputElement, btn) {
        if (!this.recognition) {
            if (window.PTSPUI) window.PTSPUI.status('Dikte belum tersedia di browser ini. Anda tetap dapat mengetik pesan atau pencarian.');
            return;
        }

        this.targetInput = inputElement;
        this.isRecording = true;

        // Update UI
        if (btn) btn.classList.add('recording');

        const indicator = document.querySelector('.voice-indicator');
        if (indicator) indicator.classList.add('active');

        try {
            this.recognition.start();
        } catch (e) {
            this.stopRecording();
        }
    }

    stopRecording() {
        this.isRecording = false;

        try {
            this.recognition.stop();
        } catch (e) { }

        // Update UI
        document.querySelectorAll('.recording').forEach(el => el.classList.remove('recording'));

        const indicator = document.querySelector('.voice-indicator');
        if (indicator) indicator.classList.remove('active');
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    window.voiceToText = new VoiceToText();
});
