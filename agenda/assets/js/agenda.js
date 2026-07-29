/* FutureCrime Summit — agenda behaviour.
   Unchanged from the previous version: the page is time-aware and knows
   which session is running. Only the styling around it has moved on. */

(() => {
  'use strict';

  const D = window.FCS || {};
  const sessions = D.sessions || [];
  const halls = D.halls || [];
  const days = D.days || [];
  const speakers = D.speakers || [];

  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  const hallById = Object.fromEntries(halls.map(h => [Number(h.id), h]));

  // Deterministic colour per person, so a monogram never changes shade
  // between the card, the session sheet and the directory.
  const HUES = ['#FF7171', '#FF9F43', '#10AC84', '#0ea5e9', '#5135FF', '#FF9FF3', '#00D2D3'];
  const hueFor = (str) => {
    let h = 0;
    for (let i = 0; i < str.length; i++) h = (h * 31 + str.charCodeAt(i)) >>> 0;
    return HUES[h % HUES.length];
  };

  const initials = (name) => {
    const parts = String(name).replace(/\(.*?\)/g, '').trim().split(/\s+/).filter(Boolean);
    const skip = /^(dr|mr|ms|mrs|prof|adv|ca|lt|col|maj|gen|air|vice|marshal|shri|smt|sh)\.?$/i;
    const real = parts.filter(p => !skip.test(p));
    const use = real.length ? real : parts;
    return ((use[0]?.[0] || '') + (use.length > 1 ? use[use.length - 1][0] : '')).toUpperCase() || '?';
  };

  const hallColor = (id) => hallById[id]?.color_hex || (id ? '#0ea5e9' : '#64748b');

  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const hhmm = (t) => String(t || '').slice(0, 5);

  const pretty = (t) => {
    const [h, m] = hhmm(t).split(':').map(Number);
    if (Number.isNaN(h)) return '';
    const ap = h >= 12 ? 'PM' : 'AM';
    const hr = h % 12 === 0 ? 12 : h % 12;
    return `${hr}:${String(m).padStart(2, '0')} ${ap}`;
  };

  const TYPE_LABEL = {
    panel: 'Panel', keynote: 'Keynote', fireside: 'Fireside', workshop: 'Workshop',
    plenary: 'Plenary', inauguration: 'Inaugural', valedictory: 'Valedictory',
    sponsor: 'Sponsor', break: 'Break', lunch: 'Networking Lunch',
    networking: 'Networking', award: 'Awards', other: 'Session'
  };
  const isBreak = (s) => ['break', 'lunch', 'networking'].includes(s.session_type);

  const avatar = (sp, cls = '') => {
    const key = sp.name || sp.full_name || '';
    if (sp.photo) return `<img class="av ${cls}" src="${esc(sp.photo)}" alt="" loading="lazy" width="34" height="34">`;
    return `<span class="av ${cls}" style="--av:${hueFor(key)}" aria-hidden="true">${esc(initials(key))}</span>`;
  };

  // Event time is IST regardless of what the device thinks — an attendee's
  // phone on roaming should not shift the schedule.
  const istNow = () => {
    const now = new Date();
    return new Date(now.getTime() + (now.getTimezoneOffset() + 330) * 60000);
  };

  const dayOf = (s) => days.find(d => Number(d.id) === Number(s.day_id));
  const startsAt = (s) => {
    const d = dayOf(s);
    if (!d) return null;
    return new Date(`${d.event_date}T${String(s.start_time).padStart(8, '0')}`);
  };
  const endsAt = (s) => {
    const d = dayOf(s);
    if (!d || !s.end_time) return null;
    return new Date(`${d.event_date}T${String(s.end_time).padStart(8, '0')}`);
  };

  const state = { day: null, halls: new Set(), query: '' };

  // Open on the day that is actually happening, otherwise the first day.
  (() => {
    const today = istNow().toISOString().slice(0, 10);
    const match = days.find(d => d.event_date === today);
    state.day = Number((match || days[0] || {}).id) || null;
  })();

  // ------------------------------------------------------------ live strip
  function liveState() {
    const now = istNow();
    const real = sessions.filter(s => !isBreak(s) && startsAt(s));
    real.sort((a, b) => startsAt(a) - startsAt(b));
    const running = real.filter(s => {
      const st = startsAt(s), en = endsAt(s);
      return st <= now && en && en > now;
    });
    const next = real.find(s => startsAt(s) > now);
    return { now, running, next };
  }

  function renderLive() {
    const el = $('#live-strip');
    if (!el) return;
    const { now, running, next } = liveState();

    if (!running.length && !next) {
      const first = sessions.map(startsAt).filter(Boolean).sort((a, b) => a - b)[0];
      el.innerHTML = `<div class="live-card is-idle">
        <div class="live-label"><span class="pulse"></span>Agenda Status</div>
        <p class="live-title">${first && first > now ? 'Awaiting commencement…' : 'Summit Concluded'}</p>
        <div class="live-meta"><span>${esc(D.event?.venue || '')}</span></div>
      </div>`;
      return;
    }

    if (!running.length && next) {
      el.innerHTML = `<div class="live-card is-idle">
        <div class="live-label"><span class="pulse"></span>Up Next</div>
        <p class="live-title">${esc(next.title)}</p>
        <div class="live-meta">
          <span><b>${pretty(next.start_time)}</b></span>
          ${next.hall_id ? `<span>${esc(hallById[next.hall_id]?.name || '')}</span>` : ''}
          <span style="color:var(--accent-cyan)">Starts in ${countdown(startsAt(next) - now)}</span>
        </div>
      </div>`;
      return;
    }

    const s = running[0];
    const st = startsAt(s), en = endsAt(s);
    const pct = Math.min(100, Math.max(0, ((now - st) / (en - st)) * 100));
    const others = running.slice(1);

    el.innerHTML = `<div class="live-card">
      <div class="live-label"><span class="pulse"></span>Happening Now</div>
      <p class="live-title">${esc(s.title)}</p>
      <div class="live-meta">
        ${s.hall_id ? `<span><b>${esc(hallById[s.hall_id]?.name || '')}</b></span>` : ''}
        <span>Ends ${pretty(s.end_time)} · ${countdown(en - now)} left</span>
        ${s.speakers?.length ? `<span>${s.speakers.length} Speaker${s.speakers.length > 1 ? 's' : ''}</span>` : ''}
      </div>
      <div class="live-bar"><span style="width:${pct.toFixed(1)}%"></span></div>
      ${others.length ? `<div class="live-next"><em>Simultaneous Track</em><b>${esc(others[0].title)}</b>
        <span>${esc(hallById[others[0].hall_id]?.name || '')}</span></div>` : ''}
      ${next ? `<div class="live-next"><em>Up Next</em><b>${esc(next.title)}</b>
        <span>${pretty(next.start_time)}${next.hall_id ? ' · ' + esc(hallById[next.hall_id]?.name || '') : ''}</span></div>` : ''}
    </div>`;
  }

  function countdown(ms) {
    const mins = Math.max(0, Math.round(ms / 60000));
    if (mins < 60) return `${mins} min`;
    const h = Math.floor(mins / 60), m = mins % 60;
    return m ? `${h}h ${m}m` : `${h}h`;
  }

  function tickClock() {
    const t = istNow();
    const el = $('#clock-time');
    if (el) el.textContent = `${String(t.getHours()).padStart(2, '0')}:${String(t.getMinutes()).padStart(2, '0')}`;
  }

  // -------------------------------------------------------------- controls
  function renderControls() {
    const tabs = $('#day-tabs');
    tabs.innerHTML = days.map(d => {
      const dt = new Date(d.event_date + 'T00:00:00');
      const label = dt.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
      return `<button class="day-tab" role="tab" data-day="${d.id}"
        aria-selected="${Number(d.id) === state.day}">${esc(d.label)}<small>${label}</small></button>`;
    }).join('');

    tabs.addEventListener('click', (e) => {
      const b = e.target.closest('.day-tab');
      if (!b) return;
      state.day = Number(b.dataset.day);
      $$('.day-tab', tabs).forEach(t => t.setAttribute('aria-selected', String(t === b)));
      renderTimeline();
    });

    const hf = $('#hall-filters');
    hf.innerHTML = halls.map(h =>
      `<button class="hall-chip" data-hall="${h.id}" aria-pressed="false"
        style="color:${hallColor(h.id)}"><i style="background:${hallColor(h.id)}"></i>${esc(h.name)}</button>`
    ).join('');

    hf.addEventListener('click', (e) => {
      const b = e.target.closest('.hall-chip');
      if (!b) return;
      const id = Number(b.dataset.hall);
      state.halls.has(id) ? state.halls.delete(id) : state.halls.add(id);
      b.setAttribute('aria-pressed', String(state.halls.has(id)));
      renderTimeline();
    });

    const input = $('#search');
    const clear = $('#search-clear');
    let t;
    input.addEventListener('input', () => {
      clearTimeout(t);
      clear.hidden = !input.value;
      t = setTimeout(() => { state.query = input.value.trim().toLowerCase(); renderTimeline(); }, 140);
    });
    clear.addEventListener('click', () => {
      input.value = ''; clear.hidden = true; state.query = ''; renderTimeline(); input.focus();
    });
  }

  // -------------------------------------------------------------- timeline
  function matches(s) {
    if (state.halls.size && !(s.hall_id && state.halls.has(Number(s.hall_id)))) {
      if (!isBreak(s)) return false;
    }
    if (!state.query) return true;
    const hay = [
      s.title, s.subtitle, s.description,
      hallById[s.hall_id]?.name,
      ...(s.speakers || []).flatMap(p => [p.name, p.designation, p.organisation])
    ].filter(Boolean).join(' ').toLowerCase();
    return hay.includes(state.query);
  }

  function renderTimeline() {
    const host = $('#timeline');
    const now = istNow();

    // Searching looks across both days — a name is not a day-scoped thing.
    const scope = state.query
      ? sessions
      : sessions.filter(s => Number(s.day_id) === state.day);

    const list = scope.filter(matches);
    $('#empty').hidden = list.length > 0;

    if (!list.length) { host.innerHTML = ''; return; }

    let html = '';
    let lastDay = null;

    list.forEach(s => {
      const dayId = Number(s.day_id);
      if (state.query && dayId !== lastDay) {
        const d = days.find(x => Number(x.id) === dayId);
        html += `<div class="day-head"><h2>${esc(d?.label || '')}</h2>
          <p>${esc(new Date(d.event_date + 'T00:00:00').toLocaleDateString('en-GB',
            { weekday: 'long', day: 'numeric', month: 'long' }))}</p></div>`;
        lastDay = dayId;
      }

      const hall = hallById[s.hall_id];
      const st = startsAt(s), en = endsAt(s);
      const running = st && st <= now && en && en > now;
      const past = en ? en <= now : (st && st < now);

      if (isBreak(s)) {
        html += `<div class="mesh-card is-break reveal">
          <div class="mc-col-1">
            <h3>${esc(s.title)}</h3>
            <div class="mc-time">${pretty(s.start_time)}${s.end_time ? ' – ' + pretty(s.end_time) : ''}</div>
          </div>
        </div>`;
        return;
      }

      const people = s.speakers || [];
      const shown = people.slice(0, 3);

      html += `<button class="mesh-card ${running ? 'is-now' : ''} ${past ? 'is-past' : ''} reveal"
        data-session="${s.id}" ${running ? 'id="now-anchor"' : ''}>
        <div class="mc-col-1">
          ${running ? '<div class="mc-live"><i></i>Live now</div>' : ''}
          <h3>${esc(s.title)}</h3>
          <div class="mc-time">${pretty(s.start_time)}${s.end_time ? ' – ' + pretty(s.end_time) : ''}</div>
        </div>
        <div class="mc-col-2">
          <div class="kv-grid">
            <span class="kv-key">Format</span>
            <span class="kv-val">${esc(TYPE_LABEL[s.session_type] || 'Session')}</span>
            <span class="kv-key">Location</span>
            <span class="kv-val">${hall ? esc(hall.name) : 'TBA'}</span>
          </div>
        </div>
        <div class="mc-col-3">
          ${people.length ? `<div class="mc-speakers">${shown.map(p => `
            <div class="mc-speaker-row">
              ${avatar(p)}
              <div class="mc-speaker-info">
                <span class="nm">${esc(p.name)}</span>
                <span class="dg">${esc([p.designation, p.organisation].filter(Boolean).join(', '))}</span>
              </div>
            </div>`).join('')}${people.length > 3
              ? `<div class="mc-speaker-row"><div class="mc-speaker-info">
                   <span class="dg">+ ${people.length - 3} more speakers</span></div></div>`
              : ''}</div>`
            : '<span class="kv-key">Speakers to be announced</span>'}
        </div>
      </button>`;
    });

    host.innerHTML = html;
    reveal();
  }

  // ------------------------------------------------------------- directory
  function renderDirectory() {
    const grid = $('#dir-grid');
    $('#dir-count').textContent = `${speakers.length} confirmed experts`;
    grid.innerHTML = speakers.map(sp => `
      <button class="dir-card reveal" data-speaker="${sp.id}">
        ${avatar({ name: sp.full_name, photo: sp.photo_path }, 'xl')}
        <div class="who">
          <span class="nm">${esc(sp.honorific ? sp.honorific + ' ' : '')}${esc(sp.full_name)}</span>
          <span class="og">${esc([sp.designation, sp.organisation].filter(Boolean).join(', '))}</span>
        </div>
      </button>`).join('');
  }

  // ----------------------------------------------------------------- sheet
  const sheet = $('#sheet');
  let lastFocus = null;

  function openSheet(html) {
    lastFocus = document.activeElement;
    $('#sheet-body').innerHTML = html;
    sheet.hidden = false;
    document.body.style.overflow = 'hidden';
    $('.sheet-close').focus();
  }

  function closeSheet() {
    sheet.hidden = true;
    document.body.style.overflow = '';
    lastFocus?.focus();
  }

  sheet.addEventListener('click', (e) => { if (e.target.closest('[data-close]')) closeSheet(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !sheet.hidden) closeSheet(); });

  function sessionSheet(id) {
    const s = sessions.find(x => Number(x.id) === Number(id));
    if (!s) return;
    const hall = hallById[s.hall_id];
    const d = dayOf(s);
    const people = s.speakers || [];

    openSheet(`
      <div class="kv" style="margin-bottom:0">
        <span style="color:var(--accent-cyan); font-weight:600">${esc(TYPE_LABEL[s.session_type] || 'Session')}</span>
        ${d ? `<span><b>${esc(d.label)}</b> · ${esc(new Date(d.event_date + 'T00:00:00')
          .toLocaleDateString('en-GB', { day: 'numeric', month: 'long' }))}</span>` : ''}
      </div>
      <h3 id="sheet-title">${esc(s.title)}</h3>
      <div class="kv">
        <span><b>${pretty(s.start_time)}${s.end_time ? ' – ' + pretty(s.end_time) : ''}</b></span>
        ${hall ? `<span style="color:${hallColor(s.hall_id)}">${esc(hall.name)}</span>` : ''}
        ${hall?.map_note ? `<span>${esc(hall.map_note)}</span>` : ''}
      </div>
      ${s.subtitle ? `<div class="body"><p><strong>${esc(s.subtitle)}</strong></p></div>` : ''}
      ${s.description ? `<div class="body">${s.description}</div>` : ''}
      ${people.length ? `<h4>Featured Experts</h4>${people.map(p => `
        <button class="sp-row" data-speaker="${p.id}">
          ${avatar(p, 'lg')}
          <span>
            <span class="sp-nm">${esc(p.honorific ? p.honorific + ' ' : '')}${esc(p.name)}</span>
            <span class="sp-dg">${esc([p.designation, p.organisation].filter(Boolean).join(', ')) || '—'}</span>
          </span>
          ${p.role && p.role !== 'panelist' ? `<span class="role-tag">${esc(p.role.replace('_', ' '))}</span>` : ''}
        </button>`).join('')}`
        : `<h4>Speakers</h4><p class="body">Being confirmed. Check back closer to the session.</p>`}
    `);
  }

  function speakerSheet(id) {
    const sp = speakers.find(x => Number(x.id) === Number(id));
    if (!sp) return;
    const appears = sessions.filter(s => (s.speakers || []).some(p => Number(p.id) === Number(id)));

    openSheet(`
      <div class="profile">
        ${avatar({ name: sp.full_name, photo: sp.photo_path }, 'xl')}
        <span>
          <h3 id="sheet-title" style="margin:0">${esc(sp.honorific ? sp.honorific + ' ' : '')}${esc(sp.full_name)}</h3>
          <p class="sp-dg" style="font-size:14px">${esc([sp.designation, sp.organisation].filter(Boolean).join(', '))}</p>
        </span>
      </div>
      ${sp.bio ? `<div class="body" style="margin-top:20px">${sp.bio}</div>` : ''}
      ${appears.length ? `<h4>Appearing In</h4>${appears.map(s => {
        const d = dayOf(s);
        return `<button class="sp-row" data-session="${s.id}">
          <span>
            <span class="sp-nm">${esc(s.title)}</span>
            <span class="sp-dg">${esc(d?.label || '')} · ${pretty(s.start_time)}${
              s.hall_id ? ' · ' + esc(hallById[s.hall_id]?.name || '') : ''}</span>
          </span>
        </button>`;
      }).join('')}` : ''}
      ${sp.linkedin_url ? `<a class="linkedin" href="${esc(sp.linkedin_url)}" target="_blank" rel="noopener">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
        LinkedIn Profile</a>` : ''}
    `);
  }

  document.addEventListener('click', (e) => {
    const sp = e.target.closest('[data-speaker]');
    if (sp) { speakerSheet(sp.dataset.speaker); return; }
    const se = e.target.closest('[data-session]');
    if (se) { sessionSheet(se.dataset.session); }
  });

  // ---------------------------------------------------------------- reveal
  let io;
  function reveal() {
    if (!('IntersectionObserver' in window)) {
      $$('.reveal').forEach(el => el.classList.add('in'));
      return;
    }
    io?.disconnect();
    io = new IntersectionObserver((entries) => {
      entries.forEach((en, i) => {
        if (!en.isIntersecting) return;
        setTimeout(() => en.target.classList.add('in'), Math.min(i * 25, 120));
        io.unobserve(en.target);
      });
    }, { rootMargin: '0px 0px -5% 0px' });
    $$('.reveal').forEach(el => io.observe(el));
  }

  // ------------------------------------------------------------------ boot
  renderControls();
  renderTimeline();
  renderDirectory();
  renderLive();
  tickClock();
  reveal();

  setInterval(tickClock, 10000);
  setInterval(() => { renderLive(); renderTimeline(); }, 60000);

  // Land the attendee on what is running, not at the top of the day.
  requestAnimationFrame(() => {
    const anchor = $('#now-anchor');
    if (!anchor) return;
    const y = anchor.getBoundingClientRect().top + window.scrollY - 180;
    window.scrollTo({ top: y, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
  });
})();


/* ---------------------------------------------------------------------------
   Bottom navigation: tilt, press ripple, and smooth scrolling to sections.
   --------------------------------------------------------------------------- */
(() => {
  'use strict';

  const nav = document.querySelector('.bottom-nav');
  if (!nav) return;

  const canvas = nav.querySelector('.bottom-nav__ripple');
  const ctx = canvas ? canvas.getContext('2d') : null;
  const ripples = [];

  let targetTiltX = 0, targetTiltY = 0;
  let currentTiltX = 0, currentTiltY = 0;

  function resizeCanvas() {
    if (!ctx) return;                 // canvas can be unavailable or blocked
    const rect = nav.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;
    canvas.width = Math.round(rect.width * ratio);
    canvas.height = Math.round(rect.height * ratio);
    canvas.style.width = `${rect.width}px`;
    canvas.style.height = `${rect.height}px`;
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
  }

  function animateTilt() {
    currentTiltX += (targetTiltX - currentTiltX) * 0.14;
    currentTiltY += (targetTiltY - currentTiltY) * 0.14;
    nav.style.setProperty('--tilt-x', `${currentTiltX.toFixed(2)}deg`);
    nav.style.setProperty('--tilt-y', `${currentTiltY.toFixed(2)}deg`);
    requestAnimationFrame(animateTilt);
  }

  function renderRipples() {
    if (!ctx) return;
    const rect = nav.getBoundingClientRect();
    ctx.clearRect(0, 0, rect.width, rect.height);

    for (let i = ripples.length - 1; i >= 0; i--) {
      const r = ripples[i];
      r.radius += 4;
      r.alpha *= 0.90;
      ctx.beginPath();
      ctx.arc(r.x, r.y, r.radius, 0, Math.PI * 2);
      ctx.strokeStyle = `rgba(81, 53, 255, ${r.alpha})`;
      ctx.lineWidth = 2.2;
      ctx.stroke();
      if (r.alpha < 0.01) ripples.splice(i, 1);
    }
    requestAnimationFrame(renderRipples);
  }

  nav.addEventListener('pointermove', (e) => {
    const rect = nav.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width;
    const y = (e.clientY - rect.top) / rect.height;
    nav.style.setProperty('--mouse-x', `${(x * 100).toFixed(1)}%`);
    nav.style.setProperty('--mouse-y', `${(y * 100).toFixed(1)}%`);
    targetTiltY = (x - 0.5) * 6;
    targetTiltX = (0.5 - y) * 6;
  });

  nav.addEventListener('pointerleave', () => { targetTiltX = 0; targetTiltY = 0; });

  nav.addEventListener('pointerdown', (e) => {
    const rect = nav.getBoundingClientRect();
    nav.style.setProperty('--nav-scale', '0.985');
    ripples.push({ x: e.clientX - rect.left, y: e.clientY - rect.top, radius: 5, alpha: 0.5 });
  });

  window.addEventListener('pointerup', () => nav.style.setProperty('--nav-scale', '1'));

  document.querySelectorAll('.nav-item').forEach((item) => {
    item.addEventListener('click', (e) => {
      const target = item.getAttribute('href');
      if (target && target.startsWith('#') && target.length > 1) {
        e.preventDefault();
        const el = document.querySelector(target);
        if (el) {
          const y = el.getBoundingClientRect().top + window.scrollY - 150;
          window.scrollTo({ top: y, behavior: 'smooth' });
        }
      } else if (target === '#') {
        e.preventDefault();
      }
      document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
      item.classList.add('active');
    });
  });

  window.addEventListener('resize', resizeCanvas);
  resizeCanvas();
  animateTilt();
  if (ctx) renderRipples();
})();
