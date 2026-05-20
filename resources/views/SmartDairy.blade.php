<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Dairy AR Scanning Interface</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
  :root {
    --teal:#00c9e0; --teal-soft:#e0faff; --green:#2ecc8f;
    --orange:#ff8c42; --pink:#ff6eb0; --navy:#0f1f3d;
    --card:#ffffff; --border:#cce8f7; --muted:#5d7fa0;
    --shadow:0 4px 28px rgba(0,180,220,.10);
    --shadow-h:0 10px 40px rgba(0,180,220,.18);
    --r:20px; --font:'Outfit',sans-serif; --mono:'Space Mono',monospace;
  }
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  body{font-family:var(--font);background:linear-gradient(150deg,#e6f7ff 0%,#d4eefb 40%,#dff5ec 100%);color:var(--navy);min-height:100vh;-webkit-font-smoothing:antialiased}

  /* HEADER */
  header{text-align:center;padding:52px 20px 26px;position:relative}
  header::after{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 60% 50% at 50% 0%,rgba(0,201,224,.12) 0%,transparent 70%);pointer-events:none}
  .eyebrow{display:inline-flex;align-items:center;gap:7px;background:rgba(0,201,224,.1);border:1px solid rgba(0,201,224,.3);border-radius:100px;padding:5px 14px;margin-bottom:14px;font-size:.7rem;font-weight:600;color:var(--teal);letter-spacing:1.2px;text-transform:uppercase}
  h1{font-size:clamp(1.7rem,4.5vw,3rem);font-weight:900;background:linear-gradient(135deg,#00c9e0 0%,#0077a8 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;letter-spacing:-1px;line-height:1.05}
  .sub{font-size:.88rem;color:var(--muted);margin-top:10px;font-weight:400}
  .srow{display:flex;gap:20px;justify-content:center;margin-top:18px;flex-wrap:wrap}
  .spill{display:flex;align-items:center;gap:7px;font-family:var(--mono);font-size:.68rem;color:var(--muted)}
  .dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
  .dg{background:var(--green);box-shadow:0 0 7px var(--green);animation:blink 2s ease-in-out infinite}
  .do{background:var(--orange)}
  .dp{background:#a78bfa}
  @keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}

  /* WRAP */
  .wrap{max-width:1120px;margin:0 auto;padding:0 20px}
  .top-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
  .bot-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;padding-bottom:40px}
  @media(max-width:780px){.top-grid{grid-template-columns:1fr}}
  @media(max-width:900px){.bot-grid{grid-template-columns:1fr 1fr}}
  @media(max-width:560px){.bot-grid{grid-template-columns:1fr}}

  /* CARD */
  .card{background:var(--card);border-radius:var(--r);padding:24px;border:1.5px solid var(--border);box-shadow:var(--shadow);transition:transform .2s,box-shadow .2s}
  .card:hover{transform:translateY(-3px);box-shadow:var(--shadow-h)}

  /* CHIP */
  .chip{display:inline-flex;align-items:center;gap:6px;font-size:.65rem;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--teal);margin-bottom:16px}

  /* ANIMAL CARD */
  .aname{font-size:2.2rem;font-weight:900;color:var(--navy);line-height:1;letter-spacing:-.5px}
  .atag{font-family:var(--mono);font-size:.72rem;color:var(--muted);margin-top:5px;margin-bottom:20px}
  .flbl{font-size:.67rem;font-weight:600;color:var(--muted);letter-spacing:.6px;text-transform:uppercase;margin-bottom:3px}
  .fval{font-family:var(--mono);font-size:.9rem;font-weight:700;color:var(--navy)}
  .div{height:1px;background:var(--border);margin:16px 0}
  .mgrid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:18px}
  .mc{background:#f4fbff;border:1px solid var(--border);border-radius:14px;padding:12px 14px}
  .mc .flbl{margin-bottom:4px}
  .mc .fval{font-size:.82rem}
  .mc.pk{background:#fff3f8;border-color:#ffd6e8}
  .mc.gn{background:#ecfff5;border-color:#b3f0d4}
  .mc.pk .fval{color:#c93074}
  .mc.gn .fval{color:#0f7a45}

  /* AR CARD */
  .arc{background:#060f1e;border-color:#162843;overflow:hidden;display:flex;flex-direction:column}
  .arc .chip{color:#2dd4bf}
  .arc:hover{transform:none}
  .arv{flex:1;position:relative;border-radius:14px;overflow:hidden;min-height:300px;background:#030b16}

  /* Cow image */
  .cow-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;filter:saturate(.35) brightness(.65);transition:opacity .5s ease}
  .cow-img.ready{opacity:.6}

  /* Shimmer placeholder */
  .cow-placeholder{position:absolute;inset:0;background:linear-gradient(110deg,#0a1628 30%,#122040 50%,#0a1628 70%);background-size:200% 100%;animation:shimmer 1.5s linear infinite}
  @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}

  .ar-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(0,201,224,.07) 1px,transparent 1px),linear-gradient(90deg,rgba(0,201,224,.07) 1px,transparent 1px);background-size:28px 28px}
  .sline{position:absolute;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent 0%,#00c9e0 40%,#00e5ff 60%,transparent 100%);opacity:.8;animation:scan 3s linear infinite;box-shadow:0 0 10px #00c9e0}
  @keyframes scan{0%{top:8%}100%{top:92%}}

  .aphone{position:absolute;left:14%;top:22%;width:46px;height:74px;border:2.5px solid #00c9e0;border-radius:10px;background:rgba(0,201,224,.08);display:flex;align-items:center;justify-content:center;animation:fly 3.5s ease-in-out infinite;box-shadow:0 0 18px rgba(0,201,224,.25)}
  .aphone::after{content:'';position:absolute;bottom:7px;width:10px;height:2px;background:#00c9e0;border-radius:2px}
  @keyframes fly{0%,100%{transform:translateY(0)}50%{transform:translateY(-9px)}}

  .aqr{position:absolute;right:16%;top:16%;width:90px;height:90px;border:2.5px solid #00e5ff;border-radius:10px;display:flex;align-items:center;justify-content:center;background:rgba(0,229,255,.05);animation:qrp 2.2s ease-in-out infinite}
  @keyframes qrp{0%,100%{box-shadow:0 0 18px rgba(0,229,255,.4)}50%{box-shadow:0 0 38px rgba(0,229,255,.85)}}

  .br{position:absolute;width:22px;height:22px}
  .br.tl{top:10px;left:10px;border-top:2px solid #00e5ff;border-left:2px solid #00e5ff;border-radius:4px 0 0 0}
  .br.tr{top:10px;right:10px;border-top:2px solid #00e5ff;border-right:2px solid #00e5ff;border-radius:0 4px 0 0}
  .br.bl{bottom:10px;left:10px;border-bottom:2px solid #00e5ff;border-left:2px solid #00e5ff;border-radius:0 0 0 4px}
  .br.br2{bottom:10px;right:10px;border-bottom:2px solid #00e5ff;border-right:2px solid #00e5ff;border-radius:0 0 4px 0}

  .cts{position:absolute;left:50%;top:55%;transform:translate(-50%,-50%);width:22px;height:22px;border:1.5px solid rgba(0,229,255,.45);border-radius:50%}
  .ats{position:absolute;top:10px;left:50%;transform:translateX(-50%);font-family:var(--mono);font-size:.6rem;color:rgba(0,229,255,.75);letter-spacing:1.5px;white-space:nowrap}
  .hbadge{position:absolute;left:14px;background:rgba(0,0,0,.65);border:1px solid #00e5ff;border-radius:8px;padding:5px 12px;font-family:var(--mono);font-size:.6rem;color:#00e5ff;letter-spacing:1.2px}

  .arsrow{display:flex;gap:14px;margin-top:14px;flex-wrap:wrap}
  .arpill{display:flex;align-items:center;gap:5px;font-family:var(--mono);font-size:.62rem;color:#4ecdc4;letter-spacing:.5px}

  /* VITALS */
  .vrow{display:flex;align-items:center;gap:13px;background:#f5fbff;border:1px solid var(--border);border-radius:14px;padding:13px 15px;margin-bottom:10px;transition:background .2s}
  .vrow:hover{background:#eaf6ff}
  .vico{width:42px;height:42px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:1.15rem;flex-shrink:0}
  .vico.t{background:#fff3e6}
  .vico.h{background:#ffe8ea}
  .vico.v{background:#e6fff2}
  .vlbl{font-size:.75rem;font-weight:500;color:var(--muted)}
  .vval{font-family:var(--mono);font-size:1rem;font-weight:700;color:var(--navy)}
  .byes{margin-left:auto;background:var(--green);color:#fff;font-size:.6rem;font-weight:800;letter-spacing:1.2px;padding:5px 11px;border-radius:100px}
  .vnote{font-size:.68rem;color:var(--green);margin-top:1px}

  /* OWNERSHIP */
  .ob{background:#f5fbff;border:1px solid var(--border);border-radius:14px;padding:13px 15px;margin-bottom:10px}
  .ob .lbl{font-size:.68rem;font-weight:600;color:var(--muted);letter-spacing:.5px;display:flex;align-items:center;gap:5px;margin-bottom:5px}
  .ob .val{font-size:.92rem;font-weight:700;color:var(--navy)}
  .ob .sub{font-family:var(--mono);font-size:.7rem;color:var(--muted);margin-top:2px}
  .irow{display:flex;align-items:center;justify-content:space-between}
  .iact{display:flex;align-items:center;gap:6px;color:var(--green);font-weight:700;font-size:.85rem}

  /* RECORDS */
  .rb{width:100%;display:flex;align-items:center;gap:11px;padding:13px 15px;border-radius:14px;border:none;font-family:var(--font);font-size:.88rem;font-weight:600;color:var(--navy);cursor:pointer;margin-bottom:8px;transition:transform .15s,box-shadow .15s;text-align:left}
  .rb:hover{transform:translateX(5px);box-shadow:0 4px 18px rgba(0,0,0,.08)}
  .rb.b{background:#e0f6ff}
  .rb.p{background:#fff0f7}
  .rb.g{background:#e8fff3}
  .rb.s{background:#f2fbf5;border:1.5px solid var(--border);justify-content:space-between;cursor:default}
  .rb.s:hover{transform:none;box-shadow:none}
  .ri{font-size:1rem}

  /* SYNC BANNER */
  .sw{padding:0 20px 40px;max-width:1120px;margin:0 auto}
  .si{background:#fffde7;border:1.5px solid #ffe082;border-radius:16px;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
  .sl{display:flex;align-items:center;gap:10px}
  .sl span{font-size:.88rem;font-weight:600;color:#6b4d00}
  .sbadge{background:var(--orange);color:#fff;font-family:var(--mono);font-size:.68rem;font-weight:700;letter-spacing:1px;padding:6px 18px;border-radius:100px}
</style>
</head>
<body>

<header>
  <div class="eyebrow">
    <svg width="10" height="10" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#00c9e0" stroke-width="2.5"/><path d="M12 8v4l2.5 2.5" stroke="#00c9e0" stroke-width="2.5" stroke-linecap="round"/></svg>
    Live Monitoring System
  </div>
  <h1>Smart Dairy AR Scanning Interface</h1>
  <p class="sub">Real-time AI-powered health monitoring for livestock &nbsp;•&nbsp; Augmented Reality Technology</p>
  <div class="srow">
    <span class="spill"><span class="dot dg"></span>Real-time Health Analytics</span>
    <span class="spill"><span class="dot do"></span>Offline Sync Ready</span>
    <span class="spill"><span class="dot dp"></span>AR Mode Active</span>
  </div>
</header>

<div class="wrap">
<div class="top-grid">

  <!-- ANIMAL INFO -->
  <div class="card">
    <div class="chip">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" stroke="#00c9e0" stroke-width="2"/><path d="M12 8v4l3 3" stroke="#00c9e0" stroke-width="2" stroke-linecap="round"/></svg>
      Animal Information
    </div>
    <div class="aname">Lakshmi</div>
    <div class="atag">Tag #DRY-2024-447</div>

    <div class="chip">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="3" stroke="#00c9e0" stroke-width="2"/><path d="M9 9h6M9 13h4" stroke="#00c9e0" stroke-width="2" stroke-linecap="round"/></svg>
      Identification Details
    </div>
    <div style="margin-bottom:14px">
      <div class="flbl">Health ID / Unique ID</div>
      <div class="fval">NELESA FB44733</div>
    </div>
    <div class="div"></div>
    <div style="margin-bottom:6px">
      <div class="flbl">QR Code / RFID Number</div>
      <div class="fval">QR-8847-NLSA-2024</div>
    </div>

    <div class="chip" style="margin-top:20px">
      <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 10h16M4 14h10" stroke="#00c9e0" stroke-width="2" stroke-linecap="round"/></svg>
      Basic Details
    </div>
    <div class="mgrid">
      <div class="mc"><div class="flbl">Breed</div><div class="fval">Holstein Friesian</div></div>
      <div class="mc"><div class="flbl">Species</div><div class="fval">Cow</div></div>
      <div class="mc"><div class="flbl">Age</div><div class="fval">4 Yrs 8 Months</div></div>
      <div class="mc pk"><div class="flbl">Gender</div><div class="fval">Female</div></div>
      <div class="mc"><div class="flbl">Date of Birth</div><div class="fval">15 Jun 2020</div></div>
      <div class="mc gn"><div class="flbl">Lactation</div><div class="fval">3rd Lactation</div></div>
    </div>
  </div>

  <!-- AR CAMERA -->
  <div class="card arc">
    <div class="chip">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="20" height="14" rx="3" stroke="#2dd4bf" stroke-width="2"/><circle cx="12" cy="13" r="3" stroke="#2dd4bf" stroke-width="2"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2" stroke="#2dd4bf" stroke-width="2"/></svg>
      AR Live Camera Feed
    </div>
    <div class="arv">
      <!-- Shimmer while loading -->
      <div class="cow-placeholder" id="shimmer"></div>
      <!-- Real Holstein Friesian cow image (Unsplash CDN, fast) -->
      <img class="cow-img" id="cowImg"
        src="https://images.unsplash.com/photo-1570042225831-d98fa7577f1e?w=720&q=80&auto=format&fit=crop"
        alt="Holstein Friesian cow"
        loading="eager"
        decoding="async"
      >
      <div class="ar-grid"></div>
      <div class="sline"></div>
      <div class="br tl"></div>
      <div class="br tr"></div>
      <div class="br bl"></div>
      <div class="br br2"></div>
      <div class="ats" id="arTs">SCAN · --:--:--</div>
      <div class="aphone">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M9 1h6a2 2 0 012 2v18a2 2 0 01-2 2H9a2 2 0 01-2-2V3a2 2 0 012-2z" stroke="#00c9e0" stroke-width="2"/></svg>
      </div>
      <div class="aqr">
        <svg width="48" height="48" viewBox="0 0 48 48" fill="none">
          <rect x="3" y="3" width="17" height="17" rx="2" stroke="#00e5ff" stroke-width="2"/>
          <rect x="7" y="7" width="9" height="9" fill="#00e5ff"/>
          <rect x="28" y="3" width="17" height="17" rx="2" stroke="#00e5ff" stroke-width="2"/>
          <rect x="32" y="7" width="9" height="9" fill="#00e5ff"/>
          <rect x="3" y="28" width="17" height="17" rx="2" stroke="#00e5ff" stroke-width="2"/>
          <rect x="7" y="32" width="9" height="9" fill="#00e5ff"/>
          <rect x="28" y="28" width="9" height="9" fill="#00e5ff"/>
          <rect x="39" y="28" width="6" height="6" fill="#00e5ff"/>
          <rect x="28" y="39" width="9" height="6" fill="#00e5ff"/>
        </svg>
      </div>
      <div class="cts"></div>
      <!-- Breed at cow knee level -->
      <div class="hbadge" style="top:70%;left:50%;transform:translateX(-50%);white-space:nowrap">🐄 BREED · HOLSTEIN FRIESIAN</div>
      <!-- Bottom row: all 3 badges -->
      <div style="position:absolute;bottom:14px;left:14px;right:14px;display:flex;gap:8px;align-items:center;">
        <div class="hbadge" style="position:static;transform:none;flex-shrink:0">AR HOLOGRAPHIC ✓</div>
        <div class="hbadge" style="position:static;transform:none;white-space:nowrap;flex-shrink:0">🌡️ TEMP · <span id="arTemp">102.5°F</span></div>
        <div class="hbadge" style="position:static;transform:none;white-space:nowrap;flex-shrink:0">❤️ HR · <span id="arHR">88 BPM</span></div>
      </div>
    </div>
    <div class="arsrow">
      <span class="arpill"><span class="dot dg"></span>Real-time Health Analytics</span>
      <span class="arpill"><span class="dot do"></span>Offline Sync</span>
      <span class="arpill"><span class="dot dp"></span>QR Detected</span>
    </div>
  </div>

</div>

<!-- BOTTOM GRID -->
<div class="bot-grid">

  <!-- VITALS -->
  <div class="card">
    <div class="chip">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" stroke="#ff5c5c" stroke-width="2"/></svg>
      Health Vitals
    </div>
    <div class="vrow">
      <div class="vico t">🌡️</div>
      <div><div class="vlbl">Temperature</div><div class="vval" id="tempVal">102.5°F</div></div>
    </div>
    <div class="vrow">
      <div class="vico h">❤️</div>
      <div><div class="vlbl">Heart Rate</div><div class="vval" id="heartVal">88 BPM</div></div>
    </div>
    <div class="vrow">
      <div class="vico v">✅</div>
      <div style="flex:1"><div class="vlbl">Vaccinated</div><div class="vnote">SC: 11 Antal Given</div></div>
      <span class="byes">YES</span>
    </div>
  </div>

  <!-- OWNERSHIP -->
  <div class="card">
    <div class="chip">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" stroke="#2ecc8f" stroke-width="2"/><circle cx="12" cy="10" r="3" stroke="#2ecc8f" stroke-width="2"/></svg>
      Ownership &amp; Location
    </div>
    <div class="ob">
      <div class="lbl">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="#5d7fa0" stroke-width="2"/><circle cx="12" cy="7" r="4" stroke="#5d7fa0" stroke-width="2"/></svg>
        Ownership Details
      </div>
      <div class="val">Rajesh Kumar Farm</div>
      <div class="sub">Owner ID: RK-8847</div>
    </div>
    <div class="ob">
      <div class="lbl">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" stroke="#5d7fa0" stroke-width="2"/></svg>
        Farm Location
      </div>
      <div class="val">Mehsana District, Gujarat</div>
      <div class="sub">PIN: 384001</div>
    </div>
    <div class="ob">
      <div class="lbl">Insurance Status</div>
      <div class="irow"><div class="iact"><span class="dot dg"></span>Active · Insured</div></div>
      <div class="sub" style="margin-top:4px">Policy: LIC-AG-2024-334</div>
    </div>
  </div>

  <!-- RECORDS -->
  <div class="card">
    <div class="chip">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="#00c9e0" stroke-width="2"/><polyline points="14 2 14 8 20 8" stroke="#00c9e0" stroke-width="2"/></svg>
      Health Records
    </div>
    <button class="rb b"><span class="ri">📊</span>Checkup Records</button>
    <button class="rb p"><span class="ri">🧬</span>Breeding History</button>
    <button class="rb g"><span class="ri">💉</span>Vaccination Log</button>
    <div class="rb s">
      <div style="display:flex;align-items:center;gap:8px">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M23 4v6h-6M1 20v-6h6" stroke="#2ecc8f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15" stroke="#2ecc8f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span style="font-size:.82rem;color:#0f7a45;font-weight:600">Offline Mode Synced</span>
      </div>
      <span class="dot dg"></span>
    </div>
  </div>

</div>
</div>

<!-- SYNC BANNER -->
<div class="sw">
  <div class="si">
    <div class="sl">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M23 4v6h-6M1 20v-6h6" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      <span>Data Synced Locally</span>
    </div>
    <span class="sbadge">OFFLINE MODE</span>
  </div>
</div>

<script>
  // Cow image load handling
  const img = document.getElementById('cowImg');
  const shimmer = document.getElementById('shimmer');
  function onImgLoad() {
    img.classList.add('ready');
    shimmer.style.display = 'none';
  }
  if (img.complete && img.naturalWidth > 0) {
    onImgLoad();
  } else {
    img.addEventListener('load', onImgLoad);
    img.addEventListener('error', () => { shimmer.style.animation = 'none'; shimmer.style.background = '#0a1628'; });
  }

  // Live AR clock
  function tick() {
    const t = new Date().toLocaleTimeString('en-IN',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:true});
    document.getElementById('arTs').textContent = 'SCAN · ' + t;
  }
  tick(); setInterval(tick, 1000);

  // Live vitals
  setInterval(() => {
    const bpm = (86 + Math.floor(Math.random()*5)) + ' BPM';
    document.getElementById('heartVal').textContent = bpm;
    document.getElementById('arHR').textContent = bpm;
  }, 3000);
  setInterval(() => {
    const temp = (102.2 + Math.random()*.6).toFixed(1) + '°F';
    document.getElementById('tempVal').textContent = temp;
    document.getElementById('arTemp').textContent = temp;
  }, 5000);
</script>
</body>
</html>
