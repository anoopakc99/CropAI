@extends('layouts.app')
@section('title','🌿 AI Crop Health — Smart Monitor')

@section('styles')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
/* ════ DESIGN TOKENS ════ */
:root{
  --g900:#1B5E20;--g800:#2E7D32;--g700:#388E3C;--g500:#4CAF50;--g200:#C8E6C9;
  --teal:#00897B;--amber:#F57C00;--red:#C62828;--indigo:#4F46E5;
  --bg:#eef4ee;--bg2:#f6fbf6;--card:rgba(255,255,255,.92);
  --border:#ddeedd;--txt:#1a2e1a;--sub:#546E7A;
  --sh:0 2px 20px rgba(0,0,0,.09);--sh2:0 8px 32px rgba(0,0,0,.13);
  --r:16px;--r2:10px;--r3:8px;
  --map-h:calc(100vh - 290px);
  --transition: all .35s cubic-bezier(.4,0,.2,1);
}
body.dm{
  --bg:#0f1a0f;--bg2:#162016;--card:rgba(20,32,20,.94);
  --border:#253525;--txt:#ddeedd;--sub:#90A4AE;
  --sh:0 2px 20px rgba(0,0,0,.4);--sh2:0 8px 32px rgba(0,0,0,.5);
}
*{font-family:'Inter',sans-serif!important;box-sizing:border-box;margin:0;padding:0;}

/* ════ PAGE ════ */
.sp{background:var(--bg);min-height:100vh;padding-bottom:28px;}

