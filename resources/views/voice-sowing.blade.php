<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Voice Sowing — AI Assistant</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg:#f3f6fb;
            --card:#ffffff;
            --muted:#6b7280;
            --accent1: #4f46e5;
            --accent2: #7c3aed;
            --maxw: 900px;
        }
        *{box-sizing:border-box}
        html,body{height:100%}
        body{margin:0;padding:32px;background:linear-gradient(180deg,#f7f9fc 0%,var(--bg) 100%);font-family:'Poppins',system-ui,-apple-system,Segoe UI,Roboto,'Helvetica Neue',Arial;color:#111827}
        .wrap{max-width:var(--maxw);margin:30px auto;padding:28px;border-radius:16px;background:linear-gradient(180deg, rgba(255,255,255,0.6), rgba(250,250,250,0.9));box-shadow:0 10px 30px rgba(15,23,42,0.06)}
        .header{display:flex;align-items:center;gap:16px;margin-bottom:18px}
        .logo{width:56px;height:56px;border-radius:12px;background:#6b8e23;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:20px}
        .title h1{margin:0;font-size:20px;font-weight:700}
        .title p{margin:0;color:var(--muted);font-size:13px}
        .main{display:flex;gap:24px;align-items:flex-start}
        @media(max-width:880px){.main{flex-direction:column}}
        .left{flex:0 0 320px}
        .mic-wrap{display:flex;flex-direction:column;align-items:center;gap:12px}
        .mic-btn{width:130px;height:130px;border-radius:999px;border:0;cursor:pointer;display:flex;align-items:center;justify-content:center;color:white;font-size:26px;background: linear-gradient(135deg, #7d9b1d, #6b8e23);;box-shadow:0 10px 30px #6b8e23;transition:transform .18s ease}
        .mic-btn:hover{transform:translateY(-4px);box-shadow:0 18px 40px #6b8e23}
        .mic-icon{font-size:40px}
        .mic-status{font-weight:600;color:#100f12;font-size:15px}
        .mic-btn.listening::after{content:'';position:absolute;inset:0;border-radius:999px;box-shadow:0 0 0 6px #6b8e23;animation:pulse 1.6s infinite}
        @keyframes pulse{0%{transform:scale(1);opacity:1}70%{transform:scale(1.15);opacity:0}100%{transform:scale(1.25);opacity:0}}
        .input-area{margin-top:18px}
        textarea#result{width:100%;min-height:120px;max-height:300px;resize:none;padding:14px;border-radius:12px;border:1px solid #eef2f7;background:#f7f8fa;font-size:15px;color:#111827;line-height:1.45}
        textarea#result::placeholder{color:#9ca3af}
        .send-btn{display:inline-block;margin-top:12px;padding:10px 18px;border-radius:12px;border:0;color:white; background:#6b8e23;cursor:pointer;font-weight:700;box-shadow:0 8px 30px #6b8e23;transition:transform .12s}
        .send-btn:hover{transform:translateY(-3px)}
        .btn-ghost{background:transparent;border:1px solid rgba(15,23,42,0.06);padding:8px 14px;border-radius:10px;cursor:pointer;color:var(--muted)}
        .right{flex:1}
        .hint{color:var(--muted);font-size:13px;margin-top:8px}
        .ai-card{background:var(--card);border-radius:12px;box-shadow:0 12px 40px rgba(2,6,23,0.06);padding:18px;opacity:0;transform:translateY(10px);transition:all .32s cubic-bezier(.2,.9,.2,1);border-left:6px solid transparent}
        .ai-card.show{opacity:1;transform:none}
        .card-title{display:flex;align-items:center;gap:12px;margin-bottom:10px}
        .card-title h3{margin:0;font-size:16px}
        .details-grid{display:flex;flex-wrap:wrap;gap:12px;margin-top:6px}
        .detail{display:flex;gap:12px;align-items:center;padding:10px;border-radius:10px;background:linear-gradient(180deg, #ffffff, #fbfdff);border:1px solid rgba(15,23,42,0.03);min-width:200px;flex:1 1 calc(50% - 12px)}
        .detail:first-of-type{margin-top:0}
        .icon{width:36px;height:36px;border-radius:8px;background:linear-gradient(180deg,rgba(79,70,229,0.08),rgba(124,58,237,0.06));display:flex;align-items:center;justify-content:center;font-size:18px}
        .meta{flex:1}
        .meta .k{font-weight:600}
        .meta .v{color:var(--muted);font-size:14px;margin-top:3px}
        .badge{display:inline-block;padding:6px 10px;background:linear-gradient(90deg, rgba(99,102,241,0.08), rgba(124,58,237,0.06));border-radius:999px;font-weight:600;color:var(--accent2);font-size:12px}
        @media(max-width:520px){.mic-btn{width:110px;height:110px}.wrap{padding:18px;border-radius:12px}textarea#result{min-height:100px}}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header">
            <div class="logo">AI</div>
            <div class="title">
                <h1>Voice Sowing Assistant</h1>
                <p>Ask about sowing operations for plots (e.g. "Plot A1 me sowing kab hui?")</p>
            </div>
        </div>

        <div class="main">
            <div class="left">
                <div class="mic-wrap">
                    <button id="mic" class="mic-btn" aria-pressed="false" title="Start/Stop listening">
                        <span id="micIcon" class="mic-icon">🎤</span>
                    </button>
                    <div id="status" class="mic-status">Start Listening</div>

                    <div class="input-area">
                        <textarea id="result" placeholder="Speak or type your query..."></textarea>
                        <div style="display:flex;gap:10px;align-items:center;justify-content:flex-start;margin-top:8px">
                            <button id="send" class="send-btn" disabled>Send Query</button>
                            <button id="clear" class="btn-ghost">Clear</button>
                        </div>
                        <div class="hint">Tip: Say <em>"Plot A1 me sowing kab hui"</em> or type your question.</div>
                    </div>
                </div>
            </div>

            <div class="right">
                <div id="resultArea" aria-live="polite"></div>
            </div>
        </div>
    </div>

    <script>
        const micBtn = document.getElementById('mic');
        const micIcon = document.getElementById('micIcon');
        const statusEl = document.getElementById('status');
        const resultEl = document.getElementById('result');
        const sendBtn = document.getElementById('send');
        const clearBtn = document.getElementById('clear');
        const resultArea = document.getElementById('resultArea');

        let recognition;
        let listening = false;

        function supportsSpeech(){
            return ('SpeechRecognition' in window) || ('webkitSpeechRecognition' in window);
        }

        if (supportsSpeech()){
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            recognition.lang = 'hi-IN';
            recognition.interimResults = true;
            recognition.maxAlternatives = 1;

            recognition.onstart = () => {
                listening = true;
                micBtn.classList.add('listening');
                statusAnimate(true);
                micBtn.setAttribute('aria-pressed','true');
            };

            recognition.onend = () => {
                listening = false;
                micBtn.classList.remove('listening');
                statusAnimate(false);
                micBtn.setAttribute('aria-pressed','false');
            };

            recognition.onresult = (event) => {
                let finalTranscript = '';
                let interimTranscript = '';
                for (let i = 0; i < event.results.length; ++i) {
                    const t = event.results[i][0].transcript;
                    if (event.results[i].isFinal) finalTranscript += t + ' ';
                    else interimTranscript += t;
                }
                resultEl.value = (finalTranscript + interimTranscript).trim();
                sendBtn.disabled = (resultEl.value.trim().length === 0);
                autoResize(resultEl);
            };

            recognition.onerror = (e) => {
                console.warn('Recognition error', e);
            };
        } else {
            statusEl.textContent = 'Speech not supported';
            micBtn.disabled = true;
        }

        function statusAnimate(isListening){
            if (isListening){
                let dots = 0;
                micBtn.dataset.pulse = '1';
                micBtn._interval = setInterval(()=>{
                    dots = (dots+1)%4;
                    statusEl.textContent = 'Listening' + '.'.repeat(dots);
                },400);
            } else {
                if (micBtn._interval) clearInterval(micBtn._interval);
                micBtn.dataset.pulse = '0';
                statusEl.textContent = (resultEl.value.trim().length>0)? 'Recognized' : 'Start Listening';
            }
        }

        micBtn.addEventListener('click', ()=>{
            if (!recognition) return;
            if (!listening){
                resultEl.value = '';
                sendBtn.disabled=true;
                autoResize(resultEl);
                recognition.start();
            }
            else recognition.stop();
        });

        sendBtn.addEventListener('click', sendQuery);
        clearBtn.addEventListener('click', ()=>{
            resultEl.value='';
            autoResize(resultEl);
            sendBtn.disabled=true;
            resultArea.innerHTML='';
            statusEl.textContent='Start Listening';
        });

        function autoResize(el){
            el.style.height = 'auto';
            el.style.height = (el.scrollHeight) + 'px';
        }

        resultEl.addEventListener('input', ()=>{
            autoResize(resultEl);
            sendBtn.disabled = (resultEl.value.trim().length === 0);
        });

        async function sendQuery(){
            const txt = resultEl.value.trim();
            if (!txt) return;
            showLoadingCard();
            try{
                const res = await fetch('/api/voice-sowing', {
                    method:'POST',
                    headers:{'Content-Type':'application/json','Accept':'application/json'},
                    body: JSON.stringify({ text: txt })
                });
                const j = await res.json();
                if (!j || !j.success) {
                    showErrorCard(j && j.message ? j.message : 'No records found');
                    speak('No sowing record found');
                    return;
                }
                renderAiCard(j);
                speak(j.text || 'Here are the sowing details');
            } catch (err){
                console.error(err);
                showErrorCard('Server error');
            }
        }

        function showLoadingCard(){
            resultArea.innerHTML = `<div class="ai-card show"><div class="card-title"><h3>Searching…</h3></div><div style="color:var(--muted)">Fetching sowing details…</div></div>`;
        }

        function showErrorCard(msg){
            resultArea.innerHTML = `<div class="ai-card show" style="border-left:6px solid #ef4444"><div class="card-title"><h3>⚠️ Error</h3></div><div style="color:var(--muted)">${escapeHtml(msg)}</div></div>`;
        }

        function renderAiCard(payload){
            const raw = payload.data || {};
            const titlePlot = (payload.parsed && payload.parsed.plot_name) ? payload.parsed.plot_name : (raw.plot_name_text || raw.plot_name || payload.plot || 'Unknown');
            const items = [
                {i:'📆', k:'Date', v: payload.data && payload.data.date ? payload.data.date : ''},
                {i:'🌾', k:'Variety', v: raw.variety || ''},
                {i:'🌱', k:'Seed Used', v: raw.seed_consumption ? (raw.seed_consumption + ' KG') : ''},
                {i:'🛠️', k:'Method', v: raw.sowing_method_id || raw.sowing_method || ''},
                {i:'📏', k:'Row Distance', v: raw.row_distance ? (raw.row_distance + ' cm') : ''},
                {i:'📌', k:'Depth', v: raw.sowing_depth ? (raw.sowing_depth + ' cm') : ''},
                {i:'🧪', k:'Area', v: raw.area || ''},
                {i:'🧱', k:'Area Covered', v: raw.area_covered || ''},
                {i:'🚜', k:'Tractor', v: raw.tractor_id || raw.tractor || ''},
                {i:'⚙️', k:'Machine', v: raw.machine_id || raw.machine || ''},
                {i:'⛽', k:'Diesel Used', v: raw.hsd_consumption ? (raw.hsd_consumption + ' L') : ''},
                {i:'⏰', k:'Start Time', v: raw.start_time || ''},
                {i:'🔚', k:'End Time', v: raw.end_time || ''},
                {i:'🕒', k:'Total Hours', v: raw.hours_used || raw.time_hrs || ''}
            ];
            let html = `<div class="ai-card show" style="border-left:6px solid; border-image: linear-gradient(180deg,var(--accent1),var(--accent2)) 1;"><div class="card-title"><div class="badge">🌱 Sowing Details</div><h3>– Plot ${escapeHtml(titlePlot)}</h3></div>`;
            let detailsHtml = '';
            for (const it of items){
                if (!it.v) continue;
                detailsHtml += `<div class="detail"><div class="icon">${it.i}</div><div class="meta"><div class="k">${escapeHtml(it.k)}</div><div class="v">${escapeHtml(it.v)}</div></div></div>`;
            }
            html += `<div class="details-grid">${detailsHtml}</div></div>`;
            resultArea.innerHTML = html;
            resultArea.scrollIntoView({behavior:'smooth', block:'center'});
        }

        function escapeHtml(s){
            if(!s && s!==0) return '';
            return String(s).replace(/[&<>"']/g,function(m){
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m];
            });
        }

        function speak(text){
            if (!('speechSynthesis' in window)) return;
            const u=new SpeechSynthesisUtterance(text);
            u.lang='hi-IN';
            speechSynthesis.cancel();
            speechSynthesis.speak(u);
        }

        autoResize(resultEl);
    </script>
</body>
</html>
