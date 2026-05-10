(function () {
    /* ══════════════════════════════════════
       🔊 SOUND ENGINE (Web Audio API)
    ══════════════════════════════════════ */
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    let audioCtx = null;

    function getAudio() {
        if (!audioCtx) audioCtx = new AudioCtx();
        return audioCtx;
    }

    function playTone(freq, duration, type, vol) {
        try {
            const ctx = getAudio();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = type || 'sine';
            osc.frequency.setValueAtTime(freq, ctx.currentTime);
            gain.gain.setValueAtTime(vol || 0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + duration);
        } catch (_) {}
    }

    function soundClick() {
        playTone(880, 0.08, 'sine', 0.12);
        setTimeout(() => playTone(1100, 0.08, 'sine', 0.10), 50);
    }

    function soundCelebrate() {
        const notes = [523, 659, 784, 1047, 1319];
        notes.forEach((n, i) => setTimeout(() => playTone(n, 0.18, 'sine', 0.12), i * 80));
    }

    function soundPop() {
        playTone(600, 0.06, 'triangle', 0.10);
    }

    function soundWhoosh() {
        try {
            const ctx = getAudio();
            const bufferSize = ctx.sampleRate * 0.15;
            const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
            const data = buffer.getChannelData(0);
            for (let i = 0; i < bufferSize; i++) data[i] = (Math.random() * 2 - 1) * (1 - i / bufferSize);
            const source = ctx.createBufferSource();
            const gain = ctx.createGain();
            const filter = ctx.createBiquadFilter();
            filter.type = 'bandpass';
            filter.frequency.setValueAtTime(2000, ctx.currentTime);
            filter.frequency.exponentialRampToValueAtTime(400, ctx.currentTime + 0.15);
            source.buffer = buffer;
            gain.gain.setValueAtTime(0.08, ctx.currentTime);
            source.connect(filter);
            filter.connect(gain);
            gain.connect(ctx.destination);
            source.start();
        } catch (_) {}
    }

    function soundStar() {
        playTone(1200, 0.10, 'sine', 0.08);
        setTimeout(() => playTone(1600, 0.12, 'sine', 0.06), 70);
    }

    /* ══════════════════════════════════════
       ✨ PARTICLE SYSTEM
    ══════════════════════════════════════ */
    const COLORS = ['#f59e0b', '#7c3aed', '#ec4899', '#10b981', '#60a5fa', '#ef4444', '#f97316'];
    const EMOJIS_STAR = ['⭐', '🌟', '✨', '💫', '🎉', '🎊', '🏆', '♔', '♕'];

    function createParticle(x, y, opts) {
        const el = document.createElement('div');
        const size = opts.size || 10;
        const color = opts.color || COLORS[Math.floor(Math.random() * COLORS.length)];
        const emoji = opts.emoji;

        Object.assign(el.style, {
            position: 'fixed',
            left: x + 'px',
            top: y + 'px',
            width: size + 'px',
            height: size + 'px',
            pointerEvents: 'none',
            zIndex: '99999',
            borderRadius: opts.square ? '3px' : '50%',
            fontSize: size + 'px',
            lineHeight: '1',
            textAlign: 'center',
        });

        if (emoji) {
            el.textContent = emoji;
            el.style.background = 'none';
            el.style.width = 'auto';
            el.style.height = 'auto';
        } else {
            el.style.background = color;
        }

        document.body.appendChild(el);
        return el;
    }

    function burstParticles(x, y, count, opts) {
        for (let i = 0; i < count; i++) {
            const angle = (Math.PI * 2 * i) / count + (Math.random() - 0.5) * 0.5;
            const velocity = 120 + Math.random() * 180;
            const p = createParticle(x, y, {
                size: opts.size || (6 + Math.random() * 8),
                color: opts.color,
                emoji: opts.emojis ? opts.emojis[Math.floor(Math.random() * opts.emojis.length)] : null,
                square: Math.random() > 0.5,
            });

            const dx = Math.cos(angle) * velocity;
            const dy = Math.sin(angle) * velocity - 60;
            const rot = Math.random() * 720 - 360;
            const dur = 700 + Math.random() * 500;

            p.animate([
                { transform: 'translate(0,0) rotate(0deg) scale(1)', opacity: 1 },
                { transform: `translate(${dx}px, ${dy + 120}px) rotate(${rot}deg) scale(0)`, opacity: 0 }
            ], { duration: dur, easing: 'cubic-bezier(.25,.46,.45,.94)', fill: 'forwards' });

            setTimeout(() => p.remove(), dur + 50);
        }
    }

    /* ── Star burst (emojis) ── */
    function starBurst(x, y) {
        burstParticles(x, y, 12, { emojis: EMOJIS_STAR, size: 22 });
        burstParticles(x, y, 20, { size: 6 });
    }

    /* ══════════════════════════════════════
       💬 ENCOURAGING WORDS SYSTEM
    ══════════════════════════════════════ */
    const PRAISE_WORDS = [
        'Молодец! 🎉', 'Отлично! ⭐', 'Супер! 🚀', 'Класс! 💪',
        'Чемпион! 🏆', 'Браво! 👏', 'Круто! 🔥', 'Ты лучший! 🌟',
        'Невероятно! 💫', 'Фантастика! ✨', 'Гениально! 🧠',
        'Так держать! 🎯', 'Здорово! 🥇', 'Мастер! ♔', 'Победа! 🎊',
        'Умница! 💎', 'Герой! 🦸', 'Вперёд! 🚀', 'Сила! 💥', 'Wow! 🤩',
    ];

    function showPraiseAt(x, y) {
        const word = PRAISE_WORDS[Math.floor(Math.random() * PRAISE_WORDS.length)];
        const el = document.createElement('div');
        el.className = 'praise-popup';
        el.textContent = word;
        Object.assign(el.style, {
            position: 'fixed',
            left: x + 'px',
            top: y + 'px',
            zIndex: '100000',
            pointerEvents: 'none',
        });
        document.body.appendChild(el);
        el.animate([
            { transform: 'translate(-50%, 0) scale(0.5)', opacity: 0 },
            { transform: 'translate(-50%, -20px) scale(1.1)', opacity: 1, offset: 0.2 },
            { transform: 'translate(-50%, -60px) scale(1)', opacity: 1, offset: 0.7 },
            { transform: 'translate(-50%, -100px) scale(0.8)', opacity: 0 },
        ], { duration: 1600, easing: 'ease-out', fill: 'forwards' });
        setTimeout(() => el.remove(), 1700);
    }

    /* ── Auto floating words ── */
    function spawnFloatingWord() {
        const words = ['♔', '♕', '♖', '♗', '♘', '♙', '⭐', '🏆', '✨', '🎯'];
        const el = document.createElement('div');
        el.className = 'floating-word';
        el.textContent = words[Math.floor(Math.random() * words.length)];
        const startX = Math.random() * window.innerWidth;
        Object.assign(el.style, {
            position: 'fixed',
            left: startX + 'px',
            bottom: '-50px',
            fontSize: (24 + Math.random() * 30) + 'px',
            opacity: '0',
            pointerEvents: 'none',
            zIndex: '1',
        });
        document.body.appendChild(el);

        const drift = (Math.random() - 0.5) * 200;
        const dur = 4000 + Math.random() * 4000;

        el.animate([
            { transform: `translateX(0) rotate(0deg)`, opacity: 0 },
            { opacity: 0.25, offset: 0.1 },
            { opacity: 0.25, offset: 0.8 },
            { transform: `translateX(${drift}px) translateY(-${window.innerHeight + 100}px) rotate(${Math.random() * 90 - 45}deg)`, opacity: 0 },
        ], { duration: dur, easing: 'linear', fill: 'forwards' });

        setTimeout(() => el.remove(), dur + 100);
    }

    /* ── Scrolling encouragement banner ── */
    function createBanner() {
        const isPublic = document.querySelector('.public-page');
        if (!isPublic) return;

        const banner = document.createElement('div');
        banner.className = 'encourage-banner';
        const messages = [
            '🏆 Каждый ход делает тебя сильнее!',
            '⭐ Шахматы — игра чемпионов!',
            '🚀 Ты становишься лучше с каждым днём!',
            '♔ Будущий гроссмейстер среди нас!',
            '🎯 Думай, играй, побеждай!',
            '💪 Практика ведёт к мастерству!',
            '🧠 Шахматы тренируют ум!',
            '🌟 Ты — звезда шахмат!',
        ];
        banner.innerHTML = '<div class="banner-track">' +
            messages.concat(messages).map(m => `<span>${m}</span>`).join('') +
            '</div>';
        document.body.appendChild(banner);
    }

    /* ══════════════════════════════════════
       🖱️ SPARKLE CURSOR TRAIL
    ══════════════════════════════════════ */
    let lastTrail = 0;
    function initCursorTrail() {
        if (!document.querySelector('.public-page')) return;
        document.addEventListener('mousemove', (e) => {
            const now = Date.now();
            if (now - lastTrail < 50) return;
            lastTrail = now;
            const spark = document.createElement('div');
            spark.className = 'cursor-spark';
            spark.textContent = ['✦', '✧', '·', '⋆'][Math.floor(Math.random() * 4)];
            Object.assign(spark.style, {
                left: e.clientX + 'px',
                top: e.clientY + 'px',
                color: COLORS[Math.floor(Math.random() * COLORS.length)],
                fontSize: (10 + Math.random() * 14) + 'px',
            });
            document.body.appendChild(spark);
            spark.animate([
                { transform: 'translate(-50%,-50%) scale(1)', opacity: 1 },
                { transform: `translate(${(Math.random()-0.5)*40}px, ${-20 - Math.random()*30}px) scale(0)`, opacity: 0 },
            ], { duration: 600, easing: 'ease-out', fill: 'forwards' });
            setTimeout(() => spark.remove(), 650);
        });
    }

    /* ══════════════════════════════════════
       🃏 CARD INTERACTIONS
    ══════════════════════════════════════ */
    function initCardClicks() {
        const cards = document.querySelectorAll('.leader-card');
        cards.forEach((card, i) => {
            /* Entrance animation */
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px) scale(0.9)';
            setTimeout(() => {
                card.style.transition = 'opacity .5s ease, transform .5s cubic-bezier(.34,1.56,.64,1)';
                card.style.opacity = '1';
                card.style.transform = '';
                soundPop();
            }, 200 + i * 150);

            /* Click interaction */
            card.addEventListener('click', (e) => {
                const rect = card.getBoundingClientRect();
                const cx = rect.left + rect.width / 2;
                const cy = rect.top + rect.height / 2;

                /* Sound */
                soundCelebrate();

                /* Star burst */
                starBurst(e.clientX, e.clientY);

                /* Praise popup */
                showPraiseAt(cx, cy - 20);

                /* Card wiggle */
                card.animate([
                    { transform: 'scale(1) rotate(0deg)' },
                    { transform: 'scale(1.06) rotate(-2deg)' },
                    { transform: 'scale(1.08) rotate(2deg)' },
                    { transform: 'scale(1.04) rotate(-1deg)' },
                    { transform: 'scale(1) rotate(0deg)' },
                ], { duration: 500, easing: 'ease-in-out' });

                /* Score bounce */
                const scoreEl = card.querySelector('.leader-copy p');
                if (scoreEl) {
                    scoreEl.animate([
                        { transform: 'scale(1)', color: '' },
                        { transform: 'scale(1.4)', color: '#7c3aed' },
                        { transform: 'scale(1)', color: '' },
                    ], { duration: 400, easing: 'ease-in-out' });
                }

                /* Avatar spin */
                const avatar = card.querySelector('.avatar-ring');
                if (avatar) {
                    avatar.animate([
                        { transform: 'rotate(0deg) scale(1)' },
                        { transform: 'rotate(360deg) scale(1.2)' },
                        { transform: 'rotate(360deg) scale(1)' },
                    ], { duration: 600, easing: 'cubic-bezier(.34,1.56,.64,1)' });
                }

                /* Ring pulse effect */
                const ring = document.createElement('div');
                ring.className = 'click-ring';
                Object.assign(ring.style, {
                    position: 'fixed',
                    left: (e.clientX - 30) + 'px',
                    top: (e.clientY - 30) + 'px',
                });
                document.body.appendChild(ring);
                setTimeout(() => ring.remove(), 700);
            });

            /* Hover sound */
            card.addEventListener('mouseenter', () => {
                soundClick();
            });
        });
    }

    /* ── Score counter-up ── */
    function initScoreCountUp() {
        // Leaderboard cards
        document.querySelectorAll('.leader-card').forEach((card) => {
            const scoreEl = card.querySelector('.leader-copy p');
            if (!scoreEl) return;
            const text = scoreEl.textContent || '';
            const match = text.match(/(\d+)/);
            if (!match) return;
            const target = parseInt(match[1], 10);
            const suffix = text.replace(match[1], '').trim();
            let current = 0;
            const step = Math.max(1, Math.ceil(target / 55));
            scoreEl.textContent = `0 ${suffix}`;
            const timer = setInterval(() => {
                current = Math.min(current + step, target);
                scoreEl.textContent = `${current} ${suffix}`;
                if (current >= target) {
                    clearInterval(timer);
                    soundStar();
                }
            }, 16);
        });

        // Student cabinet score
        const scoreNumber = document.querySelector('.score-number');
        if (scoreNumber) {
            const target = parseInt(scoreNumber.dataset.target || '0', 10);
            let current = 0;
            const step = Math.max(1, Math.ceil(target / 60));
            scoreNumber.textContent = '0';
            const timer = setInterval(() => {
                current = Math.min(current + step, target);
                scoreNumber.textContent = current.toString();
                if (current >= target) {
                    clearInterval(timer);
                    soundStar();
                }
            }, 16);
        }
    }

    /* ══════════════════════════════════════
       🎉 CONFETTI CANNON (on page load)
    ══════════════════════════════════════ */
    function fireConfetti() {
        if (!document.querySelector('.leader-card.gold')) return;
        setTimeout(() => {
            soundCelebrate();
            const cx = window.innerWidth / 2;
            burstParticles(cx, window.innerHeight * 0.3, 35, { size: 8 });
            burstParticles(cx - 100, window.innerHeight * 0.35, 20, { emojis: ['⭐','🌟','🏆','✨','🎉'], size: 24 });
            burstParticles(cx + 100, window.innerHeight * 0.35, 20, { emojis: ['♔','♕','♖','💎','🎊'], size: 24 });
        }, 800);
    }

    /* ══════════════════════════════════════
       🎹 INTERACTIVE BRAND MARK
    ══════════════════════════════════════ */
    function initBrandMark() {
        const mark = document.querySelector('.public-hero .brand-mark');
        if (!mark) return;
        let clickCount = 0;
        mark.style.cursor = 'pointer';
        mark.addEventListener('click', (e) => {
            clickCount++;
            soundCelebrate();
            starBurst(e.clientX, e.clientY);
            showPraiseAt(e.clientX, e.clientY - 40);

            mark.animate([
                { transform: 'scale(1) rotate(0deg)' },
                { transform: 'scale(1.3) rotate(20deg)' },
                { transform: 'scale(1.3) rotate(-20deg)' },
                { transform: 'scale(1) rotate(0deg)' },
            ], { duration: 500, easing: 'ease-in-out' });

            /* Easter egg — extra confetti every 5 clicks */
            if (clickCount % 5 === 0) {
                for (let i = 0; i < 3; i++) {
                    setTimeout(() => {
                        burstParticles(
                            Math.random() * window.innerWidth,
                            Math.random() * window.innerHeight * 0.5,
                            25, { emojis: EMOJIS_STAR, size: 28 }
                        );
                        soundCelebrate();
                    }, i * 300);
                }
            }
        });
    }

    /* ══════════════════════════════════════
       🎯 SEASON PILL INTERACTION
    ══════════════════════════════════════ */
    function initSeasonPill() {
        const pill = document.querySelector('.season-pill');
        if (!pill) return;
        pill.style.cursor = 'pointer';
        pill.addEventListener('click', (e) => {
            soundWhoosh();
            burstParticles(e.clientX, e.clientY, 15, { size: 5 });
            pill.animate([
                { transform: 'scale(1)' },
                { transform: 'scale(1.15)' },
                { transform: 'scale(1)' },
            ], { duration: 350, easing: 'ease-in-out' });
        });
    }

    /* ══════════════════════════════════════
       🎓 STUDENT CABINET INTERACTIONS
    ══════════════════════════════════════ */
    function initStudentCabinet() {
        const isStudent = document.querySelector('.student-cabinet');
        if (!isStudent) return;

        // Avatar click interaction
        const avatar = document.getElementById('student-avatar');
        if (avatar) {
            let clickCount = 0;
            avatar.style.cursor = 'pointer';
            avatar.addEventListener('click', (e) => {
                clickCount++;
                soundCelebrate();
                starBurst(e.clientX, e.clientY);
                showPraiseAt(e.clientX, e.clientY - 40);

                avatar.animate([
                    { transform: 'scale(1) rotate(0deg)' },
                    { transform: 'scale(1.3) rotate(15deg)' },
                    { transform: 'scale(1.3) rotate(-15deg)' },
                    { transform: 'scale(1) rotate(0deg)' },
                ], { duration: 500, easing: 'ease-in-out' });

                if (clickCount % 3 === 0) fireConfetti();
            });
            avatar.addEventListener('mouseenter', soundPop);
        }

        // Score card click interaction
        const scoreCard = document.getElementById('score-card');
        if (scoreCard) {
            scoreCard.style.cursor = 'pointer';
            scoreCard.addEventListener('click', (e) => {
                const rect = scoreCard.getBoundingClientRect();
                const cx = rect.left + rect.width / 2;
                const cy = rect.top + rect.height / 2;
                
                soundCelebrate();
                starBurst(e.clientX, e.clientY);
                showPraiseAt(cx, cy - 30);
                
                scoreCard.animate([
                    { transform: 'scale(1)' },
                    { transform: 'scale(1.05) rotate(1deg)' },
                    { transform: 'scale(1)' },
                ], { duration: 300, easing: 'ease-in-out' });
                
                const scoreNumber = scoreCard.querySelector('.score-number');
                if (scoreNumber) {
                    scoreNumber.animate([
                        { transform: 'scale(1)' },
                        { transform: 'scale(1.4)', color: '#ffd700' },
                        { transform: 'scale(1)' },
                    ], { duration: 400, easing: 'ease-in-out' });
                }
            });
            scoreCard.addEventListener('mouseenter', soundClick);
        }

        // Award cards interactions
        document.querySelectorAll('.award-card').forEach((card, i) => {
            // Staggered entrance
            card.style.opacity = '0';
            card.style.transform = 'translateX(-30px)';
            setTimeout(() => {
                card.style.transition = 'opacity .4s ease, transform .4s cubic-bezier(.34,1.56,.64,1)';
                card.style.opacity = '1';
                card.style.transform = '';
            }, 300 + i * 80);

            // Click interaction
            card.addEventListener('click', (e) => {
                const rect = card.getBoundingClientRect();
                const cx = rect.left + rect.width / 2;
                const cy = rect.top + rect.height / 2;
                
                soundPop();
                setTimeout(soundStar, 50);
                burstParticles(e.clientX, e.clientY, 15, { emojis: ['💎','🏆','⭐'], size: 18 });
                showPraiseAt(cx, cy - 20);

                card.animate([
                    { transform: 'scale(1)' },
                    { transform: 'scale(1.04) rotate(-1deg)' },
                    { transform: 'scale(1.04) rotate(1deg)' },
                    { transform: 'scale(1)' },
                ], { duration: 400, easing: 'ease-in-out' });
            });
            
            card.addEventListener('mouseenter', soundPop);
        });
    }

    /* ══════════════════════════════════════
       INIT (only on public page)
    ══════════════════════════════════════ */
    const isPublic = document.querySelector('.public-page');

    if (isPublic) {
        initCardClicks();
        initScoreCountUp();
        fireConfetti();
        initCursorTrail();
        initBrandMark();
        initSeasonPill();
        createBanner();
        initStudentCabinet();

        /* Spawn floating chess pieces periodically */
        spawnFloatingWord();
        setInterval(spawnFloatingWord, 2500);
    }

    /* ── FORM HANDLING (admin) ── */
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const submitter = event.submitter;
            const message = submitter ? submitter.getAttribute('data-confirm') : null;
            if (message && !window.confirm(message)) { event.preventDefault(); return; }
            if (submitter && submitter.matches('button[type="submit"]')) {
                submitter.dataset.originalText = submitter.textContent || '';
                submitter.textContent = 'Сохранение...';
            }
        });
    });

    /* ── DETAILS ACCORDION (admin) ── */
    document.querySelectorAll('details').forEach((details) => {
        details.addEventListener('toggle', () => {
            if (!details.open) return;
            document.querySelectorAll('details[open]').forEach((other) => {
                if (other !== details && other.closest('section') === details.closest('section')) other.open = false;
            });
        });
    });

    /* ── REWARD PICKER (admin) — multi-select checkboxes ── */
    document.querySelectorAll('.reward-picker-multi').forEach((picker) => {
        const choices = picker.querySelectorAll('.reward-choice');
        choices.forEach((choice) => {
            const input = choice.querySelector('input[type="checkbox"]');
            if (!input) return;
            /* Toggle is-selected class based on checkbox state */
            choice.addEventListener('click', () => {
                /* Allow the click to propagate to the checkbox first */
                requestAnimationFrame(() => {
                    choice.classList.toggle('is-selected', input.checked);
                    soundPop();
                });
            });
        });
    });

    /* Legacy radio-based picker (keep for backwards compat) */
    document.querySelectorAll('.reward-picker:not(.reward-picker-multi)').forEach((picker) => {
        const choices = picker.querySelectorAll('.reward-choice');
        choices.forEach((choice) => {
            const input = choice.querySelector('input[type="radio"]');
            if (!input) return;
            input.addEventListener('change', () => {
                choices.forEach((item) => item.classList.remove('is-selected'));
                choice.classList.add('is-selected');
            });
        });
    });

    /* ══════════════════════════════════════
       ⚡ AJAX AWARD FORM SUBMISSION
    ══════════════════════════════════════ */
    function showToast(message, type) {
        // Check if there's already a toast function, otherwise create one
        const existing = document.querySelector('.toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'toast toast-' + (type || 'success');
        toast.textContent = message;
        document.body.appendChild(toast);

        function dismiss() {
            toast.classList.add('toast-out');
            toast.addEventListener('animationend', () => toast.remove(), { once: true });
        }
        const timer = setTimeout(dismiss, 4500);
        toast.addEventListener('click', () => { clearTimeout(timer); dismiss(); });
    }

    const awardForm = document.querySelector('.award-form input[name="action"][value="add_award"]');
    if (awardForm) {
        const form = awardForm.closest('form');
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            /* Validate at least 1 student + 1 reward checked */
            const studentChecks = form.querySelectorAll('.bulk-student-item input[type="checkbox"]:checked');
            const rewardChecks  = form.querySelectorAll('.reward-picker-multi input[type="checkbox"]:checked');

            if (studentChecks.length === 0) {
                showToast('Выберите хотя бы одного ученика.', 'error');
                return;
            }
            if (rewardChecks.length === 0) {
                showToast('Выберите хотя бы одну награду.', 'error');
                return;
            }

            const btn = form.querySelector('button[type="submit"]');
            const origText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = '⏳ Сохраняю...'; }

            try {
                const data = new FormData(form);
                const resp = await fetch(form.action || window.location.href, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: data,
                });

                const json = await resp.json();

                if (json.ok) {
                    showToast('🎉 ' + json.message, 'success');
                    soundCelebrate();
                    burstParticles(window.innerWidth / 2, window.innerHeight / 3);

                    /* Update student score badges in the bulk picker */
                    if (json.updated_scores) {
                        Object.entries(json.updated_scores).forEach(([sid, score]) => {
                            const item = form.querySelector(`.bulk-student-item input[value="${sid}"]`);
                            if (item) {
                                const scoreEl = item.closest('.bulk-student-item')?.querySelector('.bulk-student-score');
                                if (scoreEl) {
                                    scoreEl.textContent = score + ' оч.';
                                    scoreEl.style.transition = 'color .4s';
                                    scoreEl.style.color = '#7c3aed';
                                    setTimeout(() => { scoreEl.style.color = ''; }, 1500);
                                }
                            }
                        });
                    }

                    /* Clear selections */
                    form.querySelectorAll('.reward-picker-multi input[type="checkbox"]').forEach(cb => {
                        cb.checked = false;
                        cb.closest('.reward-choice')?.classList.remove('is-selected');
                    });
                } else {
                    showToast('❌ ' + (json.message || 'Ошибка при сохранении.'), 'error');
                    soundPop();
                }
            } catch (err) {
                showToast('❌ Ошибка сети. Попробуйте ещё раз.', 'error');
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = origText; }
            }
        });
    }

    /* ══════════════════════════════════════
       🔔 TOAST AUTO-DISMISS
    ══════════════════════════════════════ */
    function dismissToast(toast) {
        toast.classList.add('toast-out');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    }

    document.querySelectorAll('.toast').forEach((toast) => {
        /* Auto-dismiss after 4 seconds */
        const timer = setTimeout(() => dismissToast(toast), 4000);
        /* Click to dismiss early */
        toast.addEventListener('click', () => { clearTimeout(timer); dismissToast(toast); });
    });

    /* ══════════════════════════════════════
       📊 RANK PROGRESS BAR ANIMATION
    ══════════════════════════════════════ */
    function initRankProgressBar() {
        const fill = document.querySelector('.rank-xp-fill');
        if (!fill) return;

        const target = parseFloat(fill.dataset.target || '0');

        /* Animate after a short delay so user sees the bar grow */
        setTimeout(() => {
            fill.style.width = target + '%';
        }, 700);
    }

    /* ══════════════════════════════════════
       ⬆️ BACK-TO-TOP BUTTON
    ══════════════════════════════════════ */
    initRankProgressBar();

    /* ══════════════════════════════════════
       👥 BULK SELECT-ALL STUDENTS (admin)
    ══════════════════════════════════════ */
    (function initBulkSelect() {
        const selectAllBtn = document.getElementById('select-all-students');
        if (!selectAllBtn) return;
        let allSelected = false;

        selectAllBtn.addEventListener('click', () => {
            allSelected = !allSelected;
            document.querySelectorAll('.bulk-student-item input[type="checkbox"]').forEach((cb) => {
                cb.checked = allSelected;
            });
            selectAllBtn.textContent = allSelected ? 'Снять всех' : 'Выбрать всех';
            soundPop();
        });
    })();

    (function initBackToTop() {

        const btn = document.createElement('button');
        btn.className = 'back-to-top';
        btn.setAttribute('aria-label', 'Наверх');
        btn.setAttribute('title', 'Наверх');
        btn.innerHTML = '↑';
        document.body.appendChild(btn);

        function onScroll() {
            if (window.scrollY > 320) {
                btn.classList.add('visible');
            } else {
                btn.classList.remove('visible');
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        btn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            soundClick();
        });
    })();

    /* ══════════════════════════════════════
       👁 CMS LIVE PREVIEW (Settings page)
    ══════════════════════════════════════ */
    (function initCmsPreview() {
        const frame = document.getElementById('cms-preview-frame');
        const refreshBtn = document.getElementById('cms-refresh-preview');
        const settingsForm = document.getElementById('cms-settings-form');
        if (!frame || !settingsForm) return;

        /* Refresh button */
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                frame.src = frame.src;
            });
        }

        /* Debounce helper */
        function debounce(fn, delay) {
            let t;
            return function(...args) {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), delay);
            };
        }

        /* On any input change, reload the preview after a short delay */
        const reloadPreview = debounce(() => {
            frame.src = 'index.php';
        }, 1800);

        settingsForm.querySelectorAll('input[type="text"], input[type="number"]').forEach(input => {
            input.addEventListener('input', reloadPreview);
        });
    })();

})();