/* ════ TOP BAR ════ */
.tbar{
  background:linear-gradient(100deg,#1B5E20 0%,#2E7D32 55%,#00897B 100%);
  padding:11px 22px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;
  box-shadow:0 3px 20px rgba(27,94,32,.45);position:relative;z-index:10;
}
.tbar-brand{color:#fff;font-weight:800;font-size:1.05rem;display:flex;align-items:center;gap:8px;letter-spacing:-.3px;}
.tbar-sub{color:rgba(255,255,255,.6);font-size:.7rem;font-weight:400;}
.tbar-right{margin-left:auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.live-pill{background:rgba(76,175,80,.18);border:1px solid rgba(76,175,80,.38);color:#a5d6a7;
  font-size:.67rem;font-weight:700;padding:4px 11px;border-radius:50px;display:flex;align-items:center;gap:5px;}
.dot{width:6px;height:6px;border-radius:50%;background:#69F0AE;
  animation:_dot 1.8s ease-in-out infinite;}
@keyframes _dot{0%{box-shadow:0 0 0 0 rgba(105,240,174,.7)}70%{box-shadow:0 0 0 8px transparent}100%{box-shadow:0 0 0 0 transparent}}
.tbar-btn{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.18);
  color:rgba(255,255,255,.88);border-radius:8px;padding:5px 13px;font-size:.74rem;
  cursor:pointer;transition:var(--transition);font-weight:600;}
.tbar-btn:hover{background:rgba(255,255,255,.22);}

/* ════ STAT STRIP ════ */
.stat-strip{display:flex;gap:10px;padding:14px 22px 0;flex-wrap:wrap;}
.sc{flex:1;min-width:120px;background:var(--card);border-radius:12px;padding:13px 15px;
  box-shadow:var(--sh);border:1px solid var(--border);position:relative;overflow:hidden;
  backdrop-filter:blur(14px);transition:var(--transition);}
.sc:hover{transform:translateY(-2px);box-shadow:var(--sh2);}
.sc::after{content:'';position:absolute;right:-12px;bottom:-12px;width:54px;height:54px;border-radius:50%;opacity:.07;}
.sc.c1{border-top:3px solid var(--g800);}  .sc.c1::after{background:var(--g800);}
.sc.c2{border-top:3px solid var(--teal);}  .sc.c2::after{background:var(--teal);}
.sc.c3{border-top:3px solid var(--amber);} .sc.c3::after{background:var(--amber);}
.sc.c4{border-top:3px solid var(--red);}   .sc.c4::after{background:var(--red);}
.sc.c5{border-top:3px solid var(--indigo);}.sc.c5::after{background:var(--indigo);}
.sc-ico{font-size:1.1rem;margin-bottom:5px;}
.sc-val{font-size:1.6rem;font-weight:900;line-height:1;letter-spacing:-1px;color:var(--txt);}
.sc-lbl{font-size:.59rem;text-transform:uppercase;letter-spacing:.6px;color:var(--sub);font-weight:700;margin-top:3px;}

/* ════ TIMELINE ════ */
.tlbar{
  background:var(--card);border-bottom:1px solid var(--border);
  padding:10px 22px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;
  backdrop-filter:blur(14px);position:sticky;top:0;z-index:9;
}
.tl-lbl{font-size:.72rem;font-weight:700;color:var(--g800);white-space:nowrap;}
.tl-ctrl{display:flex;align-items:center;gap:8px;flex:1;min-width:220px;}
.pbtn{width:30px;height:30px;border-radius:50%;border:none;background:var(--g800);color:#fff;
  cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.8rem;
  transition:var(--transition);flex-shrink:0;box-shadow:0 2px 8px rgba(46,125,50,.4);}
.pbtn:hover{background:var(--g900);transform:scale(1.1);}
#tls{
  flex:1;-webkit-appearance:none;height:5px;border-radius:3px;
  background:linear-gradient(to right,var(--g800) 0%,var(--border) 0%);outline:none;cursor:pointer;
}
#tls::-webkit-slider-thumb{-webkit-appearance:none;width:16px;height:16px;border-radius:50%;
  background:var(--g800);border:2.5px solid #fff;box-shadow:0 1px 6px rgba(0,0,0,.25);cursor:grab;}
.tl-date{font-size:.72rem;font-weight:700;color:var(--g800);min-width:90px;text-align:right;}
.spd{border:1.5px solid var(--border);border-radius:7px;padding:3px 8px;font-size:.72rem;
  color:var(--g800);background:var(--card);cursor:pointer;}
.lbtn{border:1.5px solid var(--border);border-radius:20px;padding:4px 12px;font-size:.7rem;
  font-weight:700;color:var(--sub);background:var(--card);cursor:pointer;transition:var(--transition);}
.lbtn:hover,.lbtn.on{background:var(--g800);color:#fff;border-color:var(--g800);}
.opc{display:flex;align-items:center;gap:5px;font-size:.68rem;color:var(--sub);}
.opc input[type=range]{width:65px;height:3px;cursor:pointer;}

/* ════ 3-PANEL ════ */
.dbody{
  display:grid;grid-template-columns:255px 1fr 305px;
  gap:0;padding:14px 22px;align-items:stretch;
}
@media(max-width:1100px){.dbody{grid-template-columns:215px 1fr 265px;}}
@media(max-width:820px){
  .dbody{grid-template-columns:1fr;padding:10px;}
  #mapcol{order:1;min-height:380px;}
  .lp{order:2;border-radius:var(--r)!important;border!important;}
  .rp{order:3;border-radius:var(--r)!important;}
}

/* ════ LEFT PANEL ════ */
.lp{
  background:var(--card);border-radius:var(--r) 0 0 var(--r);
  border:1px solid var(--border);border-right:none;
  display:flex;flex-direction:column;overflow:hidden;backdrop-filter:blur(14px);
}
.phd{
  padding:11px 14px;border-bottom:1px solid var(--border);
  font-size:.76rem;font-weight:800;color:var(--g800);
  display:flex;align-items:center;justify-content:space-between;
}
.phd-count{font-size:.65rem;font-weight:600;color:var(--sub);background:var(--bg2);
  padding:2px 7px;border-radius:20px;border:1px solid var(--border);}
.fpills{display:flex;flex-wrap:wrap;gap:4px;padding:9px 12px;border-bottom:1px solid var(--border);}
.fp{border:1.5px solid var(--border);border-radius:20px;padding:3px 9px;font-size:.65rem;
  font-weight:700;color:var(--sub);background:var(--card);cursor:pointer;transition:.15s;}
.fp:hover{opacity:.8;}
.fp.all.on{color:var(--g800);background:#e8f5e9;border-color:var(--g800);}
.fp.excellent.on{color:#00695c;background:#e0f7fa;border-color:#00897B;}
.fp.good.on{color:var(--g700);background:#e8f5e9;border-color:var(--g700);}
.fp.moderate.on{color:#E65100;background:#fff3e0;border-color:#FF8F00;}
.fp.warning.on{color:#bf360c;background:#fbe9e7;border-color:#E64A19;}
.fp.critical.on{color:var(--red);background:#ffebee;border-color:var(--red);}
.fp.no_data.on{color:#455A64;background:#f5f5f5;border-color:#78909C;}
.plist{flex:1;overflow-y:auto;padding:5px;scrollbar-width:thin;}
.plist::-webkit-scrollbar{width:4px;}
.plist::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px;}
.pi{
  padding:8px 10px;border-radius:var(--r3);cursor:pointer;
  border:1.5px solid transparent;margin-bottom:3px;
  display:flex;align-items:center;gap:9px;transition:.18s;
}
.pi:hover{background:rgba(46,125,50,.07);border-color:var(--border);}
.pi.active{background:rgba(46,125,50,.11);border-color:var(--g700);box-shadow:0 2px 8px rgba(46,125,50,.12);}
.pi-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;box-shadow:0 0 0 2px rgba(0,0,0,.07);}
.pi-name{font-size:.76rem;font-weight:700;color:var(--txt);line-height:1.25;}
.pi-sub{font-size:.61rem;color:var(--sub);}
.pi-ndvi{margin-left:auto;font-size:.73rem;font-weight:800;}
.pi-trend{font-size:.58rem;margin-left:4px;}

/* ════ MAP COL ════ */
#mapcol{border:1px solid var(--border);border-left:none;border-right:none;position:relative;overflow:hidden;}
#map{width:100%;height:var(--map-h);min-height:420px;}

/* smooth polygon transitions via svg */
.leaflet-interactive{transition:fill .4s ease,fill-opacity .4s ease,stroke .3s ease,stroke-width .2s ease!important;}

/* Map loader */
.mloader{position:absolute;inset:0;background:rgba(15,26,15,.72);
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  z-index:9999;transition:opacity .4s;pointer-events:none;}
.mloader.gone{opacity:0;}
.spin{width:38px;height:38px;border:4px solid rgba(105,240,174,.25);border-top-color:#69F0AE;
  border-radius:50%;animation:_sp .7s linear infinite;margin-bottom:10px;}
@keyframes _sp{to{transform:rotate(360deg)}}
.spin-lbl{color:rgba(255,255,255,.7);font-size:.72rem;font-weight:600;}

/* Tooltip */
.leaflet-tt{background:rgba(15,26,15,.9)!important;border:1px solid rgba(105,240,174,.25)!important;
  border-radius:8px!important;color:#e0f4e0!important;font-size:.72rem!important;
  padding:6px 10px!important;box-shadow:0 4px 16px rgba(0,0,0,.3)!important;}
.leaflet-tt b{color:#69F0AE;}

/* glow for critical */
@keyframes _glow{
  0%,100%{box-shadow:0 0 0 0 rgba(244,67,54,.4);}
  50%{box-shadow:0 0 14px 4px rgba(244,67,54,.5);}
}
@keyframes _bdash{
  0%,100%{stroke-dashoffset:0;}50%{stroke-dashoffset:12;}
}
.lblink path{animation:_bdash 1.2s ease-in-out infinite;}

/* Floating legend — rendered via L.control, Leaflet handles positioning */
.mleg{
  background:rgba(255,255,255,.94);backdrop-filter:blur(10px);
  border-radius:10px;padding:10px 13px;font-size:.67rem;
  box-shadow:0 3px 14px rgba(0,0,0,.15);border:1px solid rgba(0,0,0,.08);
}
body.dm .mleg{background:rgba(20,32,20,.94);border-color:var(--border);}
.lr{display:flex;align-items:center;gap:6px;padding:2px 0;}
.ld{width:11px;height:11px;border-radius:3px;flex-shrink:0;}
.ll{font-size:.65rem;color:var(--txt);}
.ll span{color:var(--sub);}

/* ════ RIGHT PANEL ════ */
.rp{
  background:var(--card);border-radius:0 var(--r) var(--r) 0;
  border:1px solid var(--border);border-left:none;
  overflow-y:auto;display:flex;flex-direction:column;backdrop-filter:blur(14px);
  scrollbar-width:thin;
}
.rp::-webkit-scrollbar{width:4px;}
.rp::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px;}

/* Farm summary panel (default state) */
.farm-summary{padding:14px 16px;flex:1;}
.farm-title{font-size:.9rem;font-weight:800;color:var(--g800);margin-bottom:2px;
  display:flex;align-items:center;gap:6px;}
.farm-sub{font-size:.68rem;color:var(--sub);margin-bottom:14px;}
.farm-ring-wrap{display:flex;justify-content:center;margin-bottom:14px;}
.farm-ring{position:relative;width:100px;height:100px;}
.farm-ring svg{transform:rotate(-90deg);}
.farm-ring-val{position:absolute;inset:0;display:flex;flex-direction:column;
  align-items:center;justify-content:center;}
.farm-ring-num{font-size:1.1rem;font-weight:900;color:var(--g800);}
.farm-ring-lbl{font-size:.52rem;text-transform:uppercase;color:var(--sub);letter-spacing:.5px;}
.fstat-row{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px;}
.fstat{flex:1;min-width:60px;text-align:center;background:var(--bg2);border-radius:var(--r3);
  padding:8px 5px;border:1px solid var(--border);}
.fstat-n{font-size:1.05rem;font-weight:800;}
.fstat-l{font-size:.58rem;text-transform:uppercase;letter-spacing:.4px;color:var(--sub);font-weight:600;}
.farm-insight-box{background:linear-gradient(135deg,rgba(27,94,32,.06),rgba(0,137,123,.04));
  border:1px solid rgba(27,94,32,.14);border-radius:var(--r3);padding:10px 12px;margin-bottom:10px;
  font-size:.75rem;color:var(--txt);line-height:1.6;}
.health-bar-wrap{margin-bottom:10px;}
.hb-lbl{font-size:.63rem;font-weight:700;color:var(--sub);text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;}
.hbar{height:8px;border-radius:4px;display:flex;overflow:hidden;gap:1px;}
.hbs{height:100%;border-radius:2px;transition:width .8s ease;}

/* Shimmer */
.shim{height:12px;border-radius:6px;
  background:linear-gradient(90deg,var(--border) 25%,var(--bg2) 50%,var(--border) 75%);
  background-size:200% 100%;animation:_sh 1.4s infinite;margin:5px 0;}
@keyframes _sh{0%{background-position:200% 0}100%{background-position:-200% 0}}

/* Plot insight */
.aic{padding:14px 16px;flex:1;}
.ai-pname{font-size:.95rem;font-weight:800;color:var(--txt);}
.ai-psub{font-size:.68rem;color:var(--sub);margin-bottom:12px;}
.rbadge{display:inline-flex;align-items:center;gap:5px;padding:5px 13px;border-radius:50px;
  font-size:.71rem;font-weight:800;text-transform:uppercase;letter-spacing:.6px;margin-bottom:11px;}
.rl{background:#e8f5e9;color:#1b5e20;}
.rm{background:#fffde7;color:#f57f17;}
.rh{background:#fce4ec;color:#c62828;}
.rc{background:#ffebee;color:#b71c1c;animation:_glow .8s ease infinite;}
.ru{background:#f5f5f5;color:#607d8b;}
.dnut-wrap{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.dnut{width:72px;height:72px;position:relative;flex-shrink:0;}
.dnut svg{transform:rotate(-90deg);display:block;}
.dnut-v{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.dnut-n{font-size:.85rem;font-weight:900;line-height:1;}
.dnut-s{font-size:.5rem;text-transform:uppercase;color:var(--sub);}
.sbdg{display:inline-flex;align-items:center;gap:2px;padding:2px 8px;border-radius:20px;
  font-size:.63rem;font-weight:800;text-transform:uppercase;}
.se{background:#e0f7fa;color:#00695c;} .sg{background:#e8f5e9;color:#2e7d32;}
.sm{background:#fffde7;color:#f57f17;} .sw{background:#fbe9e7;color:#bf360c;}
.sk{background:#ffebee;color:#b71c1c;} .sn{background:#f5f5f5;color:#607d8b;}
.trnd{display:inline-flex;align-items:center;gap:2px;padding:2px 7px;border-radius:20px;font-size:.64rem;font-weight:700;}
.tu{background:#e8f5e9;color:#1b5e20;} .td{background:#ffebee;color:#c62828;} .ts{background:#f5f5f5;color:#78909c;}
.narr{background:linear-gradient(135deg,rgba(27,94,32,.06),rgba(0,137,123,.04));
  border:1px solid rgba(27,94,32,.15);border-radius:var(--r3);
  padding:10px 12px;font-size:.75rem;color:var(--txt);line-height:1.65;margin-bottom:8px;}
.iblk{background:var(--bg2);border-radius:var(--r3);padding:9px 12px;margin-bottom:7px;border:1px solid var(--border);}
.ititle{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px;color:var(--sub);margin-bottom:4px;}
.ival{font-size:.76rem;color:var(--txt);line-height:1.45;}
.irrb{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:.65rem;font-weight:700;}
.iok{background:#e8f5e9;color:#1b5e20;} .ireq{background:#fff3e0;color:#e65100;} .iurg{background:#ffebee;color:#c62828;}
.cw{padding:0 16px 12px;}
.clbl{font-size:.61rem;color:var(--sub);font-weight:700;text-transform:uppercase;letter-spacing:.4px;padding-bottom:4px;}

/* dark tweaks */
body.dm .tbar{background:linear-gradient(100deg,#0d200d,#16301a,#0a1f1c);}
body.dm #map{filter:brightness(.9) saturate(.85);}
body.dm .mleg{color:var(--txt);}
</style>
@endsection

@section('content')
<div class="sp" id="satPage">

{{-- TOP BAR --}}
<div class="tbar">
  <div class="tbar-brand">
    🌿 AI Crop Health
    <span class="tbar-sub">· {{ $siteName }} · Sentinel-2 NDVI</span>
  </div>
  <div class="tbar-right">
    <span class="live-pill"><span class="dot"></span>LIVE <span id="syncT" style="color:#fff;font-weight:800;margin-left:2px;"></span></span>
    <span style="color:rgba(255,255,255,.45);font-size:.63rem;">Auto-refresh 5 min</span>
    <button class="tbar-btn" id="dmBtn" onclick="toggleDark()">🌙 Dark</button>
  </div>
</div>

{{-- STAT STRIP --}}
<div class="stat-strip">
  <div class="sc c1"><div class="sc-ico">🌾</div><div class="sc-val" id="sT">—</div><div class="sc-lbl">Total Plots</div></div>
  <div class="sc c2"><div class="sc-ico">💚</div><div class="sc-val" id="sH" style="color:var(--teal)">—</div><div class="sc-lbl">Healthy</div></div>
  <div class="sc c3"><div class="sc-ico">⚠️</div><div class="sc-val" id="sW" style="color:var(--amber)">—</div><div class="sc-lbl">Attention</div></div>
  <div class="sc c4"><div class="sc-ico">❌</div><div class="sc-val" id="sN" style="color:var(--red)">—</div><div class="sc-lbl">No Data</div></div>
  <div class="sc c5"><div class="sc-ico">📡</div><div class="sc-val" id="sA" style="color:var(--indigo)">—</div><div class="sc-lbl">Farm Avg NDVI</div></div>
</div>

{{-- TIMELINE --}}
<div class="tlbar" style="margin-top:14px;">
  <span class="tl-lbl">📅 Timeline:</span>
  <div class="tl-ctrl">
    <button class="pbtn" id="playBtn" onclick="togglePlay()"><i class="fas fa-play" id="playIco"></i></button>
    <input type="range" id="tls" min="0" max="0" value="0">
    <span class="tl-date" id="tlDate">Latest</span>
    <select class="spd" id="spdSel">
      <option value="1800">1×</option>
      <option value="900">2×</option>
      <option value="400">4×</option>
    </select>
  </div>
  <div style="display:flex;gap:5px;flex-wrap:wrap;">
    <button class="lbtn" data-l="sat" onclick="setLayer('sat',this)">🛰️ Satellite</button>
    <button class="lbtn on" data-l="ndvi" onclick="setLayer('ndvi',this)">🌡️ NDVI Heat</button>
    <button class="lbtn" data-l="hybrid" onclick="setLayer('hybrid',this)">🗺️ Hybrid</button>
  </div>
  <div class="opc" title="Heat opacity">🌫️<input type="range" id="hopo" min="5" max="95" value="70" oninput="setHeatOp(this.value)"></div>
  {{-- Date jump picker --}}
  <div style="display:flex;align-items:center;gap:5px;font-size:.7rem;color:var(--sub);">
    <span>📅</span>
    <select id="datePick" class="spd" style="min-width:100px;" onchange="jumpToDate(this.value)">
      <option value="">Latest</option>
    </select>
  </div>
</div>

{{-- 3-PANEL --}}
<div class="dbody">

  {{-- LEFT --}}
  <div class="lp">
    <div class="phd">
      <span><i class="fas fa-seedling" style="color:var(--g700);margin-right:5px;"></i>Plot List</span>
      <span class="phd-count" id="pCount">—</span>
    </div>
    <div class="fpills" id="fpills">
      <button class="fp all on"    data-f="all"       onclick="applyFilter('all',this)">All</button>
      <button class="fp excellent" data-f="excellent" onclick="applyFilter('excellent',this)">💚 Excellent</button>
      <button class="fp good"      data-f="good"      onclick="applyFilter('good',this)">🟢 Good</button>
      <button class="fp moderate"  data-f="moderate"  onclick="applyFilter('moderate',this)">🟡 Moderate</button>
      <button class="fp critical"  data-f="critical"  onclick="applyFilter('critical',this)">🔴 Critical</button>
      <button class="fp no_data"   data-f="no_data"   onclick="applyFilter('no_data',this)">⬜ No Data</button>
    </div>
    <div class="plist" id="plist">
      @for($i=0;$i<7;$i++)<div class="shim" style="height:44px;border-radius:10px;margin:3px 4px;"></div>@endfor
    </div>
  </div>

  {{-- MAP --}}
  <div id="mapcol">
    <div id="map"></div>
    <div class="mloader" id="mload"><div class="spin"></div><span class="spin-lbl">Loading NDVI…</span></div>
    {{-- Legend is added via Leaflet L.Control in JS below --}}
  </div>

  {{-- RIGHT --}}
  <div class="rp" id="rp">
    <div class="phd"><i class="fas fa-robot" style="color:var(--g700);margin-right:5px;"></i>AI Insight</div>

    {{-- Farm summary (default) --}}
    <div class="farm-summary" id="farmSum">
      <div class="farm-title">🌾 Farm Overview</div>
      <div class="farm-sub" id="farmSumSub">Loading farm analysis…</div>
      <div class="farm-ring-wrap">
        <div class="farm-ring">
          <svg viewBox="0 0 100 100" width="100" height="100">
            <circle cx="50" cy="50" r="40" fill="none" stroke="var(--border)" stroke-width="10"/>
            <circle id="farmArc" cx="50" cy="50" r="40" fill="none" stroke="var(--g800)" stroke-width="10"
              stroke-linecap="round" stroke-dasharray="251.2" stroke-dashoffset="251.2"
              style="transition:stroke-dashoffset 1.2s ease,stroke .4s ease"/>
          </svg>
          <div class="farm-ring-val">
            <span class="farm-ring-num" id="farmAvgV">—</span>
            <span class="farm-ring-lbl">Avg NDVI</span>
          </div>
        </div>
      </div>
      <div class="fstat-row">
        <div class="fstat"><div class="fstat-n" id="fse" style="color:#00897B">—</div><div class="fstat-l">Excellent</div></div>
        <div class="fstat"><div class="fstat-n" id="fsg" style="color:#388E3C">—</div><div class="fstat-l">Good</div></div>
        <div class="fstat"><div class="fstat-n" id="fsm" style="color:#F57C00">—</div><div class="fstat-l">Moderate</div></div>
        <div class="fstat"><div class="fstat-n" id="fsw" style="color:#E64A19">—</div><div class="fstat-l">Warning</div></div>
        <div class="fstat"><div class="fstat-n" id="fsc" style="color:#C62828">—</div><div class="fstat-l">Critical</div></div>
      </div>
      <div class="health-bar-wrap">
        <div class="hb-lbl">Health Distribution</div>
        <div class="hbar" id="hbar"></div>
      </div>
      <div class="farm-insight-box" id="farmNarr">🤖 Analyzing farm health patterns…</div>
      <div style="font-size:.63rem;color:var(--sub);text-align:center;padding-top:4px;">
        Click any plot on the map or list for detailed analysis
      </div>
    </div>

    {{-- Shimmer --}}
    <div id="aiShim" style="display:none;padding:14px;">
      <div class="shim" style="height:16px;width:55%;"></div>
      <div class="shim" style="height:11px;width:35%;margin-top:8px;"></div>
      <div class="shim" style="height:48px;margin-top:12px;"></div>
      <div class="shim" style="height:38px;margin-top:8px;"></div>
      <div class="shim" style="height:38px;margin-top:8px;"></div>
      <div class="shim" style="height:72px;margin-top:8px;"></div>
    </div>

    {{-- Plot insight --}}
    <div id="aiC" style="display:none;" class="aic"></div>
    <div class="cw" id="chartW" style="display:none;">
      <div class="clbl">NDVI Trend</div>
      <canvas id="mc" height="78"></canvas>
    </div>
  </div>
</div>

</div>{{-- /sp --}}
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
/* ── CONFIG ── */
const GEO_URL     = '/ai-crop-health-geojson';
const INS_URL     = '/ai-crop-health-insight';
const DATES_URL   = '/ai-crop-health-dates';

const SC = {excellent:'#1a7a1a', good:'#4CAF50', moderate:'#FFC107', critical:'#F44336', no_data:'#9E9E9E'};
const SL = {excellent:'💚 Excellent', good:'🟢 Good', moderate:'🟡 Moderate', critical:'🔴 Critical', no_data:'⬜ No Data'};

/* ── STATE ── */
let allF=[], heatL=null, layerMode='sat';
let activeFilter='all', selPlot=null, currentDate=null;
let scanDates=[], playing=false, playTm=null, miniCh=null;
let initialFitDone=false; // fit bounds only once
let tlTimeout=null;        // throttle timeline drag
let layerMap={};           // plot_id → Leaflet layer (duplicate guard)

/* ── MAP INIT ── */
const map = L.map('map',{zoomControl:true,attributionControl:false,preferCanvas:false});

// Persistent LayerGroup — never removed from map, only cleared
const plotLG = L.layerGroup().addTo(map);
const heatLG = L.layerGroup().addTo(map); // heat kept separate
const tileSat = L.tileLayer(
  'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
  {maxZoom:19});
const tileRoads = L.tileLayer(
  'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
  {maxZoom:19,opacity:.38});

tileSat.addTo(map);
map.setView([30.37,69.35],9);

/* ── NDVI LEGEND as Leaflet Control (always inside the map container) ── */
const legendCtrl = L.control({position:'bottomleft'});
legendCtrl.onAdd = function(){
  const d = L.DomUtil.create('div','mleg');
  d.innerHTML = `
    <div style="font-size:.64rem;font-weight:800;color:var(--g800);margin-bottom:5px;letter-spacing:.3px;">NDVI LEGEND</div>
    <div class="lr"><div class="ld" style="background:#1a7a1a"></div><div class="ll">Excellent <span>&gt;0.60</span></div></div>
    <div class="lr"><div class="ld" style="background:#4CAF50"></div><div class="ll">Good <span>0.40–0.60</span></div></div>
    <div class="lr"><div class="ld" style="background:#FFC107"></div><div class="ll">Moderate <span>0.20–0.40</span></div></div>
    <div class="lr"><div class="ld" style="background:#F44336"></div><div class="ll">Critical <span>&lt;0.20</span></div></div>
    <div class="lr"><div class="ld" style="background:#9E9E9E"></div><div class="ll">No Data</div></div>
  `;
  // Prevent map click/drag when interacting with legend
  L.DomEvent.disableClickPropagation(d);
  return d;
};
legendCtrl.addTo(map);

/* ── LAYER TOGGLE ── */
function setLayer(type,btn){
  document.querySelectorAll('.lbtn').forEach(b=>b.classList.remove('on'));
  btn.classList.add('on'); layerMode=type;
  if(type==='hybrid'){ if(!map.hasLayer(tileRoads)) tileRoads.addTo(map); }
  else { if(map.hasLayer(tileRoads)) map.removeLayer(tileRoads); }
  // Polygon style
  plotLG.eachLayer(l=>{
    if(!l.feature) return;
    if(type==='ndvi') l.setStyle({fillOpacity:0,color:'rgba(255,255,255,.2)',weight:.8});
    else l.setStyle(pStyle(l.feature.properties));
  });
  // Heat visibility
  if(type==='sat'){ if(heatL && map.hasLayer(heatL)) map.removeLayer(heatL); }
  else { if(heatL && !map.hasLayer(heatL)) map.addLayer(heatL); setHeatOp(document.getElementById('hopo').value); }
}

function setHeatOp(v){
  // leaflet-heat has no setOpacity() — it renders to a <canvas>, set opacity there
  if(heatL && heatL._canvas) heatL._canvas.style.opacity = v/100;
}

/* ── DATES ── */
async function loadDates(){
  try{
    const r=await fetch(DATES_URL), d=await r.json();
    scanDates=d.dates||[];
    const sl=document.getElementById('tls');
    sl.max=Math.max(0,scanDates.length-1);
    sl.value=0;
    document.getElementById('tlDate').textContent=scanDates[0]||'Latest';
    updateSliderFill(sl);
    // Populate date picker dropdown
    const pick=document.getElementById('datePick');
    pick.innerHTML='<option value="">Latest</option>';
    scanDates.forEach(d=>{
      const o=document.createElement('option');
      o.value=d; o.textContent=d;
      pick.appendChild(o);
    });
  }catch(e){console.warn('dates',e);}
}

// Jump to a specific date from the dropdown
function jumpToDate(date){
  currentDate=date||null;
  // Sync slider position
  const idx=scanDates.indexOf(date);
  const sl=document.getElementById('tls');
  if(idx>=0){ sl.value=idx; updateSliderFill(sl); }
  document.getElementById('tlDate').textContent=date||'Latest';
  loadGeoJSON(currentDate,false);
}

function updateSliderFill(sl){
  const pct=(sl.value/Math.max(sl.max,1))*100;
  sl.style.background=`linear-gradient(to right,var(--g800) ${pct}%,var(--border) ${pct}%)`;
}

document.getElementById('tls').addEventListener('input',function(){
  updateSliderFill(this);
  const d=scanDates[parseInt(this.value)];
  document.getElementById('tlDate').textContent=d||'Latest';
  currentDate=d||null;
  // Throttle: only update polygon styles (no full reload during drag)
  clearTimeout(tlTimeout);
  tlTimeout=setTimeout(()=>loadGeoJSON(currentDate,false),280);
});

/* ── PLAY ── */
function togglePlay(){
  playing=!playing;
  document.getElementById('playIco').className=playing?'fas fa-pause':'fas fa-play';
  if(playing) startPlay(); else stopPlay();
}
function stopPlay(){ clearInterval(playTm); playing=false; document.getElementById('playIco').className='fas fa-play'; }
function startPlay(){
  const sl=document.getElementById('tls');
  const spd=parseInt(document.getElementById('spdSel').value);
  playTm=setInterval(()=>{
    let v=parseInt(sl.value);
    v=v>=parseInt(sl.max)?0:v+1;
    sl.value=v; updateSliderFill(sl);
    sl.dispatchEvent(new Event('input'));
  },spd);
}

/* ── LOAD GEOJSON ── */
async function loadGeoJSON(date, fitMap=true){
  showLoader(true);
  try{
    const url=GEO_URL+(date?'?date='+date:'');
    const res=await fetch(url);
    if(!res.ok) throw new Error('fetch failed');
    const gd=await res.json();
    allF=gd.features||[];
    console.log(`[CropAI] GeoJSON loaded: ${allF.length} features for date=${date||'latest'}`);
    renderPolygons(allF);
    renderList(allF);
    renderSummary(allF);
    buildHeat(allF);
    // Fit map to polygons ONLY on first load — never on timeline/date change
    if(fitMap && !initialFitDone && plotLG.getLayers().length){
      fitBounds(); initialFitDone=true;
    }
    document.getElementById('syncT').textContent=new Date().toLocaleTimeString();
    if(selPlot) highlightMapPlot(selPlot);
  }catch(e){ console.error('GeoJSON error',e); }
  finally{ showLoader(false); }
}

/* ── RENDER POLYGONS ──
 * ALWAYS calls plotLG.clearLayers() first — zero possibility of stacking.
 * layerMap tracks plot_id → layer for O(1) highlight/reset.
 * Frontend reads p.status directly from API — no local recalculation.
 */
function renderPolygons(features){
  // 1. Clear all existing polygon layers
  plotLG.clearLayers();
  layerMap = {};
  console.log(`[CropAI] renderPolygons: cleared, adding ${features.length} features (filter=${activeFilter})`);

  const visible=features.filter(f=>filterFeat(f));

  visible.forEach(feat=>{
    const p=feat.properties;
    // Guard: skip if already added (should never happen, but be safe)
    if(layerMap[p.plot_id]){
      console.warn(`[CropAI] Duplicate plot_id detected: ${p.plot_id} - skipping`);
      return;
    }
    const layer=L.geoJSON(feat,{
      style: ()=>pStyle(p),
      onEachFeature(_f,l){
        l.feature=feat; // ensure feature is attached
        l.on({
          mouseover(e){
            if(selPlot!==p.plot_id) e.target.setStyle({weight:2.5,fillOpacity:.8,color:'#fff'});
            e.target.bringToFront();
            const nd=p.ndvi!==null?p.ndvi.toFixed(3):'N/A';
            const tr=p.trend==='improving'?'↑':p.trend==='declining'?'↓':'→';
            e.target.bindTooltip(
              `<b>Plot ${p.plot_name}</b>${p.area?' &nbsp;'+p.area+' Ac':''}<br>NDVI: <b>${nd}</b> ${tr}<br>${SL[p.status]||''}`,
              {sticky:true,className:'leaflet-tt'}
            ).openTooltip();
          },
          mouseout(e){
            if(selPlot!==p.plot_id) e.target.setStyle(pStyle(p));
            e.target.closeTooltip();
          },
          click(){ selectPlot(p.plot_id); }
        });
      }
    });
    layer.addTo(plotLG);
    layerMap[p.plot_id]=layer;
  });

  // Apply NDVI Heat mode style
  if(layerMode==='ndvi') plotLG.eachLayer(l=>l.setStyle({fillOpacity:0,color:'rgba(255,255,255,.2)',weight:.8}));
}

function pStyle(p){
  // In NDVI Heat mode: show plot as thin white dashed outline only,
  // so the heat overlay is clearly visible underneath.
  if(layerMode==='ndvi') return {
    fillOpacity: 0,
    color: 'rgba(255,255,255,0.7)',
    weight: 1.5,
    dashArray: '4,3',
  };
  // Normal satellite / hybrid mode
  const col=SC[p.status]||'#9E9E9E';
  const drop=p.improvement!==null && p.improvement<-5;
  const crit=p.status==='critical';
  return {
    fillColor: col,
    fillOpacity: p.has_data ? 0.52 : 0.18,
    color: drop||crit ? '#FF3D00' : '#fff',
    weight: drop||crit ? 2.5 : 1.8,
    dashArray: drop ? '6,4' : '',
    className: drop||crit ? 'lblink' : '',
  };
}

function filterFeat(f){
  if(activeFilter==='all') return true;
  if(activeFilter==='no_data') return !f.properties.has_data;
  return f.properties.status===activeFilter;
}

/* ── HEAT LAYER — fills each polygon with a dense grid of heat points ──
 * Instead of a single blob at the centroid, we extract the polygon's
 * bounding box from its GeoJSON geometry and seed a 10×10 grid of heat
 * points inside it. Each point uses the plot's NDVI as intensity.
 * This makes the heat visually fill the plot area like a proper overlay.
 */
function buildHeat(features){
  if(heatL){ map.removeLayer(heatL); heatL=null; }

  const pts=[];
  const STEPS=10; // 10×10 = 100 points per plot

  features.forEach(f=>{
    const p=f.properties;
    if(!p.has_data || p.ndvi===null) return;

    // Extract bounding box from polygon geometry
    const ring = f.geometry?.coordinates?.[0];
    if(!ring || ring.length < 4){
      pts.push([p.lat, p.lng, p.ndvi]); // fallback: single point
      return;
    }

    let minLat=Infinity, maxLat=-Infinity, minLng=Infinity, maxLng=-Infinity;
    ring.forEach(([lng,lat])=>{
      if(lat<minLat) minLat=lat; if(lat>maxLat) maxLat=lat;
      if(lng<minLng) minLng=lng; if(lng>maxLng) maxLng=lng;
    });

    const dLat=(maxLat-minLat)/STEPS;
    const dLng=(maxLng-minLng)/STEPS;

    // Seeded deterministic jitter per plot so heat looks natural,
    // consistent across re-renders (no Math.random — avoids flickering)
    const seed = p.plot_id || 1;
    for(let i=0; i<=STEPS; i++){
      for(let j=0; j<=STEPS; j++){
        // Deterministic sub-grid jitter (0..1 range scaled to 30% of cell)
        const jLat = (((seed*7+i*13+j*17)%100)/100 - 0.5) * dLat * 0.3;
        const jLng = (((seed*11+i*19+j*23)%100)/100 - 0.5) * dLng * 0.3;
        const lat = minLat + i*dLat + jLat;
        const lng = minLng + j*dLng + jLng;
        pts.push([lat, lng, p.ndvi]);
      }
    }
  });

  if(!pts.length) return;

  heatL=L.heatLayer(pts,{
    radius: 18,   // small radius: fills dense grid tightly
    blur: 14,
    maxZoom: 18,
    max: 1.0,
    // Reference-image-style gradient: red (low) → orange → yellow → green (high)
    gradient:{0:'#D32F2F',0.25:'#FF5722',0.4:'#FF9800',0.55:'#FFC107',0.7:'#8BC34A',1.0:'#1B5E20'}
  });

  if(layerMode!=='sat'){
    heatL.addTo(map);
    requestAnimationFrame(()=>setHeatOp(document.getElementById('hopo').value));
  }
}

/* ── FIT BOUNDS ── */
function fitBounds(){
  // Collect all polygon bounds from the LayerGroup
  const bounds=[];
  plotLG.eachLayer(l=>{
    try{ const b=l.getBounds(); if(b.isValid()) bounds.push(b); }catch(_){}
  });
  if(!bounds.length) return;
  const combined=bounds.reduce((acc,b)=>acc.extend(b), bounds[0]);
  map.flyToBounds(combined,{padding:[35,35],duration:1.0,maxZoom:16});
}

/* ── PLOT LIST ── */
function renderList(features){
  const visible=features.filter(f=>filterFeat(f));
  document.getElementById('pCount').textContent=visible.length;
  const el=document.getElementById('plist');
  if(!visible.length){
    el.innerHTML='<div style="padding:18px;text-align:center;color:var(--sub);font-size:.75rem;">No plots match filter</div>';
    return;
  }
  // Sort: critical first
  const order={critical:0,warning:1,moderate:2,good:3,excellent:4,no_data:5};
  visible.sort((a,b)=>(order[a.properties.status]??5)-(order[b.properties.status]??5));
  el.innerHTML=visible.map(f=>{
    const p=f.properties;
    const col=SC[p.status]||'#9E9E9E';
    const nd=p.ndvi!==null?p.ndvi.toFixed(3):'—';
    const tr=p.trend==='improving'?'<span class="pi-trend" style="color:#1b5e20">↑</span>'
            :p.trend==='declining'?'<span class="pi-trend" style="color:#c62828">↓</span>':'';
    return `<div class="pi${selPlot===p.plot_id?' active':''}" data-id="${p.plot_id}" onclick="selectPlot(${p.plot_id})">
      <div class="pi-dot" style="background:${col}"></div>
      <div><div class="pi-name">Plot ${p.plot_name}</div><div class="pi-sub">${p.block_name||''}</div></div>
      <div class="pi-ndvi" style="color:${col}">${nd}${tr}</div>
    </div>`;
  }).join('');
}

function applyFilter(f,btn){
  document.querySelectorAll('.fp').forEach(b=>b.classList.remove('on'));
  btn.classList.add('on'); activeFilter=f;
  if(allF.length){ renderPolygons(allF); renderList(allF); }
}

/* ── SUMMARY ── */
function renderSummary(features){
  const total=features.length;
  const excellent=features.filter(f=>f.properties.status==='excellent').length;
  const good=features.filter(f=>f.properties.status==='good').length;
  const moderate=features.filter(f=>f.properties.status==='moderate').length;
  const warning=features.filter(f=>f.properties.status==='warning').length;
  const critical=features.filter(f=>f.properties.status==='critical').length;
  const nodata=features.filter(f=>!f.properties.has_data).length;
  const healthy=excellent+good;
  const weak=moderate+warning+critical;
  const wd=features.filter(f=>f.properties.has_data);
  const avg=wd.length?wd.reduce((s,f)=>s+(f.properties.ndvi||0),0)/wd.length:0;

  cnt('sT',total); cnt('sH',healthy); cnt('sW',weak); cnt('sN',nodata);
  document.getElementById('sA').textContent=avg.toFixed(4);

  // Farm ring
  document.getElementById('farmAvgV').textContent=avg.toFixed(3);
  const arc=document.getElementById('farmArc');
  const offset=(1-Math.min(avg,1))*251.2;
  arc.style.strokeDashoffset=offset;
  const avgCol=avg>0.7?'#1a7a1a':avg>0.55?'#4CAF50':avg>0.4?'#FFC107':avg>0.25?'#FF9800':'#F44336';
  arc.style.stroke=avgCol;

  // Farm stats
  document.getElementById('fse').textContent=excellent;
  document.getElementById('fsg').textContent=good;
  document.getElementById('fsm').textContent=moderate;
  document.getElementById('fsw').textContent=warning;
  document.getElementById('fsc').textContent=critical;

  // Health bar
  const hbar=document.getElementById('hbar');
  if(total>0){
    hbar.innerHTML=`
      <div class="hbs" style="width:${(excellent/total*100).toFixed(1)}%;background:#1a7a1a;" title="Excellent"></div>
      <div class="hbs" style="width:${(good/total*100).toFixed(1)}%;background:#4CAF50;" title="Good"></div>
      <div class="hbs" style="width:${(moderate/total*100).toFixed(1)}%;background:#FFC107;" title="Moderate"></div>
      <div class="hbs" style="width:${(warning/total*100).toFixed(1)}%;background:#FF9800;" title="Warning"></div>
      <div class="hbs" style="width:${(critical/total*100).toFixed(1)}%;background:#F44336;" title="Critical"></div>
      <div class="hbs" style="width:${(nodata/total*100).toFixed(1)}%;background:#9E9E9E;" title="No Data"></div>`;
  }

  // Farm narrative (rule-based)
  let narr='';
  const pct=(((excellent+good)/Math.max(total,1))*100).toFixed(0);
  if(critical>0) narr+=`🚨 ${critical} plot(s) in critical condition require immediate attention. `;
  if(warning>0)  narr+=`⚠️ ${warning} plot(s) showing warning-level NDVI. Schedule irrigation. `;
  if(healthy/Math.max(total,1)>=.7) narr+=`✅ ${pct}% of farm is healthy (NDVI good–excellent). `;
  narr+=avg>0.6?`Overall farm health is strong (avg ${avg.toFixed(3)}).`:
        avg>0.4?`Farm average NDVI ${avg.toFixed(3)} is moderate — monitor closely.`:
                `Farm average NDVI ${avg.toFixed(3)} is below optimal — review irrigation plan.`;
  document.getElementById('farmNarr').textContent='🤖 '+narr;
  document.getElementById('farmSumSub').textContent=
    `${total} plots · ${healthy} healthy · ${critical} critical · Last sync ${new Date().toLocaleTimeString()}`;
}

function cnt(id,t){
  const el=document.getElementById(id);if(!el)return;
  let v=0,s=Math.max(1,Math.ceil(t/18));
  const tm=setInterval(()=>{v+=s;if(v>=t){v=t;clearInterval(tm);}el.textContent=v;},30);
}

/* ── SELECT PLOT ── */
async function selectPlot(plotId){
  selPlot=plotId;
  // List highlight
  document.querySelectorAll('.pi').forEach(e=>e.classList.toggle('active',parseInt(e.dataset.id)===plotId));
  // Map highlight
  highlightMapPlot(plotId);
  // Show shimmer
  document.getElementById('farmSum').style.display='none';
  document.getElementById('aiShim').style.display='block';
  document.getElementById('aiC').style.display='none';
  document.getElementById('chartW').style.display='none';
  try{
    const url=INS_URL+'/'+plotId+(currentDate?'?date='+currentDate:'');
    const res=await fetch(url);
    const d=await res.json();
    renderInsight(d);
  }catch(e){
    document.getElementById('aiC').innerHTML='<div style="padding:10px;color:var(--red);font-size:.8rem;">Failed to load insight</div>';
    document.getElementById('aiC').style.display='block';
    document.getElementById('aiShim').style.display='none';
  }
}

/* ── HIGHLIGHT MAP PLOT ── */
function highlightMapPlot(plotId){
  // Reset all layers to default style
  plotLG.eachLayer(l=>{
    if(!l.feature) return;
    l.setStyle(pStyle(l.feature.properties));
  });
  // Highlight selected
  if(layerMap[plotId]){
    const tgt=layerMap[plotId];
    tgt.eachLayer ? tgt.eachLayer(l=>{ l.setStyle({weight:3.5,color:'#fff',fillOpacity:.85}); l.bringToFront(); })
                  : (tgt.setStyle({weight:3.5,color:'#fff',fillOpacity:.85}), tgt.bringToFront());
  }
}

function renderInsight(d){
  document.getElementById('aiShim').style.display='none';
  const col=SC[d.status]||'#9E9E9E';
  const risk=d.risk_level||'unknown';
  const riskL={low:'✅ Low Risk',medium:'⚠️ Medium Risk',high:'🔶 High Risk',critical:'🚨 Critical',unknown:'❓ Unknown'}[risk]||risk;
  const riskC={low:'rl',medium:'rm',high:'rh',critical:'rc',unknown:'ru'}[risk]||'ru';
  const tr=d.trend==='improving'?`<span class="trnd tu"><i class="fas fa-arrow-up" style="font-size:.5rem"></i> +${d.improvement}%</span>`
           :d.trend==='declining'?`<span class="trnd td"><i class="fas fa-arrow-down" style="font-size:.5rem"></i> ${d.improvement}%</span>`
           :`<span class="trnd ts"><i class="fas fa-minus" style="font-size:.5rem"></i> Stable</span>`;
  const stS={'excellent':'se','good':'sg','moderate':'sm','warning':'sw','critical':'sk','no_data':'sn'}[d.status||'']||'sn';
  const iC=d.irrigation_status==='Not required'||d.irrigation_status==='As scheduled'?'iok'
           :(d.irrigation_status?.includes('Emergency')||d.irrigation_status?.includes('24')?'iurg':'ireq');
  const cl=d.cloud>20?`<span style="font-size:.63rem;color:var(--amber)"> ☁️${d.cloud}%</span>`:'';
  const C=2*Math.PI*26;
  const off=((1-(d.ndvi||0))*C).toFixed(2);

  document.getElementById('aiC').innerHTML=`
    <div class="ai-pname">Plot ${d.plot_name||'Plot'}</div>
    <div class="ai-psub">${d.block_name||''} ${d.area?'· '+d.area+' Ac':''} <span style="font-size:.62rem;color:var(--sub)">${d.last_date||''}${cl}</span></div>
    <span class="rbadge ${riskC}">${riskL}</span>
    <div class="dnut-wrap">
      <div class="dnut">
        <svg viewBox="0 0 64 64" width="64" height="64">
          <circle cx="32" cy="32" r="26" fill="none" stroke="var(--border)" stroke-width="7.5"/>
          <circle cx="32" cy="32" r="26" fill="none" stroke="${col}" stroke-width="7.5"
            stroke-linecap="round" stroke-dasharray="${C.toFixed(2)}" stroke-dashoffset="${off}"
            style="transition:stroke-dashoffset 1s ease"/>
        </svg>
        <div class="dnut-v"><span class="dnut-n" style="color:${col}">${d.ndvi!==null?d.ndvi.toFixed(3):'N/A'}</span><span class="dnut-s">NDVI</span></div>
      </div>
      <div>
        <span class="sbdg ${stS}">${SL[d.status]||'No Data'}</span>
        <div style="margin-top:6px;">${tr}</div>
        <div style="font-size:.63rem;color:var(--sub);margin-top:5px;">${d.scans_analyzed||0} scans</div>
      </div>
    </div>
    <div class="narr">🤖 ${d.narrative||'—'}</div>
    <div class="iblk"><div class="ititle">⚡ Stress Reason</div><div class="ival">${d.stress_reason||'—'}</div></div>
    <div class="iblk"><div class="ititle">💧 Irrigation</div><div class="ival"><span class="irrb ${iC}">${d.irrigation_status||'—'}</span></div></div>
    <div class="iblk"><div class="ititle">🌱 Recommendation</div><div class="ival">${d.recommendation||'—'}</div></div>
    <div class="iblk"><div class="ititle">🧪 Fertilizer</div><div class="ival">${d.fertilizer_note||'—'}</div></div>
  `;
  document.getElementById('aiC').style.display='block';
  if(d.sparkline && d.sparkline.length>1){
    document.getElementById('chartW').style.display='block';
    buildMini(d.sparkline,col);
  }
}

function buildMini(spark,col){
  const c=document.getElementById('mc');
  if(miniCh){miniCh.destroy();miniCh=null;}
  const g=c.getContext('2d').createLinearGradient(0,0,0,78);
  g.addColorStop(0,col+'55'); g.addColorStop(1,col+'04');
  miniCh=new Chart(c,{type:'line',
    data:{labels:spark.map(x=>x.d),datasets:[{data:spark.map(x=>x.v),
      borderColor:col,backgroundColor:g,borderWidth:2,tension:.4,fill:true,
      pointRadius:2.5,pointBackgroundColor:'#fff',pointBorderColor:col,pointBorderWidth:1.5}]},
    options:{responsive:true,animation:{duration:500},
      plugins:{legend:{display:false},tooltip:{backgroundColor:'#1B5E20',titleColor:'#fff',
        bodyColor:'#c8e6c9',padding:6,callbacks:{label:x=>'NDVI: '+x.parsed.y.toFixed(4)}}},
      scales:{x:{ticks:{font:{size:9},color:'var(--sub)'},grid:{display:false}},
              y:{display:false,min:0,max:1}}}});
}

/* ── DARK MODE ── */
function toggleDark(){
  document.body.classList.toggle('dm');
  document.getElementById('dmBtn').textContent=document.body.classList.contains('dm')?'☀️ Light':'🌙 Dark';
}

/* ── LOADER ── */
function showLoader(v){document.getElementById('mload').classList.toggle('gone',!v);}

/* ── AUTO REFRESH ── */
setInterval(()=>{if(!playing) loadGeoJSON(currentDate,false);},5*60*1000);

/* ── BOOT ── */
(async()=>{
  await loadDates();
  await loadGeoJSON(null,true);
  // Default to Hybrid mode so NDVI heat is visible on first load
  const hybBtn=document.querySelector('.lbtn[data-l="hybrid"]');
  if(hybBtn) setLayer('hybrid',hybBtn);
})();
</script>
@endpush
