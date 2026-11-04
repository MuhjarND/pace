// ============================================
// PTSP Online - Call Center (WebRTC Simulated)
// ============================================

class CallCenter {
    constructor() {
        this.currentCall = null;
        this.callTimer = null;
        this.callDuration = 0;
        this.dialNumber = '';
        this.init();
    }

    init() {
        this.bindDialPad();
        this.bindCallActions();
        this.bindContactList();
    }

    bindDialPad() {
        document.querySelectorAll('.dial-btn[data-digit]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.dialNumber += btn.dataset.digit;
                this.updateDisplay();
                this.playTone();
            });
        });

        const clearBtn = document.getElementById('dialClear');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.dialNumber = this.dialNumber.slice(0, -1);
                this.updateDisplay();
            });
        }
    }

    updateDisplay() {
        const display = document.getElementById('dialDisplay');
        if (display) {
            display.textContent = this.dialNumber || 'Masukkan Nomor';
        }
    }

    playTone() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 440 + Math.random() * 200;
            gain.gain.value = 0.05;
            osc.start();
            osc.stop(ctx.currentTime + 0.1);
        } catch (e) { }
    }

    bindCallActions() {
        const callBtn = document.getElementById('startCallBtn');
        if (callBtn) {
            callBtn.addEventListener('click', () => this.startCall());
        }

        const endBtn = document.getElementById('endCallBtn');
        if (endBtn) {
            endBtn.addEventListener('click', () => this.endCall());
        }

        const muteBtn = document.getElementById('muteBtn');
        if (muteBtn) {
            muteBtn.addEventListener('click', () => {
                muteBtn.classList.toggle('active');
                muteBtn.querySelector('i').classList.toggle('fa-microphone');
                muteBtn.querySelector('i').classList.toggle('fa-microphone-slash');
            });
        }
    }

    bindContactList() {
        document.querySelectorAll('.contact-call-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const userId = btn.dataset.userId;
                const userName = btn.dataset.userName;
                this.callContact(userId, userName);
            });
        });
    }

    startCall() {
        if (!this.dialNumber && !this.currentCall) return;
        this.showCallOverlay('Memanggil...', this.dialNumber);

        // Simulate ringing
        setTimeout(() => {
            this.showCallOverlay('Terhubung', this.dialNumber);
            this.startTimer();
        }, 3000);
    }

    callContact(userId, userName) {
        this.showCallOverlay('Memanggil...', userName);

        // Make API call to log
        ajaxRequest('/call-center/call', 'POST', {
            receiver_id: userId,
            type: 'audio'
        }, (err, data) => {
            if (!err && data.success) {
                this.currentCall = data.call;
                setTimeout(() => {
                    this.showCallOverlay('Terhubung', userName);
                    this.startTimer();
                }, 2500);
            }
        });
    }

    showCallOverlay(status, name) {
        const overlay = document.getElementById('callOverlay');
        if (!overlay) return;

        overlay.classList.add('active');
        const nameEl = overlay.querySelector('.call-name');
        const statusEl = overlay.querySelector('.call-status');
        if (nameEl) nameEl.textContent = name || 'Unknown';
        if (statusEl) statusEl.textContent = status;
    }

    hideCallOverlay() {
        const overlay = document.getElementById('callOverlay');
        if (overlay) overlay.classList.remove('active');
    }

    startTimer() {
        this.callDuration = 0;
        this.callTimer = setInterval(() => {
            this.callDuration++;
            const mins = Math.floor(this.callDuration / 60).toString().padStart(2, '0');
            const secs = (this.callDuration % 60).toString().padStart(2, '0');
            const timerEl = document.querySelector('.call-timer');
            if (timerEl) timerEl.textContent = `${mins}:${secs}`;
        }, 1000);
    }

    endCall() {
        clearInterval(this.callTimer);

        if (this.currentCall) {
            ajaxRequest(`/call-center/${this.currentCall.id}/end`, 'POST', {
                duration: this.callDuration
            }, () => { });
        }

        this.hideCallOverlay();
        this.currentCall = null;
        this.callDuration = 0;
        this.dialNumber = '';
        this.updateDisplay();

        // Refresh page to update history
        setTimeout(() => location.reload(), 500);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.call-container')) {
        window.callCenter = new CallCenter();
    }
});
