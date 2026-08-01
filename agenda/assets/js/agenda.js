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

  /* ------------------------------------------------------------------------
     Speaker order on a card: alphabetical.

     Sorted on the first real word of the name, so honorifics do not bunch
     everyone together — "Dr. Rakshit Tandon" files under R, not D.
     ------------------------------------------------------------------------ */
  const HONORIFICS = /^(dr|mr|mrs|ms|prof|adv|ca|cs|lt|col|maj|gen|brig|air|vice|marshal|shri|smt|sh|retd|avm|dy|sp|ips|its|justice|major|general|vsm|avsm|sysm|er|cdr|cmde)\.?$/i;

  function sortName(p) {
    const raw = String(p.name || p.full_name || '')
      .replace(/\([^)]*\)/g, ' ')            // drop "(Retd.)", "(Dr)" and the like
      .replace(/[^\p{L}\p{N} ]/gu, ' ');
    const words = raw.split(/\s+/).filter(Boolean);
    const real = words.filter(w => !HONORIFICS.test(w));
    return (real.length ? real : words).join(' ').toLowerCase();
  }

  const byName = (a, b) =>
    sortName(a).localeCompare(sortName(b), 'en', { sensitivity: 'base' });

  // Straight A-Z, moderators included. Their role still shows as a tag on
  // the session card, so the ordering does not hide who is chairing.
  sessions.forEach(s => {
    if (Array.isArray(s.speakers)) s.speakers.sort(byName);
  });

  // The directory uses the same basis.
  speakers.sort(byName);

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
    if (sp.photo) return `<img class="av ${cls}" src="${esc(sp.photo)}" alt="" loading="lazy">`;
    return `<span class="av ${cls}" style="--av:${hueFor(key)}" aria-hidden="true">${esc(initials(key))}</span>`;
  };

  // The real instant. Session times below are parsed with an explicit +05:30,
  // so comparisons hold whatever timezone the phone is set to.
  const istNow = () => new Date();

  // Today's date in IST, as YYYY-MM-DD.
  const istDate = () => new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Kolkata' });

  const dayOf = (s) => days.find(d => Number(d.id) === Number(s.day_id));
  const startsAt = (s) => {
    const d = dayOf(s);
    if (!d) return null;
    return new Date(`${d.event_date}T${String(s.start_time).padStart(8, '0')}+05:30`);
  };
  const endsAt = (s) => {
    const d = dayOf(s);
    if (!d || !s.end_time) return null;
    return new Date(`${d.event_date}T${String(s.end_time).padStart(8, '0')}+05:30`);
  };

  // hall: null = every hall, otherwise the id of the selected one.
  const state = { day: null, hall: null, query: '' };

  // Open on the day that is actually happening, otherwise the first day.
  (() => {
    const today = istDate();
    const match = days.find(d => d.event_date === today);
    state.day = Number((match || days[0] || {}).id) || null;

    // Land on the Main Hall: most attendees are there, and showing both
    // tracks interleaved makes the day look busier than it is.
    const main = halls.find(h => /main/i.test(h.name)) || halls[0];
    state.hall = main ? Number(main.id) : null;
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
        <div class="live-label">Agenda Status</div>
        <p class="live-title">${first && first > now ? 'Awaiting commencement…' : 'Summit Concluded'}</p>
        <div class="live-meta"><span>${esc(D.event?.venue || '')}</span></div>
      </div>`;
      return;
    }

    if (!running.length && next) {
      el.innerHTML = `<div class="live-card is-idle">
        <div class="live-label">Up Next</div>
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
      <div class="live-label">Happening Now</div>
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
    const el = $('#clock-time');
    if (el) el.textContent = new Date().toLocaleTimeString('en-GB', {
      timeZone: 'Asia/Kolkata', hour: '2-digit', minute: '2-digit', hour12: false,
    });
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

    // With only one hall visible there is nothing to switch between, so the
    // control hides itself rather than showing a lone dead button.
    const hf = $('#hall-filters');
    if (halls.length < 2) {
      hf.hidden = true;
      return;
    }
    hf.innerHTML = halls.map(h =>
      `<button class="hall-tab" role="tab" data-hall="${h.id}"
        aria-selected="${state.hall === Number(h.id)}">${esc(h.name)}</button>`
    ).join('');

    // Tapping the selected hall again clears the filter and shows both.
    hf.addEventListener('click', (e) => {
      const b = e.target.closest('.hall-tab');
      if (!b) return;
      const id = Number(b.dataset.hall);
      state.hall = (state.hall === id) ? null : id;
      $$('.hall-tab', hf).forEach(t =>
        t.setAttribute('aria-selected', String(Number(t.dataset.hall) === state.hall)));
      renderTimeline();
      renderDirectory();
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
    // Breaks are venue-wide, so they stay visible whichever hall is picked.
    if (state.hall !== null && Number(s.hall_id) !== state.hall) {
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
          ${running ? '<div class="mc-live">Live now</div>' : ''}
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

    // Show only the people appearing in the hall currently selected.
    let list = speakers;
    if (state.hall !== null) {
      const inHall = new Set();
      sessions
        .filter(s => Number(s.hall_id) === state.hall)
        .forEach(s => (s.speakers || []).forEach(p => inHall.add(Number(p.id))));
      list = speakers.filter(sp => inHall.has(Number(sp.id)));
    }

    $('#dir-count').textContent = `${list.length} confirmed experts`;
    grid.innerHTML = list.map(sp => `
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

    const dStr = d ? `${d.label} · ${new Date(d.event_date + 'T00:00:00')
      .toLocaleDateString('en-GB', { day: 'numeric', month: 'long' })}` : '';

    openSheet(`
      <div class="kv" style="margin-bottom:12px; font-weight:600">
        <span style="color:var(--accent-cyan)">${esc(TYPE_LABEL[s.session_type] || 'Session')}</span>
        ${dStr ? `<span style="color:var(--text-main)">${esc(dStr)}</span>` : ''}
      </div>
      <h3 id="sheet-title" style="margin-top:0; font-size:28px; margin-bottom:12px">${esc(s.title)}</h3>
      <div class="kv" style="margin-bottom:32px">
        <span><b>${pretty(s.start_time)}${s.end_time ? ' – ' + pretty(s.end_time) : ''}</b></span>
        ${hall ? `<span style="color:var(--accent-cyan); font-weight:500">${esc(hall.name)}</span>` : ''}
        ${hall?.map_note ? `<span>${esc(hall.map_note)}</span>` : ''}
      </div>
      ${s.subtitle ? `<div class="body"><p><strong>${esc(s.subtitle)}</strong></p></div>` : ''}
      ${s.description ? `<div class="body">${s.description}</div>` : ''}
      ${people.length ? `<h4>Featured Experts</h4>${people.map(p => `
        <button class="sp-row" data-speaker="${p.id}">
          ${avatar(p, 'xl')}
          <span>
            <span class="sp-nm" style="font-size:20px; display:block; margin-bottom:4px">${esc(p.honorific ? p.honorific + ' ' : '')}${esc(p.name)}</span>
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
      <div class="profile" style="margin-bottom:32px; gap:20px">
        ${avatar({ name: sp.full_name, photo: sp.photo_path }, 'xl')}
        <div style="display:flex; flex-direction:column; justify-content:center">
          <h3 id="sheet-title" style="margin:0; font-size:26px">${esc(sp.honorific ? sp.honorific + ' ' : '')}${esc(sp.full_name)}</h3>
          <p class="sp-dg" style="font-size:15px; margin:4px 0 0 0; display:block">${esc([sp.designation, sp.organisation].filter(Boolean).join(', '))}</p>
        </div>
      </div>
      ${sp.bio ? `<div class="body" style="margin-top:20px">${sp.bio}</div>` : ''}
      ${appears.length ? `<h4>Appearing In</h4>${appears.map(s => {
        const d = dayOf(s);
        return `<button class="sp-row" data-session="${s.id}">
          <span>
            <span class="sp-nm">${esc(s.title)}</span>
            <span class="sp-meta">${esc(d?.label || '')} · ${pretty(s.start_time)}${
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

  const VENUE_QUERY = 'Bharat Mandapam Gate No 7, Pragati Maidan, New Delhi';

  function venueSheet() {
    const q = encodeURIComponent(VENUE_QUERY);
    openSheet(`
      <h3 id="sheet-title" style="margin-top:0">Getting to the venue</h3>
      <div class="kv" style="margin-bottom:20px">
        <span>Bharat Mandapam, Pragati Maidan, New Delhi</span>
      </div>

      <div class="venue-gate">
        <span class="num">7</span>
        <span class="txt">
          <b>Enter through Gate No. 7</b>
          <span>Nearest metro: Supreme Court (Blue Line). Delegate entry and
                registration are just inside this gate.</span>
        </span>
      </div>

      <!-- To use your own venue map instead, replace this iframe with:
           <img src="images/venue-map.jpg" alt="Venue map showing Gate No. 7"> -->
      <div class="venue-map">
        <iframe src="https://maps.google.com/maps?q=${q}&z=16&output=embed"
                title="Map of Bharat Mandapam, Gate No. 7"
                loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen></iframe>
      </div>

      <div class="venue-actions">
        <a class="btn-directions" target="_blank" rel="noopener"
           href="https://www.google.com/maps/dir/?api=1&destination=${q}">
          <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
          </svg>
          Get directions
        </a>
        <a class="btn-map" target="_blank" rel="noopener"
           href="https://www.google.com/maps/search/?api=1&query=${q}">
          <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
            <circle cx="12" cy="10" r="3"></circle>
          </svg>
          Open in Maps
        </a>
      </div>
    `);
  }

  document.addEventListener('click', (e) => {
    const venue = e.target.closest('[data-sheet="venue"]');
    if (venue) { e.preventDefault(); venueSheet(); return; }
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
    const still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    window.scrollTo({ top: y, behavior: still ? 'auto' : 'smooth' });
  });
})();


/* ---------------------------------------------------------------------------
   Bottom navigation: smooth scroll to a section, and the active state.
   --------------------------------------------------------------------------- */
(() => {
  'use strict';

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
})();
