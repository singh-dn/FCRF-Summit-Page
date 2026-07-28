/* FutureCrime Summit — admin console.
   One shell, panels swapped client-side. Every mutation goes through
   api.php with the CSRF header and is written to the audit log server-side. */

(() => {
  'use strict';

  const A = window.FCS_ADMIN;
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const can = () => true;   // one password, full access

  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const hhmm = (t) => String(t || '').slice(0, 5);
  const dayLabel = (id) => A.days.find(d => +d.id === +id)?.label || '—';
  const hallName = (id) => A.halls.find(h => +h.id === +id)?.name || '—';

  const TYPES = ['panel', 'keynote', 'fireside', 'workshop', 'plenary', 'inauguration',
                 'valedictory', 'break', 'lunch', 'networking', 'award', 'other'];
  const ROLES = ['panelist', 'speaker', 'moderator', 'chair', 'chief_guest', 'keynote', 'host'];

  // ------------------------------------------------------------------ net
  async function api(action, data, opts = {}) {
    const isForm = data instanceof FormData;
    const init = { method: data ? 'POST' : 'GET', headers: {} };
    if (data) {
      init.headers['X-CSRF-Token'] = A.csrf;
      if (isForm) init.body = data;
      else { init.headers['Content-Type'] = 'application/json'; init.body = JSON.stringify(data); }
    }
    const r = await fetch('api.php?action=' + action + (opts.qs ? '&' + opts.qs : ''), init);
    let d;
    try { d = await r.json(); } catch { throw new Error('The server sent something unreadable.'); }
    if (r.status === 401) { location.reload(); throw new Error('Signed out.'); }
    if (!r.ok || d.ok === false) throw new Error(d.error || 'That did not save.');
    return d;
  }

  function toast(msg, kind = 'ok') {
    const el = document.createElement('div');
    el.className = 'px-4 py-2.5 rounded-lg text-[13px] border shadow-lg';
    el.style.cssText = kind === 'ok'
      ? 'background:#fff;border-color:#bfe6e2;color:#0f6f68'
      : 'background:#fdf6f5;border-color:#e8cdc8;color:#b3392a';
    el.textContent = msg;
    $('#toast').append(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; }, 2600);
    setTimeout(() => el.remove(), 3000);
  }

  function flashSaved() {
    const s = $('#saved');
    s.style.opacity = '1';
    setTimeout(() => (s.style.opacity = '0'), 1400);
  }

  // ---------------------------------------------------------------- modal
  const modal = $('#modal');
  function openModal(title, html, onMount) {
    $('#modal-title').textContent = title;
    $('#modal-body').innerHTML = html;
    modal.hidden = false;
    onMount?.($('#modal-body'));
    $('#modal-body').querySelector('input, select, textarea')?.focus();
  }
  const closeModal = () => { modal.hidden = true; $('#modal-body').innerHTML = ''; };
  modal.addEventListener('click', e => { if (e.target.closest('[data-close]')) closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

  function confirmAction(message, detail, confirmLabel, onYes) {
    openModal('Are you sure?', `
      <p class="text-[14.5px] mb-1.5">${esc(message)}</p>
      <p class="text-[13px] text-soft mb-6">${esc(detail)}</p>
      <div class="flex gap-2.5 justify-end">
        <button class="btn btn-ghost" data-close>Keep it</button>
        <button class="btn btn-danger" id="yes">${esc(confirmLabel)}</button>
      </div>`, (root) => {
      $('#yes', root).addEventListener('click', async () => { closeModal(); await onYes(); });
    });
  }

  // ------------------------------------------------------------- shell nav
  const PANELS = {
    dashboard: ['Dashboard', renderDashboard],
    agenda: ['Agenda', renderAgenda],
    speakers: ['Speakers', renderSpeakers],
    tracks: ['Tracks', () => renderTaxonomy('track')],
    venue: ['Venue', () => renderTaxonomy('hall')],
    history: ['History', renderHistory],
    trash: ['Trash', renderTrash],
    settings: ['Settings', renderSettings],
  };

  function go(key) {
    if (!PANELS[key]) key = 'dashboard';
    $$('.panel').forEach(p => (p.hidden = true));
    $('#panel-' + key).hidden = false;
    $('#panel-title').textContent = PANELS[key][0];
    $$('.nav-item').forEach(b => {
      const on = b.dataset.panel === key;
      b.style.background = on ? '#fff' : '';
      b.style.color = on ? '#16181d' : '';
    });
    if (window.innerWidth < 1024) $('#sidebar').classList.add('-translate-x-full');
    location.hash = key;
    PANELS[key][1]();
  }

  $('#nav').addEventListener('click', e => {
    const b = e.target.closest('.nav-item');
    if (b) go(b.dataset.panel);
  });
  $('#menu')?.addEventListener('click', () => $('#sidebar').classList.toggle('-translate-x-full'));
  $('#logout').addEventListener('click', async () => {
    await api('auth.logout', {});
    location.href = 'agenda.php';
  });

  // ------------------------------------------------------------- dashboard
  async function renderDashboard() {
    const host = $('#panel-dashboard');
    host.innerHTML = `<p class="text-soft text-sm">Loading…</p>`;
    const d = await api('dash.stats');
    const s = d.stats;

    const tile = (label, value, note = '', accent = '#E8EEF7') => `
      <div class="bg-white border border-rule rounded-xl p-4">
        <div class="font-mono text-[10.5px] tracking-[.13em] uppercase text-soft">${esc(label)}</div>
        <div class="text-[28px] font-extrabold tracking-tight mt-1.5" style="color:${accent}">${value}</div>
        ${note ? `<div class="text-[12px] text-soft mt-0.5">${esc(note)}</div>` : ''}
      </div>`;

    host.innerHTML = `
      <div class="grid gap-3 mb-6" style="grid-template-columns:repeat(auto-fit,minmax(168px,1fr))">
        ${tile('Updates today', s.updates_today, '', '#0f9d94')}
        ${tile('Sessions', s.sessions, `${s.sessions_live} published`)}
        ${tile('Speakers', s.speakers, `${s.speakers_live} published`)}
        </div>

      ${(s.sessions_no_speaker || s.in_trash) ? `
      <div class="grid gap-3 mb-6" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
        ${s.sessions_no_speaker ? `<div class="rounded-xl p-4 border" style="background:#fdf9f0;border-color:#f0dfc0">
          <div class="text-[13.5px] font-semibold" style="color:#a5640a">${s.sessions_no_speaker} sessions have no speaker yet</div>
          <button class="btn btn-ghost mt-2.5" data-goto="agenda">Open the agenda</button>
        </div>` : ''}
        ${s.in_trash ? `<div class="rounded-xl p-4 border border-rule bg-white">
          <div class="text-[13.5px] font-semibold">${s.in_trash} items in the trash</div>
          <button class="btn btn-ghost mt-2.5" data-goto="trash">Review them</button>
        </div>` : ''}
      </div>` : ''}

      <h3 class="font-mono text-[11px] tracking-[.14em] uppercase text-soft mb-3">Recent activity</h3>
      <div class="bg-white border border-rule rounded-xl divide-y divide-rule">
        ${d.feed.length ? d.feed.map(f => `
          <div class="px-4 py-3 flex items-center gap-3 text-[13px]">
            <span class="font-mono text-[10.5px] px-2 py-0.5 rounded"
                  style="background:#f6f7f9;color:#6b7280">${esc(f.action)}</span>
            <span class="truncate"><b>${esc(f.entity_label || f.entity_type)}</b></span>
            <span class="text-soft ml-auto shrink-0 text-[12px]">${esc(f.user_name || 'system')} · ${timeAgo(f.created_at)}</span>
          </div>`).join('')
        : `<p class="px-4 py-6 text-soft text-sm">Nothing has been changed yet.</p>`}
      </div>`;

    $$('[data-goto]', host).forEach(b => b.addEventListener('click', () => go(b.dataset.goto)));
  }

  function timeAgo(ts) {
    const then = new Date(ts.replace(' ', 'T'));
    const mins = Math.round((Date.now() - then.getTime()) / 60000);
    if (mins < 1) return 'just now';
    if (mins < 60) return mins + ' min ago';
    if (mins < 1440) return Math.floor(mins / 60) + 'h ago';
    return then.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
  }

  // ---------------------------------------------------------------- agenda
  let SESSIONS = [], SPEAKERS = [];

  async function loadSpeakers() {
    SPEAKERS = (await api('speaker.list')).speakers;
    return SPEAKERS;
  }

  async function renderAgenda() {
    const host = $('#panel-agenda');
    host.innerHTML = `<p class="text-soft text-sm">Loading…</p>`;
    SESSIONS = (await api('session.list')).sessions;
    if (!SPEAKERS.length) await loadSpeakers();

    const dayId = +(host.dataset.day || A.days[0]?.id || 0);
    host.dataset.day = dayId;

    host.innerHTML = `
      <div class="flex items-center gap-2.5 flex-wrap mb-5">
        <div class="flex gap-1.5">
          ${A.days.map(d => `<button class="btn ${+d.id === dayId ? 'btn-primary' : 'btn-ghost'}"
            data-day="${d.id}">${esc(d.label)}</button>`).join('')}
        </div>
        ${can('agenda.create') ? `<button class="btn btn-primary ml-auto" id="new-session">New session</button>` : ''}
      </div>
      <p class="text-[12.5px] text-soft mb-3">
        ${can('agenda.reorder') ? 'Drag a row to reorder it within the day.' : ''}
      </p>
      <div id="session-list" class="grid gap-2"></div>`;

    $$('[data-day]', host).forEach(b => b.addEventListener('click', () => {
      host.dataset.day = b.dataset.day; renderAgenda();
    }));
    $('#new-session', host)?.addEventListener('click', () => sessionForm(null));

    drawSessions(dayId);
  }

  function drawSessions(dayId) {
    const list = $('#session-list');
    const rows = SESSIONS.filter(s => +s.day_id === dayId);
    if (!rows.length) {
      list.innerHTML = `<p class="text-soft text-sm py-8">No sessions on this day yet. Add the first one.</p>`;
      return;
    }

    list.innerHTML = rows.map(s => `
      <div class="session-row bg-white border border-rule rounded-xl px-4 py-3 flex items-center gap-3.5"
           draggable="${can('agenda.reorder')}" data-id="${s.id}">
        <div class="font-mono text-[12.5px] text-center shrink-0" style="width:52px">
          <div class="font-bold">${hhmm(s.start_time)}</div>
          <div class="text-soft text-[10.5px]">${hhmm(s.end_time) || '—'}</div>
        </div>
        <div class="min-w-0 flex-1">
          <div class="text-[14px] font-semibold truncate">${esc(s.title)}</div>
          <div class="text-[11.5px] text-soft mt-0.5 font-mono">
            ${esc(hallName(s.hall_id))} · ${esc(s.session_type)}
            ${s.speakers.length ? ` · ${s.speakers.length} speaker${s.speakers.length > 1 ? 's' : ''}`
                                : ` · <span style="color:#a5640a">no speakers</span>`}
          </div>
        </div>
        <span class="font-mono text-[10px] px-2 py-1 rounded shrink-0"
              style="${s.status === 'published'
                ? 'background:#e7f6f4;color:#0f7d76'
                : 'background:#fdf3e3;color:#a5640a'}">${s.status}</span>
        <div class="flex gap-1.5 shrink-0">
          ${can('agenda.publish') ? `<button class="btn btn-ghost px-2.5 py-1.5 text-[12px]" data-act="publish" data-id="${s.id}">
            ${s.status === 'published' ? 'Unpublish' : 'Publish'}</button>` : ''}
          ${can('agenda.edit') ? `<button class="btn btn-ghost px-2.5 py-1.5 text-[12px]" data-act="edit" data-id="${s.id}">Edit</button>` : ''}
          ${can('agenda.create') ? `<button class="btn btn-ghost px-2.5 py-1.5 text-[12px]" data-act="dupe" data-id="${s.id}">Duplicate</button>` : ''}
          ${can('history.view') ? `<button class="btn btn-ghost px-2.5 py-1.5 text-[12px]" data-act="versions" data-id="${s.id}">History</button>` : ''}
          ${can('agenda.delete') ? `<button class="btn btn-danger px-2.5 py-1.5 text-[12px]" data-act="del" data-id="${s.id}">Delete</button>` : ''}
        </div>
      </div>`).join('');

    list.onclick = async (e) => {
      const b = e.target.closest('[data-act]');
      if (!b) return;
      const id = +b.dataset.id;
      const s = SESSIONS.find(x => +x.id === id);
      try {
        if (b.dataset.act === 'edit') return sessionForm(s);
        if (b.dataset.act === 'versions') return versionList('session', id, s.title);
        if (b.dataset.act === 'publish') {
          await api('session.publish', { id, publish: s.status !== 'published' });
          flashSaved(); return renderAgenda();
        }
        if (b.dataset.act === 'dupe') {
          await api('session.duplicate', { id });
          toast('Duplicated as a draft.'); return renderAgenda();
        }
        if (b.dataset.act === 'del') {
          return confirmAction(`Delete “${s.title}”?`,
            'It goes to the trash and can be restored from there.', 'Delete', async () => {
              await api('session.delete', { id });
              toast('Moved to the trash.'); renderAgenda();
            });
        }
      } catch (err) { toast(err.message, 'err'); }
    };

    if (can('agenda.reorder')) enableDrag(list, async (order) => {
      try { await api('session.reorder', { order }); flashSaved(); }
      catch (err) { toast(err.message, 'err'); renderAgenda(); }
    });
  }

  function enableDrag(list, onDrop) {
    let dragged = null;
    list.addEventListener('dragstart', e => {
      dragged = e.target.closest('.session-row');
      dragged?.classList.add('dragging');
    });
    list.addEventListener('dragend', () => {
      dragged?.classList.remove('dragging');
      $$('.session-row', list).forEach(r => r.classList.remove('drag-over'));
      onDrop($$('.session-row', list).map(r => +r.dataset.id));
      dragged = null;
    });
    list.addEventListener('dragover', e => {
      e.preventDefault();
      const over = e.target.closest('.session-row');
      if (!over || over === dragged) return;
      $$('.session-row', list).forEach(r => r.classList.remove('drag-over'));
      over.classList.add('drag-over');
      const rect = over.getBoundingClientRect();
      const after = e.clientY > rect.top + rect.height / 2;
      list.insertBefore(dragged, after ? over.nextSibling : over);
    });
  }

  function sessionForm(s) {
    const assigned = s?.speakers || [];
    openModal(s ? 'Edit session' : 'New session', `
      <div class="grid gap-4">
        <div><label for="f-title">Title</label>
          <input id="f-title" value="${esc(s?.title || '')}"></div>
        <div><label for="f-sub">Subtitle</label>
          <input id="f-sub" value="${esc(s?.subtitle || '')}"></div>

        <div class="grid grid-cols-2 gap-3">
          <div><label for="f-day">Day</label><select id="f-day">
            ${A.days.map(d => `<option value="${d.id}" ${+d.id === +(s?.day_id || A.days[0].id) ? 'selected' : ''}>${esc(d.label)}</option>`).join('')}
          </select></div>
          <div><label for="f-hall">Hall</label><select id="f-hall">
            <option value="">No hall (venue-wide)</option>
            ${A.halls.map(h => `<option value="${h.id}" ${+h.id === +(s?.hall_id || 0) ? 'selected' : ''}>${esc(h.name)}</option>`).join('')}
          </select></div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div><label for="f-start">Starts</label>
            <input id="f-start" type="time" value="${hhmm(s?.start_time) || '10:00'}"></div>
          <div><label for="f-end">Ends</label>
            <input id="f-end" type="time" value="${hhmm(s?.end_time) || ''}"></div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div><label for="f-type">Type</label><select id="f-type">
            ${TYPES.map(t => `<option value="${t}" ${t === (s?.session_type || 'panel') ? 'selected' : ''}>${t}</option>`).join('')}
          </select></div>
          <div><label for="f-track">Track</label><select id="f-track">
            <option value="">None</option>
            ${A.tracks.map(t => `<option value="${t.id}" ${+t.id === +(s?.track_id || 0) ? 'selected' : ''}>${esc(t.name)}</option>`).join('')}
          </select></div>
        </div>

        <div><label for="f-desc">Description</label>
          <textarea id="f-desc" rows="4">${esc(s?.description || '')}</textarea></div>

        <div>
          <label>Speakers</label>
          <div id="assigned" class="grid gap-1.5 mb-2"></div>
          <select id="f-addsp"><option value="">Add a speaker…</option>
            ${SPEAKERS.map(p => `<option value="${p.id}">${esc(p.full_name)}${p.organisation ? ' — ' + esc(p.organisation) : ''}</option>`).join('')}
          </select>
        </div>

        <div class="flex items-center gap-2">
          <input type="checkbox" id="f-pub" class="w-auto" ${s?.status === 'published' ? 'checked' : ''}
            ${can('agenda.publish') ? '' : 'disabled'}>
          <label for="f-pub" class="mb-0">Show on the public agenda</label>
        </div>

        <div class="flex gap-2.5 justify-end pt-1">
          <button class="btn btn-ghost" data-close>Cancel</button>
          <button class="btn btn-primary" id="f-save">${s ? 'Save changes' : 'Create session'}</button>
        </div>
      </div>`, (root) => {

      let picks = assigned.map(a => ({ speaker_id: +a.speaker_id, speaker_role: a.speaker_role, name: a.full_name }));

      const drawPicks = () => {
        const box = $('#assigned', root);
        box.innerHTML = picks.length ? picks.map((p, i) => `
          <div class="flex items-center gap-2 bg-mist border border-rule rounded-lg px-3 py-2">
            <span class="text-[13px] truncate flex-1">${esc(p.name)}</span>
            <select data-i="${i}" class="w-auto text-[12px] py-1">
              ${ROLES.map(r => `<option value="${r}" ${r === p.speaker_role ? 'selected' : ''}>${r.replace('_', ' ')}</option>`).join('')}
            </select>
            <button class="text-soft hover:text-ink px-1" data-rm="${i}" aria-label="Remove">&times;</button>
          </div>`).join('')
          : `<p class="text-[12.5px] text-soft">Nobody assigned yet.</p>`;

        $$('[data-rm]', box).forEach(b => b.addEventListener('click', () => {
          picks.splice(+b.dataset.rm, 1); drawPicks();
        }));
        $$('select[data-i]', box).forEach(sel => sel.addEventListener('change', () => {
          picks[+sel.dataset.i].speaker_role = sel.value;
        }));
      };
      drawPicks();

      $('#f-addsp', root).addEventListener('change', (e) => {
        const id = +e.target.value;
        if (!id) return;
        if (!picks.some(p => p.speaker_id === id)) {
          const sp = SPEAKERS.find(x => +x.id === id);
          picks.push({ speaker_id: id, speaker_role: 'panelist', name: sp.full_name });
          drawPicks();
        }
        e.target.value = '';
      });

      $('#f-save', root).addEventListener('click', async () => {
        const btn = $('#f-save', root);
        btn.disabled = true;
        try {
          await api('session.save', {
            id: s?.id || 0,
            title: $('#f-title', root).value.trim(),
            subtitle: $('#f-sub', root).value.trim(),
            day_id: $('#f-day', root).value,
            hall_id: $('#f-hall', root).value,
            track_id: $('#f-track', root).value,
            start_time: $('#f-start', root).value,
            end_time: $('#f-end', root).value,
            session_type: $('#f-type', root).value,
            description: $('#f-desc', root).value,
            status: $('#f-pub', root).checked ? 'published' : 'draft',
            speakers: picks.map(p => ({ speaker_id: p.speaker_id, speaker_role: p.speaker_role })),
          });
          closeModal(); flashSaved(); renderAgenda();
        } catch (err) { toast(err.message, 'err'); btn.disabled = false; }
      });
    });
  }

  // -------------------------------------------------------------- speakers
  async function renderSpeakers() {
    const host = $('#panel-speakers');
    host.innerHTML = `<p class="text-soft text-sm">Loading…</p>`;
    await loadSpeakers();

    host.innerHTML = `
      <div class="flex items-center gap-2.5 mb-5 flex-wrap">
        <input id="sp-search" placeholder="Filter by name or organisation" style="max-width:320px">
        <span class="text-[12.5px] text-soft" id="sp-count"></span>
        ${can('speakers.create') ? `<button class="btn btn-primary ml-auto" id="new-speaker">Add speaker</button>` : ''}
      </div>
      <div id="sp-grid" class="grid gap-2" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr))"></div>`;

    $('#new-speaker', host)?.addEventListener('click', () => speakerForm(null));
    $('#sp-search', host).addEventListener('input', e => drawSpeakers(e.target.value.toLowerCase()));
    drawSpeakers('');
  }

  function drawSpeakers(q) {
    const rows = SPEAKERS.filter(s =>
      !q || [s.full_name, s.organisation, s.designation].filter(Boolean).join(' ').toLowerCase().includes(q));
    $('#sp-count').textContent = `${rows.length} of ${SPEAKERS.length}`;

    $('#sp-grid').innerHTML = rows.map(s => `
      <div class="bg-white border border-rule rounded-xl p-3.5 flex gap-3 items-start">
        ${s.photo_path
          ? `<img src="${esc(s.photo_path)}" alt="" class="w-11 h-11 rounded-full object-cover shrink-0">`
          : `<div class="w-11 h-11 rounded-full shrink-0 grid place-items-center font-mono text-[12px] font-bold"
               style="background:#f6f7f9;color:#6b7280">${esc(mono(s.full_name))}</div>`}
        <div class="min-w-0 flex-1">
          <div class="text-[13.5px] font-semibold truncate">${esc(s.full_name)}</div>
          <div class="text-[11.5px] text-soft line-clamp-2">${esc([s.designation, s.organisation].filter(Boolean).join(', ')) || 'No organisation yet'}</div>
          <div class="flex items-center gap-1.5 mt-2 flex-wrap">
            <span class="font-mono text-[9.5px] px-1.5 py-0.5 rounded"
                  style="${s.status === 'published' ? 'background:#e7f6f4;color:#0f7d76'
                                                    : 'background:#fdf3e3;color:#a5640a'}">${s.status}</span>
            <span class="font-mono text-[9.5px] text-soft">${s.session_count} session${+s.session_count === 1 ? '' : 's'}</span>
            ${!s.photo_path ? `<span class="font-mono text-[9.5px] text-soft">no photo</span>` : ''}
          </div>
          <div class="flex gap-1.5 mt-2.5">
            ${can('speakers.edit') ? `<button class="btn btn-ghost px-2 py-1 text-[11.5px]" data-sp-edit="${s.id}">Edit</button>` : ''}
            ${can('speakers.upload') ? `<button class="btn btn-ghost px-2 py-1 text-[11.5px]" data-sp-photo="${s.id}">Photo</button>` : ''}
            ${can('speakers.delete') ? `<button class="btn btn-danger px-2 py-1 text-[11.5px]" data-sp-del="${s.id}">Delete</button>` : ''}
          </div>
        </div>
      </div>`).join('');

    $('#sp-grid').onclick = (e) => {
      const ed = e.target.closest('[data-sp-edit]');
      const ph = e.target.closest('[data-sp-photo]');
      const dl = e.target.closest('[data-sp-del]');
      if (ed) return speakerForm(SPEAKERS.find(s => +s.id === +ed.dataset.spEdit));
      if (ph) return photoUpload(SPEAKERS.find(s => +s.id === +ph.dataset.spPhoto));
      if (dl) {
        const s = SPEAKERS.find(x => +x.id === +dl.dataset.spDel);
        return confirmAction(`Delete ${s.full_name}?`,
          'They are removed from every session and moved to the trash.', 'Delete', async () => {
            try { await api('speaker.delete', { id: s.id }); toast('Moved to the trash.'); renderSpeakers(); }
            catch (err) { toast(err.message, 'err'); }
          });
      }
    };
  }

  const mono = (name) => {
    const p = String(name).replace(/\(.*?\)/g, '').trim().split(/\s+/).filter(Boolean);
    return ((p[0]?.[0] || '') + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase();
  };

  function speakerForm(s) {
    openModal(s ? 'Edit speaker' : 'Add speaker', `
      <div class="grid gap-4">
        <div class="grid grid-cols-[92px_1fr] gap-3">
          <div><label for="f-hon">Title</label>
            <input id="f-hon" placeholder="Dr." value="${esc(s?.honorific || '')}"></div>
          <div><label for="f-name">Full name</label>
            <input id="f-name" value="${esc(s?.full_name || '')}"></div>
        </div>
        <div><label for="f-desig">Designation</label>
          <input id="f-desig" value="${esc(s?.designation || '')}"></div>
        <div><label for="f-org">Organisation</label>
          <input id="f-org" value="${esc(s?.organisation || '')}"></div>
        <div><label for="f-bio">Bio</label>
          <textarea id="f-bio" rows="5">${esc(s?.bio || '')}</textarea></div>
        <div><label for="f-li">LinkedIn URL</label>
          <input id="f-li" type="url" placeholder="https://" value="${esc(s?.linkedin_url || '')}"></div>
        <div class="flex items-center gap-2">
          <input type="checkbox" id="f-spub" class="w-auto" ${(!s || s.status === 'published') ? 'checked' : ''}>
          <label for="f-spub" class="mb-0">Show on the public agenda</label>
        </div>
        <div class="flex gap-2.5 justify-end pt-1">
          <button class="btn btn-ghost" data-close>Cancel</button>
          <button class="btn btn-primary" id="f-ssave">${s ? 'Save changes' : 'Add speaker'}</button>
        </div>
      </div>`, (root) => {
      $('#f-ssave', root).addEventListener('click', async () => {
        const btn = $('#f-ssave', root); btn.disabled = true;
        try {
          await api('speaker.save', {
            id: s?.id || 0,
            honorific: $('#f-hon', root).value.trim(),
            full_name: $('#f-name', root).value.trim(),
            designation: $('#f-desig', root).value.trim(),
            organisation: $('#f-org', root).value.trim(),
            bio: $('#f-bio', root).value.trim(),
            linkedin_url: $('#f-li', root).value.trim(),
            status: $('#f-spub', root).checked ? 'published' : 'draft',
          });
          closeModal(); flashSaved(); renderSpeakers();
        } catch (err) { toast(err.message, 'err'); btn.disabled = false; }
      });
    });
  }

  function photoUpload(s) {
    openModal(`Photo — ${s.full_name}`, `
      <div class="grid gap-4">
        <div class="grid place-items-center gap-3 py-3">
          <div id="preview" class="w-28 h-28 rounded-full overflow-hidden grid place-items-center
                                    border border-rule" style="background:#1B2740">
            ${s.photo_path ? `<img src="${esc(s.photo_path)}" class="w-full h-full object-cover" alt="">`
                           : `<span class="font-mono text-2xl text-soft">${esc(mono(s.full_name))}</span>`}
          </div>
          <p class="text-[12.5px] text-soft text-center">
            JPG, PNG or WebP, up to 3 MB.<br>Square crops work best — it is centre-cropped automatically.
          </p>
        </div>
        <input type="file" id="f-file" accept="image/jpeg,image/png,image/webp">
        <div class="flex gap-2.5 justify-end">
          <button class="btn btn-ghost" data-close>Cancel</button>
          <button class="btn btn-primary" id="f-up" disabled>Upload photo</button>
        </div>
      </div>`, (root) => {
      const file = $('#f-file', root), btn = $('#f-up', root);
      file.addEventListener('change', () => {
        btn.disabled = !file.files.length;
        if (file.files[0]) {
          const url = URL.createObjectURL(file.files[0]);
          $('#preview', root).innerHTML = `<img src="${url}" class="w-full h-full object-cover" alt="">`;
        }
      });
      btn.addEventListener('click', async () => {
        btn.disabled = true; btn.textContent = 'Uploading…';
        const fd = new FormData();
        fd.append('id', s.id);
        fd.append('photo', file.files[0]);
        try {
          await api('speaker.photo', fd);
          closeModal(); toast('Photo updated.'); renderSpeakers();
        } catch (err) { toast(err.message, 'err'); btn.disabled = false; btn.textContent = 'Upload photo'; }
      });
    });
  }

  // -------------------------------------------------------- halls / tracks
  async function renderTaxonomy(type) {
    const host = $(type === 'hall' ? '#panel-venue' : '#panel-tracks');
    const rows = type === 'hall' ? A.halls : A.tracks;
    const noun = type === 'hall' ? 'hall' : 'track';

    host.innerHTML = `
      <div class="flex mb-5">
        <p class="text-[13px] text-soft max-w-[520px]">
          ${type === 'hall'
            ? 'Halls drive the colour coding and the wayfinding note attendees see on a session.'
            : 'Tracks become the filter pills on the public agenda.'}
        </p>
        <button class="btn btn-primary ml-auto" id="new-tax">Add ${noun}</button>
      </div>
      <div class="grid gap-2" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
        ${rows.map(r => `
          <div class="bg-white border border-rule rounded-xl p-4">
            <div class="flex items-center gap-2.5">
              <span class="w-3 h-3 rounded" style="background:${esc(r.color_hex || '#1B2740')}"></span>
              <span class="text-[14px] font-semibold">${esc(r.name)}</span>
            </div>
            <div class="text-[12px] text-soft mt-1.5">
              ${esc(r.venue || r.description || '')}${r.capacity ? ` · seats ${r.capacity}` : ''}
            </div>
            ${r.map_note ? `<div class="text-[11.5px] font-mono text-soft mt-1">${esc(r.map_note)}</div>` : ''}
            <div class="flex gap-1.5 mt-3">
              <button class="btn btn-ghost px-2.5 py-1 text-[12px]" data-tax-edit="${r.id}">Edit</button>
              <button class="btn btn-danger px-2.5 py-1 text-[12px]" data-tax-del="${r.id}">Delete</button>
            </div>
          </div>`).join('') || `<p class="text-soft text-sm">Nothing here yet.</p>`}
      </div>`;

    $('#new-tax', host).addEventListener('click', () => taxForm(type, null));
    host.onclick = (e) => {
      const ed = e.target.closest('[data-tax-edit]');
      const dl = e.target.closest('[data-tax-del]');
      if (ed) return taxForm(type, rows.find(r => +r.id === +ed.dataset.taxEdit));
      if (dl) {
        const r = rows.find(x => +x.id === +dl.dataset.taxDel);
        return confirmAction(`Delete “${r.name}”?`,
          `Sessions keep running but lose their ${noun}.`, 'Delete', async () => {
            try { await api(type + '.delete', { id: r.id }); location.reload(); }
            catch (err) { toast(err.message, 'err'); }
          });
      }
    };
  }

  function taxForm(type, r) {
    const isHall = type === 'hall';
    openModal((r ? 'Edit ' : 'Add ') + (isHall ? 'hall' : 'track'), `
      <div class="grid gap-4">
        <div><label for="t-name">Name</label><input id="t-name" value="${esc(r?.name || '')}"></div>
        ${isHall ? `
          <div><label for="t-venue">Venue</label><input id="t-venue" value="${esc(r?.venue || '')}"></div>
          <div class="grid grid-cols-2 gap-3">
            <div><label for="t-floor">Floor / wing</label><input id="t-floor" value="${esc(r?.floor_info || '')}"></div>
            <div><label for="t-cap">Capacity</label><input id="t-cap" type="number" value="${esc(r?.capacity || '')}"></div>
          </div>
          <div><label for="t-map">Wayfinding note</label>
            <input id="t-map" placeholder="Ground floor, past registration" value="${esc(r?.map_note || '')}"></div>
        ` : `
          <div><label for="t-desc">Description</label><input id="t-desc" value="${esc(r?.description || '')}"></div>
        `}
        <div class="grid grid-cols-2 gap-3">
          <div><label for="t-color">Colour</label>
            <input id="t-color" type="color" value="${esc(r?.color_hex || '#0f9d94')}" style="height:40px;padding:4px"></div>
          <div><label for="t-sort">Order</label>
            <input id="t-sort" type="number" value="${esc(r?.sort_order ?? 0)}"></div>
        </div>
        <div class="flex gap-2.5 justify-end pt-1">
          <button class="btn btn-ghost" data-close>Cancel</button>
          <button class="btn btn-primary" id="t-save">Save</button>
        </div>
      </div>`, (root) => {
      $('#t-save', root).addEventListener('click', async () => {
        const payload = {
          id: r?.id || 0,
          name: $('#t-name', root).value.trim(),
          color_hex: $('#t-color', root).value,
          sort_order: +$('#t-sort', root).value || 0,
        };
        if (isHall) Object.assign(payload, {
          venue: $('#t-venue', root).value.trim(),
          floor_info: $('#t-floor', root).value.trim(),
          capacity: $('#t-cap', root).value,
          map_note: $('#t-map', root).value.trim(),
        });
        else payload.description = $('#t-desc', root).value.trim();

        try { await api(type + '.save', payload); location.reload(); }
        catch (err) { toast(err.message, 'err'); }
      });
    });
  }


  
  
  
  // --------------------------------------------------------------- history
  async function renderHistory() {
    const host = $('#panel-history');
    host.innerHTML = `<p class="text-soft text-sm">Loading…</p>`;
    const d = await api('history.list', null, { qs: 'limit=100' });

    host.innerHTML = `
      <p class="text-[13px] text-soft mb-5">Every change, who made it and what it replaced. Nothing is removed from this log.</p>
      <div class="bg-white border border-rule rounded-xl divide-y divide-rule">
        ${d.entries.map(en => {
          const changes = en.new_values && typeof en.new_values === 'object'
            ? Object.keys(en.new_values).filter(k => !k.startsWith('_')).slice(0, 4) : [];
          return `
          <div class="px-4 py-3">
            <div class="flex items-center gap-2.5 flex-wrap">
              <span class="font-mono text-[10px] px-2 py-0.5 rounded"
                    style="background:#f6f7f9;color:#6b7280">${esc(en.action)}</span>
              <span class="font-mono text-[10px] text-soft">${esc(en.entity_type)}</span>
              <span class="text-[13.5px] font-semibold truncate">${esc(en.entity_label || '—')}</span>
              <span class="text-[11.5px] text-soft ml-auto shrink-0">
                ${esc(en.user_name || 'system')} · ${timeAgo(en.created_at)}${en.ip ? ' · ' + esc(en.ip) : ''}</span>
            </div>
            ${changes.length ? `<div class="mt-2 grid gap-1">
              ${changes.map(k => `
                <div class="font-mono text-[11.5px] flex gap-2 items-baseline flex-wrap">
                  <span class="text-soft">${esc(k)}</span>
                  <span style="color:#b3392a">${esc(trunc(en.old_values?.[k]))}</span>
                  <span class="text-soft">→</span>
                  <span style="color:#0f7d76">${esc(trunc(en.new_values?.[k]))}</span>
                </div>`).join('')}
            </div>` : ''}
          </div>`;
        }).join('') || `<p class="px-4 py-6 text-soft text-sm">The log is empty.</p>`}
      </div>`;
  }

  const trunc = (v) => {
    if (v === null || v === undefined || v === '') return '(empty)';
    const s = String(v);
    return s.length > 44 ? s.slice(0, 44) + '…' : s;
  };

  async function versionList(type, id, label) {
    const { versions } = await api('version.list', null,
      { qs: `entity_type=${type}&entity_id=${id}` });
    openModal(`History — ${label}`, `
      <p class="text-[13px] text-soft mb-4">A snapshot is taken before every save. Restoring one also snapshots what is there now, so you can undo the undo.</p>
      <div class="bg-mist border border-rule rounded-xl divide-y divide-rule">
        ${versions.length ? versions.map(v => `
          <div class="px-4 py-3 flex items-center gap-3">
            <span class="font-mono text-[12px] font-bold" style="color:#0f9d94">v${v.version_no}</span>
            <div class="min-w-0 flex-1">
              <div class="text-[12.5px] truncate">${esc(v.note || 'Saved change')}</div>
              <div class="text-[11px] text-soft">${esc(v.created_by_name || 'system')} · ${timeAgo(v.created_at)}</div>
            </div>
            ${can('history.restore') ? `<button class="btn btn-ghost px-2.5 py-1 text-[12px]" data-restore="${v.id}">Restore</button>` : ''}
          </div>`).join('')
        : `<p class="px-4 py-6 text-soft text-sm">No earlier versions yet.</p>`}
      </div>`, (root) => {
      $$('[data-restore]', root).forEach(b => b.addEventListener('click', () => {
        confirmAction('Restore this version?',
          'The current content is snapshotted first, so this is reversible.', 'Restore', async () => {
            try {
              await api('version.restore', { version_id: +b.dataset.restore });
              toast('Version restored.');
              renderAgenda();
            } catch (err) { toast(err.message, 'err'); }
          });
      }));
    });
  }

  // ----------------------------------------------------------------- trash
  async function renderTrash() {
    const host = $('#panel-trash');
    host.innerHTML = `<p class="text-soft text-sm">Loading…</p>`;
    const { items } = await api('trash.list');

    host.innerHTML = `
      <p class="text-[13px] text-soft mb-5">
        Deleted items wait here for ${A.settings.trash_retention_days} days before they can be cleared for good.
      </p>
      <div class="bg-white border border-rule rounded-xl divide-y divide-rule">
        ${items.length ? items.map(i => `
          <div class="px-4 py-3.5 flex items-center gap-3 flex-wrap">
            <span class="font-mono text-[10px] px-2 py-0.5 rounded shrink-0"
                  style="background:#f6f7f9;color:#6b7280">${esc(i.entity_type)}</span>
            <div class="min-w-0 flex-1">
              <div class="text-[13.5px] font-semibold truncate">${esc(i.entity_label || '—')}</div>
              <div class="text-[11.5px] text-soft">${esc(i.summary || '')}</div>
            </div>
            <span class="text-[11.5px] text-soft shrink-0">${esc(i.deleted_by_name || '')} · ${timeAgo(i.deleted_at)}</span>
            <div class="flex gap-1.5 shrink-0">
              ${can('trash.restore') ? `<button class="btn btn-ghost px-2.5 py-1 text-[12px]" data-t-restore="${i.id}">Restore</button>` : ''}
              ${can('trash.purge') ? `<button class="btn btn-danger px-2.5 py-1 text-[12px]" data-t-purge="${i.id}">Delete for good</button>` : ''}
            </div>
          </div>`).join('')
        : `<p class="px-4 py-8 text-soft text-sm">The trash is empty.</p>`}
      </div>`;

    host.onclick = (e) => {
      const r = e.target.closest('[data-t-restore]');
      const p = e.target.closest('[data-t-purge]');
      if (r) return (async () => {
        try { await api('trash.restore', { id: +r.dataset.tRestore }); toast('Restored.'); renderTrash(); }
        catch (err) { toast(err.message, 'err'); }
      })();
      if (p) return confirmAction('Delete permanently?',
        'This one cannot be undone — the row and its trash entry are removed.', 'Delete for good', async () => {
          try { await api('trash.purge', { id: +p.dataset.tPurge }); toast('Deleted.'); renderTrash(); }
          catch (err) { toast(err.message, 'err'); }
        });
    };
  }

  // -------------------------------------------------------------- settings
  function renderSettings() {
    const s = A.settings;
    $('#panel-settings').innerHTML = `
      <div class="max-w-[560px] grid gap-4">
        <div><label for="s-name">Event name</label><input id="s-name" value="${esc(s.event_name)}"></div>
        <div><label for="s-venue">Venue</label><input id="s-venue" value="${esc(s.event_venue)}"></div>
        <div><label for="s-ret">Days to keep items in the trash</label>
          <input id="s-ret" type="number" min="1" max="365" value="${esc(s.trash_retention_days)}"></div>

        <div class="border border-rule rounded-xl p-4 mt-1">
          <div class="flex items-start gap-3">
            <input type="checkbox" id="s-btn" class="w-auto mt-1" ${s.show_admin_button ? 'checked' : ''}>
            <div>
              <label for="s-btn" class="mb-0.5">Show the admin dot on the agenda page</label>
              <p class="text-[12px] text-soft">
                Switch this off before the agenda goes public. The admin page still works —
                the shortcut just disappears from the public page.
              </p>
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-1">
          <button class="btn btn-primary" id="s-save">Save settings</button>
        </div>
      </div>`;

    $('#s-save').addEventListener('click', async () => {
      try {
        await api('settings.save', {
          event_name: $('#s-name').value.trim(),
          event_venue: $('#s-venue').value.trim(),
          trash_retention_days: +$('#s-ret').value || 30,
          show_admin_button: $('#s-btn').checked ? 1 : 0,
        });
        flashSaved(); toast('Settings saved.');
      } catch (err) { toast(err.message, 'err'); }
    });
  }

  // ------------------------------------------------------------------ boot
  window.addEventListener('hashchange', () => go(location.hash.slice(1)));
  go(location.hash.slice(1) || 'dashboard');
})();
