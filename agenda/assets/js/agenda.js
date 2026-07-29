/* FutureCrime Summit — public agenda behaviour.
   The page is time-aware: it knows which session is running and says so. */

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

  // Deterministic hue per person, so a speaker's monogram colour never
  // shifts between the card, the session sheet and the directory.
  const HUES = ['#a9e3de', '#b6d4f5', '#cfc3f2', '#f5c3cc', '#f2d8a8', '#bce3c8', '#e6c9ee', '#bcdcee'];
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

  const hallColor = (id) => hallById[id]?.color_hex || (id ? '#00D4C8' : '#3A4A69');

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
    sponsor: 'Sponsor',
    break: 'Break', lunch: 'Lunch', networking: 'Networking', award: 'Awards', other: 'Session'
  };
  const isBreak = (s) => ['break', 'lunch', 'networking'].includes(s.session_type);

  const avatar = (sp, cls = '') => {
    const key = sp.name || sp.full_name || '';
    if (sp.photo) return `<img class="av ${cls}" src="${esc(sp.photo)}" alt="" loading="lazy" width="30" height="30">`;
    return `<span class="av ${cls}" style="--av:${hueFor(key)}" aria-hidden="true">${esc(initials(key))}</span>`;
  };

  // -------------------------------------------------------------- clock
  // Event time is IST regardless of where the device thinks it is —
  // an attendee's phone on roaming should not shift the schedule.
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

  // Open on the day that is actually happening, else the first day.
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
        <div class="live-label"><span class="pulse"></span>Programme</div>
        <p class="live-title">${first && first > now ? 'The summit has not started yet' : 'That is a wrap'}</p>
        <div class="live-meta"><span>${esc(D.event?.venue || '')}</span></div>
      </div>`;
      return;
    }

    if (!running.length && next) {
      el.innerHTML = `<div class="live-card is-idle">
        <div class="live-label"><span class="pulse"></span>Up next</div>
        <p class="live-title">${esc(next.title)}</p>
        <div class="live-meta">
          <span><b>${pretty(next.start_time)}</b></span>
          ${next.hall_id ? `<span>${esc(hallById[next.hall_id]?.name || '')}</span>` : ''}
          <span>${countdown(startsAt(next) - now)} from now</span>
        </div>
      </div>`;
      return;
    }

    const s = running[0];
    const st = startsAt(s), en = endsAt(s);
    const pct = Math.min(100, Math.max(0, ((now - st) / (en - st)) * 100));
    const others = running.slice(1);

    el.innerHTML = `<div class="live-card">
      <div class="live-label"><span class="pulse"></span>Happening now</div>
      <p class="live-title">${esc(s.title)}</p>
      <div class="live-meta">
        ${s.hall_id ? `<span><b>${esc(hallById[s.hall_id]?.name || '')}</b></span>` : ''}
        <span>ends ${pretty(s.end_time)} · ${countdown(en - now)} left</span>
        ${s.speakers?.length ? `<span>${s.speakers.length} speaker${s.speakers.length > 1 ? 's' : ''}</span>` : ''}
      </div>
      <div class="live-bar"><span style="width:${pct.toFixed(1)}%"></span></div>
      ${others.length ? `<div class="live-next"><em>Also on</em><b>${esc(others[0].title)}</b>
        <span>${esc(hallById[others[0].hall_id]?.name || '')}</span></div>` : ''}
      ${next ? `<div class="live-next"><em>Up next</em><b>${esc(next.title)}</b>
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
    const day = days.find(d => Number(d.id) === state.day);

    // Searching looks across both days — a name is not a day-scoped thing.
    const scope = state.query
      ? sessions
      : sessions.filter(s => Number(s.day_id) === state.day);

    const list = scope.filter(matches);
    $('#empty').hidden = list.length > 0;

    if (!list.length) { host.innerHTML = ''; return; }

    // Group by start time so parallel halls sit side by side.
    const groups = new Map();
    list.forEach(s => {
      const key = `${s.day_id}|${s.start_time}`;
      if (!groups.has(key)) groups.set(key, []);
      groups.get(key).push(s);
    });

    let html = '';
    let lastDay = null;

    [...groups.entries()]
      .sort((a, b) => a[0].localeCompare(b[0]))
      .forEach(([key, items]) => {
        const dayId = Number(key.split('|')[0]);
        if (state.query && dayId !== lastDay) {
          const d = days.find(x => Number(x.id) === dayId);
          html += `<div class="day-head"><h2>${esc(d?.label || '')}</h2>
            <p>${esc(new Date(d.event_date + 'T00:00:00').toLocaleDateString('en-GB',
              { weekday: 'long', day: 'numeric', month: 'long' }))}</p></div>`;
          lastDay = dayId;
        }

        const first = items[0];
        const st = startsAt(first), en = endsAt(first);
        const running = st && st <= now && en && en > now;
        const past = en ? en <= now : (st && st < now);

        html += `<div class="slot ${items.length > 1 ? 'parallel' : ''} ${running ? 'is-now' : ''} ${past ? 'is-past' : ''}"
                      ${running ? 'id="now-anchor"' : ''}>
          <div class="slot-time">
            <span class="start">${hhmm(first.start_time)}</span>
            ${first.end_time ? `<span class="end">${hhmm(first.end_time)}</span>` : ''}
            <span class="slot-node"></span>
          </div>
          <div class="slot-cards">${items.map(s => card(s, now)).join('')}</div>
        </div>`;
      });

    host.innerHTML = html;
    reveal();
  }

  function card(s, now) {
    const hall = hallById[s.hall_id];
    const st = startsAt(s), en = endsAt(s);
    const running = st && st <= now && en && en > now;

    if (isBreak(s)) {
      return `<div class="card is-break"><h3>${esc(s.title)}</h3></div>`;
    }

    const people = s.speakers || [];
    const shown = people.slice(0, 5);

    return `<button class="card ${running ? 'is-now' : ''} reveal" data-session="${s.id}"
      style="--hall:${hallColor(s.hall_id)}">
      <div class="card-top">
        ${running ? '<span class="now-tag"><i></i>Live</span>' : ''}
        ${hall ? `<span class="card-hall">${esc(hall.name)}</span>` : ''}
        <span class="card-type">${esc(TYPE_LABEL[s.session_type] || 'Session')}</span>
        ${s.end_time ? `<span>${pretty(s.start_time)} – ${pretty(s.end_time)}</span>` : ''}
      </div>
      <h3>${esc(s.title)}</h3>
      ${s.subtitle ? `<p class="sub">${esc(s.subtitle)}</p>` : ''}
      ${people.length ? `<div class="card-people">
        <div class="stack">${shown.map(p => avatar(p)).join('')}</div>
        <span class="people-note"><b>${esc(people[0].name)}</b>${people.length > 1
          ? ` and ${people.length - 1} more` : ''}</span>
      </div>` : ''}
    </button>`;
  }

  // ------------------------------------------------------------- directory
  function renderDirectory() {
    const grid = $('#dir-grid');
    $('#dir-count').textContent = `${speakers.length} confirmed`;
    grid.innerHTML = speakers.map(sp => `
      <button class="dir-card reveal" data-speaker="${sp.id}">
        ${avatar({ name: sp.full_name, photo: sp.photo_path }, 'lg')}
        <span class="who">
          <span class="nm">${esc(sp.honorific ? sp.honorific + ' ' : '')}${esc(sp.full_name)}</span>
          <span class="og">${esc([sp.designation, sp.organisation].filter(Boolean).join(', '))}</span>
        </span>
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
        <span>${esc(TYPE_LABEL[s.session_type] || 'Session')}</span>
        ${d ? `<span><b>${esc(d.label)}</b> · ${esc(new Date(d.event_date + 'T00:00:00')
          .toLocaleDateString('en-GB', { day: 'numeric', month: 'long' }))}</span>` : ''}
      </div>
      <h3 id="sheet-title">${esc(s.title)}</h3>
      <div class="kv">
        <span><b>${pretty(s.start_time)}${s.end_time ? ' – ' + pretty(s.end_time) : ''}</b></span>
        ${hall ? `<span>${esc(hall.name)}</span>` : ''}
        ${hall?.map_note ? `<span>${esc(hall.map_note)}</span>` : ''}
      </div>
      ${s.subtitle ? `<div class="body"><p><strong>${esc(s.subtitle)}</strong></p></div>` : ''}
      ${s.description ? `<div class="body">${s.description}</div>` : ''}
      ${people.length ? `<h4>On this panel</h4>${people.map(p => `
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
          <p class="sp-dg" style="font-size:13.5px">${esc([sp.designation, sp.organisation].filter(Boolean).join(', '))}</p>
        </span>
      </div>
      ${sp.bio ? `<div class="body" style="margin-top:16px">${sp.bio}</div>` : ''}
      ${appears.length ? `<h4>Appearing in</h4>${appears.map(s => {
        const d = dayOf(s);
        return `<button class="sp-row" data-session="${s.id}">
          <span>
            <span class="sp-nm">${esc(s.title)}</span>
            <span class="sp-dg">${esc(d?.label || '')} · ${pretty(s.start_time)}${
              s.hall_id ? ' · ' + esc(hallById[s.hall_id]?.name || '') : ''}</span>
          </span>
        </button>`;
      }).join('')}` : ''}
      ${sp.linkedin_url ? `<a class="linkedin" href="${esc(sp.linkedin_url)}" target="_blank" rel="noopener">LinkedIn profile ↗</a>` : ''}
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
        setTimeout(() => en.target.classList.add('in'), Math.min(i * 28, 160));
        io.unobserve(en.target);
      });
    }, { rootMargin: '0px 0px -8% 0px' });
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
    const y = anchor.getBoundingClientRect().top + window.scrollY - 150;
    window.scrollTo({ top: y, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
  });
})();
