<?php
require_once __DIR__ . '/certificate-engine/config.php';
session_start();
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

$action = $_GET['action'] ?? '';
if ($action === 'verify' || $action === 'download') {
    require_once __DIR__ . '/certificate-engine/handlers.php';
    $action === 'verify' ? handle_verify() : handle_download();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hackathon Certificate — FutureCrime Summit 2026</title>
<meta name="robots" content="noindex">
<link rel="icon" type="image/jpeg" href="/assets/img/logo/favs.jpeg">
<link rel="apple-touch-icon" href="/assets/img/logo/favs.jpeg">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  html, body { font-family: Arial, Helvetica, sans-serif; }
  body {
    min-height: 100vh;
    background: linear-gradient(180deg,#0b1020 0%,#131e42 26%,#2b45b0 55%,#5f7bdd 78%,#aebef0 100%);
  }
  .card-shadow { box-shadow: 0 30px 60px -20px rgba(9,14,32,.55); }
  .field { background:#f2f4f7; border:1px solid #e6e9ee; border-radius:14px; padding:14px 16px; width:100%;
           font-size:16px; color:#111318; outline:none; transition:border-color .15s, box-shadow .15s, background .15s; }
  .field:focus { border-color:#2b45b0; background:#fff; box-shadow:0 0 0 4px rgba(43,69,176,.12); }
  .field.err { border-color:#e0433c; background:#fff5f5; }
  .pill { background:#0a0a0a; color:#fff; border-radius:9999px; padding:16px 24px; width:100%;
          font-size:16px; font-weight:bold; letter-spacing:.2px; transition:transform .12s, opacity .12s; }
  .pill:hover { transform:translateY(-1px); }
  .pill:disabled { opacity:.6; cursor:not-allowed; transform:none; }
  .fade { animation:fade .35s ease both; }
  @keyframes fade { from{opacity:0; transform:translateY(8px);} to{opacity:1; transform:none;} }
  @media (prefers-reduced-motion: reduce){ .fade{animation:none;} .pill:hover{transform:none;} }
</style>
</head>
<body class="text-white">
  <main class="min-h-screen w-full flex items-center justify-center px-5 py-10">
    <div class="w-full max-w-[460px]">

      <header class="flex items-start justify-between mb-6 px-1">
        <div class="min-w-0 pr-3">
          <p class="text-[12px] font-bold tracking-[.18em] uppercase text-[#9db0ef]">FutureCrime Summit 2026</p>
          <h1 class="mt-2">
            <img src="/assets/img/logo/FCRF%20Hackathon.png" alt="FCRF Hackathon"
                 class="w-auto" style="height:38px;max-width:100%;">
          </h1>
          <p class="text-[15px] text-[#b9c6f2] mt-2">Download your participation certificate</p>
        </div>
        <div class="shrink-0 mt-1">
          <svg width="72" height="72" viewBox="0 0 72 72">
            <circle cx="36" cy="36" r="30" fill="none" stroke="rgba(255,255,255,.18)" stroke-width="4"/>
            <circle id="ring" cx="36" cy="36" r="30" fill="none" stroke="#ffffff" stroke-width="4"
                    stroke-linecap="round" stroke-dasharray="188.5" stroke-dashoffset="94.25"
                    transform="rotate(-90 36 36)" style="transition:stroke-dashoffset .4s ease"/>
            <text id="ringText" x="36" y="34" text-anchor="middle" fill="#fff" font-size="15" font-weight="bold">1/2</text>
            <text x="36" y="48" text-anchor="middle" fill="#c9d3f5" font-size="9">step</text>
          </svg>
        </div>
      </header>

      <section class="bg-[#f7f8fa] text-[#111318] rounded-[28px] card-shadow p-7 sm:p-8">

        <div id="step1" class="fade">
          <p class="text-[12px] font-bold tracking-[.14em] uppercase text-[#6b7280]">Step 1 of 2</p>
          <h2 class="text-[26px] font-bold mt-2">Verify your details</h2>
          <p class="text-[15px] text-[#6b7280] mt-2 leading-relaxed">
            Enter the email you registered the hackathon with, and the name you want printed on your certificate.
          </p>
          <div class="mt-6 space-y-4">
            <div>
              <label for="email" class="block text-[13px] font-bold text-[#374151] mb-1.5">Registered email</label>
              <input id="email" type="email" autocomplete="email" inputmode="email" class="field" placeholder="you@example.com">
              <p id="emailErr" class="text-[13px] text-[#e0433c] mt-1.5 hidden"></p>
            </div>
            <div>
              <label for="name" class="block text-[13px] font-bold text-[#374151] mb-1.5">Name for the certificate</label>
              <input id="name" type="text" autocomplete="name" maxlength="60" class="field" placeholder="e.g. Priya Nair">
              <p id="nameErr" class="text-[13px] text-[#e0433c] mt-1.5 hidden"></p>
              <p class="text-[12px] text-[#9aa1ab] mt-1.5">This is locked once — enter it exactly as you want it shown.</p>
            </div>
          </div>
          <button id="continueBtn" class="pill mt-6">Continue</button>
          <p id="formErr" class="text-[13px] text-[#e0433c] mt-3 text-center hidden"></p>
        </div>

        <div id="step2" class="hidden">
          <p class="text-[12px] font-bold tracking-[.14em] uppercase text-[#6b7280]">Step 2 of 2</p>
          <h2 class="text-[26px] font-bold mt-2">Your certificate is ready</h2>
          <p class="text-[15px] text-[#6b7280] mt-2 leading-relaxed">It will be issued in this name:</p>
          <div class="mt-4 rounded-2xl bg-white border border-[#e6e9ee] px-5 py-4">
            <p class="text-[12px] uppercase tracking-wide text-[#9aa1ab] font-bold">Name on certificate</p>
            <p id="finalName" class="text-[22px] font-bold text-[#111318] mt-1 break-words">—</p>
          </div>
          <p id="lockedNote" class="text-[13px] text-[#6b7280] mt-3 hidden leading-relaxed"></p>
          <button id="downloadBtn" class="pill mt-6">Download certificate</button>
          <div class="mt-5 pt-4 border-t border-[#e8eaee] flex items-center justify-between">
            <span id="regEmail" class="text-[13px] text-[#6b7280] truncate pr-3"></span>
            <button id="restartBtn" class="text-[13px] font-bold text-[#2b45b0] shrink-0">Use a different email</button>
          </div>
          <p id="dlHint" class="text-[12px] text-[#9aa1ab] mt-3 hidden">You can download it as many times as you like.</p>
        </div>

      </section>

      <p class="text-center text-[12px] text-[#c9d3f5]/80 mt-6">
        Only registered hackathon attendees can download a certificate.
      </p>
    </div>
  </main>

<script>
const $ = (id) => document.getElementById(id);
const emailEl = $('email'), nameEl = $('name');
const API = window.location.pathname;   // same page handles ?action=...

function setRing(step){
  $('ring').style.strokeDashoffset = 188.5 * (1 - (step === 2 ? 1 : 0.5));
  $('ringText').textContent = step + '/2';
}
function showErr(el, m, msg){ el.classList.add('err'); m.textContent = msg; m.classList.remove('hidden'); }
function clearErr(el, m){ el.classList.remove('err'); m.classList.add('hidden'); }

[emailEl, nameEl].forEach(el => el.addEventListener('input', () => {
  clearErr(emailEl, $('emailErr')); clearErr(nameEl, $('nameErr')); $('formErr').classList.add('hidden');
}));
emailEl.addEventListener('keydown', e => { if(e.key==='Enter') nameEl.focus(); });
nameEl.addEventListener('keydown', e => { if(e.key==='Enter') submit(); });
$('continueBtn').addEventListener('click', submit);

const REASONS = {
  email_format:['email','Enter a valid email address.'],
  not_registered:['email',"This email isn't on the hackathon attendee list. Use the email you registered with."],
  name_invalid:['name',"Enter the name using letters, spaces, and . ' - only."],
  rate:['form','Too many attempts. Please wait a minute and try again.'],
  method:['form','Something went wrong. Please refresh and try again.'],
};

async function submit(){
  clearErr(emailEl, $('emailErr')); clearErr(nameEl, $('nameErr')); $('formErr').classList.add('hidden');
  const email = emailEl.value.trim(), name = nameEl.value.trim();
  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){ showErr(emailEl, $('emailErr'), 'Enter a valid email address.'); return; }
  if(name.length < 2){ showErr(nameEl, $('nameErr'), 'Enter the name for your certificate.'); return; }

  const btn = $('continueBtn'); btn.disabled = true; const label = btn.textContent; btn.textContent = 'Checking…';
  try{
    const res = await fetch(API + '?action=verify', {
      method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ email, name })
    });
    const data = await res.json();
    if(!data.ok){
      const [where, msg] = REASONS[data.reason] || ['form','Could not verify. Please try again.'];
      if(where==='email') showErr(emailEl, $('emailErr'), msg);
      else if(where==='name') showErr(nameEl, $('nameErr'), msg);
      else { $('formErr').textContent = msg; $('formErr').classList.remove('hidden'); }
      return;
    }
    $('finalName').textContent = data.name;
    $('regEmail').textContent = email;
    const note = $('lockedNote');
    if(data.locked && data.changed){
      note.textContent = 'This email already has a certificate locked to the name above, so that name is used instead of the one you just entered.';
      note.classList.remove('hidden');
    } else if(data.locked){
      note.textContent = 'Welcome back — your certificate is ready to download again.';
      note.classList.remove('hidden');
    } else { note.classList.add('hidden'); }
    $('step1').classList.add('hidden');
    $('step2').classList.remove('hidden'); $('step2').classList.add('fade');
    setRing(2);
  }catch(e){
    $('formErr').textContent = 'Network error. Check your connection and try again.';
    $('formErr').classList.remove('hidden');
  }finally{ btn.disabled = false; btn.textContent = label; }
}

$('downloadBtn').addEventListener('click', () => {
  window.location.href = API + '?action=download';
  $('dlHint').classList.remove('hidden');
});
$('restartBtn').addEventListener('click', () => {
  $('step2').classList.add('hidden');
  $('step1').classList.remove('hidden'); $('step1').classList.add('fade');
  emailEl.value=''; nameEl.value=''; emailEl.focus(); setRing(1);
});
setRing(1);
</script>
</body>
</html>