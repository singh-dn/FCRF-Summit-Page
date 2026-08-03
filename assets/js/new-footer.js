   // 1. Dynamic Footer Spacing & Guaranteed Visibility Fix (V2)
        function setupFcrfFooterRevealV2() {
            const footer = document.getElementById('fcrf-v2-reveal-footer');
            const main = document.getElementById('fcrf-v2-main-content');
            
            if (!footer || !main) return;

            function updateGap() {
                main.style.marginBottom = `${footer.offsetHeight}px`;
            }
            window.addEventListener('resize', updateGap);
            updateGap();
            
            const trigger = document.createElement('div');
            trigger.style.position = 'absolute';
            trigger.style.bottom = '10px'; 
            trigger.style.left = '0';
            trigger.style.width = '100%';
            trigger.style.height = '1px';
            trigger.style.pointerEvents = 'none';
            main.appendChild(trigger);

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        footer.style.visibility = 'visible';
                        footer.classList.add('fcrf-v2-is-visible');
                    } else {
                        if (entry.boundingClientRect.top > 0) {
                            footer.style.visibility = 'hidden';
                            footer.classList.remove('fcrf-v2-is-visible');
                        }
                    }
                });
            }, { rootMargin: '50px' });

            observer.observe(trigger);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupFcrfFooterRevealV2);
        } else {
            setupFcrfFooterRevealV2();
        }

        // 2. Arrow Tracking Logic (V2)
        const arrow = document.getElementById('fcrf-v2-tracking-arrow');
        if (arrow) {
            let currentAngle = 0;
            let targetAngle = 0;
            let mouseX = window.innerWidth / 2;
            let mouseY = window.innerHeight / 2;

            window.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;
            });

            function animateFcrfArrowV2() {
                const rect = arrow.getBoundingClientRect();
                const arrowCenterX = rect.left + rect.width / 2;
                const arrowCenterY = rect.top + rect.height / 2;

                const dx = mouseX - arrowCenterX;
                const dy = mouseY - arrowCenterY;

                targetAngle = Math.atan2(dy, dx) * (180 / Math.PI);

                let deltaAngle = targetAngle - currentAngle;
                deltaAngle = ((deltaAngle + 180) % 360 + 360) % 360 - 180;

                currentAngle += deltaAngle * 0.08;

                arrow.style.transform = `rotate(${currentAngle}deg)`;

                requestAnimationFrame(animateFcrfArrowV2);
            }
            
            animateFcrfArrowV2();
        }





               // IIFE to avoid polluting global namespace
        (function initSpringActionMenu() {
            const menuRoot = document.getElementById('sam-root');
            const menuTrigger = document.getElementById('sam-trigger');
            const menuItems = document.querySelectorAll('.sam-item');
            
            let isMenuOpen = false;

            function toggleMenu() {
                isMenuOpen = !isMenuOpen;
                
                if (isMenuOpen) {
                    menuRoot.classList.add('sam-active');
                    // Stagger in
                    menuItems.forEach((item, index) => {
                        item.style.transitionDelay = `${index * 0.06}s`;
                    });
                } else {
                    menuRoot.classList.remove('sam-active');
                    // Reversed Stagger out
                    const total = menuItems.length;
                    menuItems.forEach((item, index) => {
                        item.style.transitionDelay = `${(total - 1 - index) * 0.04}s`;
                    });
                }
            }

            // Bind click to main button
            menuTrigger.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleMenu();
            });

            // Bind click to individual items using data-action attribute
            menuItems.forEach(item => {
                item.addEventListener('click', (e) => {
                    const action = e.currentTarget.getAttribute('data-action');
                    
                    // -- YOUR CUSTOM LOGIC GOES HERE --
                    console.log(`Action executed: ${action}`);
                    // ---------------------------------
                    
                    toggleMenu(); // Auto close
                });
            });

            // Close when clicking outside
            document.addEventListener('click', (e) => {
                if (isMenuOpen && !menuRoot.contains(e.target)) {
                    toggleMenu();
                }
            });

            // Accessibility: Escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && isMenuOpen) toggleMenu();
            });

            // --- Scroll to Top Logic ---
            const scrollWrapper = document.getElementById('sam-scroll-wrapper');
            const scrollUpBtn = document.getElementById('sam-scroll-up');
            const progressCircle = document.getElementById('sam-progress-circle');
            
            // Setup Progress Ring
            const circumference = 2 * Math.PI * 28; // r=28
            progressCircle.style.strokeDasharray = `${circumference} ${circumference}`;
            progressCircle.style.strokeDashoffset = circumference;
            
            // Scroll event listener for visibility and progress
            window.addEventListener('scroll', () => {
                // Toggle Visibility
                if (window.scrollY > 200) {
                    scrollWrapper.classList.add('sam-visible');
                } else {
                    scrollWrapper.classList.remove('sam-visible');
                }

                // Update Progress Ring
                const scrollTop = window.scrollY;
                const docHeight = Math.max(
                    document.body.scrollHeight, document.documentElement.scrollHeight,
                    document.body.offsetHeight, document.documentElement.offsetHeight,
                    document.body.clientHeight, document.documentElement.clientHeight
                ) - window.innerHeight;

                if (docHeight > 0) {
                    const scrollPercent = scrollTop / docHeight;
                    const scrollOffset = Math.max(0, Math.min(1, scrollPercent));
                    progressCircle.style.strokeDashoffset = circumference - (scrollOffset * circumference);
                }
            });

            // Smooth scroll to top on click
            scrollUpBtn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                }); 
            });

        })();



/* =========================================================================
   FCRF SUMMIT — ESTEEMED SPEAKERS
   Data-driven, two-row marquee.
   ========================================================================= */

(() => {
  'use strict';

  const IMG_BASE = "assets/img/speakers'26/";

  /* ==================================================================
     SPEAKERS
     ================================================================== */
  const SPEAKERS = [
    {"row": 1, "name": "Air Marshal Jeetendra MIshra", "role": "SYSM, AVSM, VSM (R)", "org": "Former Air Officer Commanding-in-Chief, WAC, IAF", "img": "speaker'0.webp"},
    {"row": 1, "name": "Daljit Singh Chaudhary", "role": "Former DG", "org": "BSF", "img": "speaker'1.webp"},
    {"row": 1, "name": "Rajiv Jain", "role": "Former Director", "org": "Intelligence Bureau (IB)", "img": "speaker'2.webp"},
    {"row": 1, "name": "Lt Gen (Dr) Rajesh Pant", "role": "Former National Cyber Security Coordinator & Chairman", "org": "Cyber Security Association of India", "img": "speaker'3.webp"},
    {"row": 1, "name": "Dr. Gulshan Rai", "role": "Former National Cyber Security Coordinator & Former DG", "org": "Cert-In", "img": "speaker'4.webp"},
    {"row": 1, "name": "Dr. Sanjay Bahl", "role": "Director General", "org": "Cert-In", "img": "speaker'5.webp"},
    {"row": 1, "name": "Maj Gen (Dr) Bipin Bakshi", "role": "Distinguished Fellow", "org": "Centre for Land Warfare Studies (CLAWs)", "img": "speaker'6.webp"},
    {"row": 1, "name": "Mini Rani Sharma", "role": "Head SeMT", "org": "MeiTY, GoI", "img": "Picture1.webp"},
    {"row": 1, "name": "Lt. Col. Nishant Singh (Retd.)", "role": "Chief Operating Officer", "org": "Gramax Cybertech Limited", "img": "picture13.webp"},
    {"row": 1, "name": "Alok Vijayant", "role": "Former Director, Cyber Security Operations, Govt of India", "org": "SciRoIT Technologies", "img": "Picture11.webp"},
    {"row": 1, "name": "Deepak Vatsa", "role": "Sr VP - Fraud Control, Investigations, Legal, Risk Mitigation", "org": "HDFC ERGO", "img": "deepak-vatsa.webp"},
    {"row": 2, "name": "Somen Das", "role": "Associate Director", "org": "Accenture Cybersecurity", "img": "Picture8.webp"},
    {"row": 2, "name": "Aman Bandvi", "role": "Founder Director", "org": "Bharat Responsible AI Forum", "img": "Picture2.webp"},
    {"row": 2, "name": "Paakhhi Garg", "role": "Director", "org": "World Cyber Security Forum", "img": "Picture3.webp"},
    {"row": 2, "name": "Megha Khetarpal", "role": "Senior Director, Fraud & Identity Global Products", "org": "TransUnion", "img": "Picture4.webp"},
    {"row": 2, "name": "Dr Kulbhushan Upadhyay", "role": "Assistant General Manager", "org": "TCIL", "img": "Picture5.webp"},
    {"row": 2, "name": "Dr. Ramkumar Iyer", "role": "CIO & AI Security Governance Director", "org": "Reliscale Consulting Pvt Ltd", "img": "Picture6.webp"},
    {"row": 2, "name": "Bharat Jeswani", "role": "FCA, CFE, CFCS, CAMS — Founder", "org": "Bharat Jeswani Consulting, Global AML Consultants", "img": "Picture10.webp"},
    {"row": 2, "name": "Garima Goswamy", "role": "Principal Risk Advisor", "org": "InQuest Global", "img": "Picture12.webp"},
    {"row": 2, "name": "Suditi Tandon", "role": "Senior Officer, Global Data Privacy Office Specialist", "org": "Hella India Automotive Pvt Ltd", "img": "Picture9.webp"},
    {"row": 1, "name": "Rakesh Maheshwari", "role": "Advisor, Cyber Laws & Tech Policy", "org": "Former Sr. Director & GC, MeitY", "img": "rakesh-maheshwari.webp"},
    {"row": 1, "name": "AVM (Dr) Devesh Vatsa (Retd) VSM", "role": "Advisor", "org": "DSCI", "img": "devesh-vatsa.webp"},
    {"row": 1, "name": "Ashutosh Bahuguna", "role": "Scientist & Lead - Cybersecurity Assurance", "org": "CERT-In", "img": "ashutosh-bahuguna.webp"},
    {"row": 1, "name": "Dr. Rakshit Tandon", "role": "Cybersecurity Expert & Director - Training", "org": "Future Crime Research Foundation (FCRF)", "img": "rakshit-tandon.webp"},
    {"row": 1, "name": "Bharat Panchal", "role": "Chief Risk & Regulatory Officer", "org": "Discover (Capital One)", "img": "bharat-panchal.webp"},
    {"row": 1, "name": "Gyan Barah", "role": "Senior Advisor", "org": "Jio Financial Services", "img": "gyan-barah.webp"},
    {"row": 2, "name": "Naveenathan M", "role": "Founder & Chairman", "org": "CXO Cywayz", "img": "naveenathan-m.webp"},
    {"row": 2, "name": "Dinesh O Bareja", "role": "Cybersecurity Consultant", "org": "Independent Cybersecurity Consultant", "img": "dinesh-bareja.webp"},
    {"row": 2, "name": "Balaji Kapsikar", "role": "Director, Cyber Security & Cyber Risk - DPO", "org": "CYNKEX Cybertech", "img": "balaji-kapsikar.webp"},
    {"row": 2, "name": "Tanmayee Tilekar", "role": "Cybersecurity Expert", "org": "Cybersecurity Research & Consulting", "img": "tanmayee-tilekar.webp"},
    {"row": 2, "name": "Gaurav Ranade", "role": "CTO", "org": "Technocentric Advisory", "img": "gaurav-ranade.webp"},
    {"row": 2, "name": "CA (Dr.) Durgesh Pandey", "role": "Managing Partner", "org": "DKMS & Associates", "img": "durgesh-pandey.webp"},
    {"row": 2, "name": "Piyush Kaushik", "role": "Product Manager, Forensics", "org": "Exterro", "img": "piyush-kaushik.webp"},
    {"row": 2, "name": "Dr. Malvika Mehta", "role": "Founder", "org": "BLK Coral Intelligence", "img": "malvika-mehta.webp"},
    {"row": 2, "name": "Himanshu Patel", "role": "Senior Manager - Cyber Defence, Investigation & DFIR", "org": "Protiviti India", "img": "himanshu-patel.webp"},
    {"row": 2, "name": "Vijayant Gaur", "role": "Cybersecurity Expert", "org": "Cybersecurity Consultant", "img": "vijayant-gaur.webp"},
    {"row": 2, "name": "Alok Gupta", "role": "CEO, Secure Operations & AI", "org": "Secure Operations & AI", "img": "alok-gupta.webp"},
    {"row": 2, "name": "Dr. Akash Thakar", "role": "Assistant Professor", "org": "Rashtriya Raksha University", "img": "akash-thakar.webp"},
    {"row": 2, "name": "Dr. Nishant Sawant", "role": "Director, Managed Security Services", "org": "Meta Infotech", "img": "nishant-sawant.webp"},
    {"row": 2, "name": "Mimansa Ambastha", "role": "Founder & Data Privacy Lawyer", "org": "Starlex Consultants", "img": "mimansa-ambastha.webp"},
  ];

  /* ==================================================================
     TUNING
     ================================================================== */
  const SPEED       = 45;    // px per second of drift
  const START_DELAY = 450;   // beat after arriving before the drift begins
  const RESUME_MS   = 1200;  // idle time after an interaction before it resumes
  const NUDGE_MS    = 620;   // prev/next glide duration
  const FRICTION    = 0.94;  // inertia decay per 16.7ms after a drag
  const DRAG_SLOP   = 6;     // px of travel before a drag suppresses a click

  const easeOutCubic = t => 1 - Math.pow(1 - t, 3);
  const now = () => performance.now();

  /* ==================================================================
     CARD BUILDING
     ================================================================== */
  const ARROW_TPL = (() => {
    const t = document.createElement('template');
    t.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" ' +
      'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
      '<line x1="7" y1="17" x2="17" y2="7"></line>' +
      '<polyline points="8 7 17 7 17 16"></polyline></svg>';
    return t.content.firstElementChild;
  })();

  const el = (tag, cls) => {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    return n;
  };

  const initialsOf = (name) => {
    const skip = /^(air|marshal|lt|gen|maj|col|avm|dr|mr|ms|ca|retd|vsm|sysm|avsm)$/i;
    const parts = name.replace(/[().,]/g, ' ').split(/\s+/)
      .filter(p => p && !skip.test(p));
    return parts.slice(0, 2).map(p => p[0]).join('').toUpperCase();
  };

  // Text is set with textContent, never innerHTML — names carry "&" and
  // similar, and this sidesteps escaping bugs entirely.
  const buildCard = (s, eager) => {
    const card = el('article', 'expert-card');

    const shell = el('div', 'expert-shell');
    shell.setAttribute('aria-hidden', 'true');
    card.appendChild(shell);

    const photo = el('div', 'expert-photo');
    const img = el('img');
    img.src = IMG_BASE + s.img;
    img.alt = s.name;
    img.decoding = 'async';
    img.draggable = false;
    if (eager) { img.loading = 'eager'; img.setAttribute('fetchpriority', 'high'); }
    else img.loading = 'lazy';

    // If a file is missing the card still reads properly instead of
    // showing a broken frame — useful while the images are being added.
    img.addEventListener('error', () => {
      photo.classList.add('no-photo');
      photo.dataset.initials = initialsOf(s.name);
    }, { once: true });

    photo.appendChild(img);

    const mark = el('span', 'expert-mark');
    mark.setAttribute('aria-hidden', 'true');
    mark.textContent = 'Speaker';
    photo.appendChild(mark);
    card.appendChild(photo);

    const lip = el('div', 'expert-lip');
    lip.setAttribute('aria-hidden', 'true');
    card.appendChild(lip);

    const content  = el('div', 'expert-content');
    const headline = el('div', 'expert-headline');

    const name = el('h3', 'expert-name');
    name.textContent = s.name;
    headline.appendChild(name);

    const role = el('p', 'expert-role');
    role.textContent = s.role;
    headline.appendChild(role);
    content.appendChild(headline);

    const meta = el('div', 'expert-meta');
    const org  = el('p', 'expert-org');
    org.textContent = s.org;
    meta.appendChild(org);

    const link = el('a', 'expert-link');
    link.href = s.url || '#';
    link.setAttribute('aria-label', 'View profile of ' + s.name);
    link.appendChild(ARROW_TPL.cloneNode(true));
    meta.appendChild(link);

    content.appendChild(meta);
    card.appendChild(content);
    return card;
  };

  /* ==================================================================
     INIT
     ================================================================== */
  const init = () => {
    const section = document.getElementById('isolated-expert-module');
    if (!section) return;

    // Guard against this file being included twice, or running alongside an
    // older build — two engines on one track look exactly like "broken".
    if (section.dataset.marqueeReady === '1') return;
    section.dataset.marqueeReady = '1';

    const viewports = Array.from(section.querySelectorAll('.expert-carousel'));
    const prevBtn   = section.querySelector('#expert-prev-btn');
    const nextBtn   = section.querySelector('#expert-next-btn');
    const dotsBox   = section.querySelector('#expert-pagination-container');
    if (!viewports.length) return;

    /* -- render the cards --------------------------------------------- */
    viewports.forEach(vp => {
      const track = vp.querySelector('.expert-track');
      if (!track) return;
      const which = vp.dataset.row === 'reverse' ? 2 : 1;
      const list  = SPEAKERS.filter(s => (s.row || 1) === which);
      const frag  = document.createDocumentFragment();
      list.forEach((s, i) => frag.appendChild(buildCard(s, i < 2)));
      track.textContent = '';
      track.appendChild(frag);
    });

    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    let reduceMotion  = motionQuery.matches;

    /* ==================================================================
       ONE ROW
       ================================================================== */
    const createRow = (viewport) => {
      const dir   = viewport.dataset.row === 'reverse' ? -1 : 1;
      const track = viewport.querySelector('.expert-track');
      if (!track) return null;

      const cards = Array.from(track.querySelectorAll('.expert-card'));
      if (!cards.length) return null;
      const count = cards.length;

      /* clones: one extra full set makes the wrap invisible */
      const frag = document.createDocumentFragment();
      cards.forEach(card => {
        const clone = card.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        clone.classList.add('expert-card-clone');
        clone.querySelectorAll('img').forEach(img => {
          img.loading = 'lazy';
          img.removeAttribute('fetchpriority');
        });
        clone.querySelectorAll('a, button').forEach(e => e.setAttribute('tabindex', '-1'));
        frag.appendChild(clone);
      });
      track.appendChild(frag);

      let step = 0, loop = 0;
      let pos = 0, drawn = NaN;
      let mode = 'idle';            // idle | drift | drag | inertia | glide
      let velocity = 0, resumeAt = 0;
      let gFrom = 0, gTo = 0, gStart = 0;

      const measure = () => {
        const cs  = getComputedStyle(track);
        const gap = parseFloat(cs.columnGap) || parseFloat(cs.gap) || 24;
        const w   = cards[0].getBoundingClientRect().width;
        if (!w) return false;                     // layout not ready yet
        const old = loop;
        step = w + gap;
        loop = step * count;
        if (old > 0) pos = (pos / old) * loop;    // keep place across resizes
        drawn = NaN;
        return true;
      };

      const render = () => {
        if (loop > 0) pos = ((pos % loop) + loop) % loop;
        if (pos === drawn) return;                // skip identical frames
        drawn = pos;
        track.style.transform = 'translate3d(' + (-pos).toFixed(2) + 'px,0,0)';
      };

      const row = {
        viewport, track, dir, count, measure,
        get ready() { return loop > 0; },
        get index() {
          return step > 0 ? ((Math.round(pos / step) % count) + count) % count : 0;
        },

        reset() { pos = 0; render(); },           // always lands on card 1
        drift() { if (!reduceMotion && loop > 0) mode = 'drift'; },
        pause() { mode = 'idle'; velocity = 0; resumeAt = 0; },

        holdThenDrift(delay = RESUME_MS) { mode = 'idle'; resumeAt = now() + delay; },

        nudge(steps) {
          if (step <= 0) return;
          gFrom = pos;
          gTo   = Math.round(pos / step) * step + steps * step;
          if (reduceMotion) { pos = gTo; render(); return; }
          gStart = now();
          mode = 'glide';
        },

        shove(dx) { pos += dx; render(); row.holdThenDrift(); },

        update(t, dt) {
          if (loop <= 0) return;
          switch (mode) {
            case 'drift':
              pos += dir * SPEED * (dt / 1000);
              render();
              break;

            case 'inertia':
              pos -= velocity * dt;
              velocity *= Math.pow(FRICTION, dt / 16.67);
              render();
              if (Math.abs(velocity) < 0.02) { velocity = 0; row.holdThenDrift(600); }
              break;

            case 'glide': {
              const p = Math.min((t - gStart) / NUDGE_MS, 1);
              pos = gFrom + (gTo - gFrom) * easeOutCubic(p);
              render();
              if (p >= 1) row.holdThenDrift();
              break;
            }

            case 'idle':
              if (resumeAt && t >= resumeAt) { resumeAt = 0; row.drift(); }
              break;
          }
        }
      };

      /* drag: one code path for mouse, pen AND touch.
         `touch-action: pan-y` leaves the vertical axis to the page. */
      let dragging = false, startX = 0, startPos = 0, moved = 0;
      let lastX = 0, lastT = 0;

      viewport.addEventListener('pointerdown', (e) => {
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        dragging = true;
        moved = 0;
        startX = lastX = e.clientX;
        lastT = now();
        startPos = pos;
        velocity = 0;
        mode = 'drag';
        viewport.classList.add('is-grabbing');
        try { viewport.setPointerCapture(e.pointerId); } catch (_) {}
      });

      viewport.addEventListener('pointermove', (e) => {
        if (!dragging) return;
        const dx = e.clientX - startX;
        moved = Math.max(moved, Math.abs(dx));
        if (e.pointerType === 'mouse') e.preventDefault();
        pos = startPos - dx;
        render();
        const t = now(), dt = t - lastT;
        if (dt > 0) velocity = (e.clientX - lastX) / dt;   // px per ms
        lastX = e.clientX;
        lastT = t;
      });

      const endDrag = (e) => {
        if (!dragging) return;
        dragging = false;
        viewport.classList.remove('is-grabbing');
        try { viewport.releasePointerCapture(e.pointerId); } catch (_) {}
        if (Math.abs(velocity) > 0.05) mode = 'inertia';
        else row.holdThenDrift();
      };

      viewport.addEventListener('pointerup', endDrag);
      viewport.addEventListener('pointercancel', endDrag);
      viewport.addEventListener('lostpointercapture', endDrag);
      viewport.addEventListener('dragstart', e => e.preventDefault());

      viewport.addEventListener('click', (e) => {
        if (moved > DRAG_SLOP) { e.preventDefault(); e.stopPropagation(); moved = 0; }
      }, true);

      viewport.addEventListener('wheel', (e) => {
        if (Math.abs(e.deltaX) <= Math.abs(e.deltaY)) return;   // leave vertical alone
        e.preventDefault();
        row.shove(e.deltaX);
      }, { passive: false });

      measure();
      return row;
    };

    const rows = viewports.map(createRow).filter(Boolean);
    if (!rows.length) return;

    /* ==================================================================
       DOTS — tied to the first row
       ================================================================== */
    let dots = [], lastActive = -1;

    if (dotsBox) {
      const frag = document.createDocumentFragment();
      for (let i = 0; i < rows[0].count; i++) {
        const d = document.createElement('span');
        d.className = 'expert-dot expert-dot-xsmall';
        frag.appendChild(d);
      }
      dotsBox.textContent = '';
      dotsBox.appendChild(frag);
      dots = Array.from(dotsBox.children);
    }

    const paintDots = () => {
      if (!dots.length) return;
      const active = rows[0].index;
      if (active === lastActive) return;
      lastActive = active;
      for (let i = 0; i < dots.length; i++) {
        const d = Math.abs(i - active);
        dots[i].className = 'expert-dot ' +
          (d === 0 ? 'expert-dot-mid' : d === 1 ? 'expert-dot-small' : 'expert-dot-xsmall');
      }
    };

    /* ==================================================================
       ONE rAF LOOP FOR BOTH ROWS
       ================================================================== */
    let rafId = null, prevT = 0;

    const frame = (t) => {
      const dt = prevT ? Math.min(t - prevT, 50) : 16.7;   // clamp after a tab switch
      prevT = t;
      for (let i = 0; i < rows.length; i++) rows[i].update(t, dt);
      paintDots();
      rafId = requestAnimationFrame(frame);
    };

    const startLoop = () => { if (rafId === null) { prevT = 0; rafId = requestAnimationFrame(frame); } };
    const stopLoop  = () => { if (rafId !== null) { cancelAnimationFrame(rafId); rafId = null; } };

    /* ==================================================================
       VISIBILITY — belt and braces
       ================================================================== */
    let hasEntered = false, active = false, ioSays = false, startTimer = null;

    const ensureMeasured = (attempt = 0) => {
      const ok = rows.every(r => r.measure() !== false && r.ready);
      if (ok || attempt > 60) return ok;
      requestAnimationFrame(() => ensureMeasured(attempt + 1));
      return false;
    };

    const rectVisible = () => {
      const r  = section.getBoundingClientRect();
      const vh = window.innerHeight || document.documentElement.clientHeight;
      return r.bottom > vh * 0.12 && r.top < vh * 0.88;
    };

    const setActive = (on) => {
      if (on === active) return;
      active = on;

      if (!on) {
        clearTimeout(startTimer);
        rows.forEach(r => r.pause());
        stopLoop();
        return;
      }

      if (!ensureMeasured()) {
        requestAnimationFrame(() => { active = false; setActive(true); });
        return;
      }

      const first = !hasEntered;
      if (first) { hasEntered = true; rows.forEach(r => r.reset()); }

      startLoop();
      clearTimeout(startTimer);
      startTimer = setTimeout(() => {
        if (active) rows.forEach(r => r.drift());
      }, first ? START_DELAY : 0);
    };

    const evaluate = () => setActive(ioSays || rectVisible());

    if ('IntersectionObserver' in window) {
      new IntersectionObserver((entries) => {
        for (const entry of entries) ioSays = entry.isIntersecting;
        evaluate();
      }, { threshold: [0, 0.01, 0.15] }).observe(section);
    }

    let scrollTicking = false;
    const onScroll = () => {
      if (scrollTicking) return;
      scrollTicking = true;
      requestAnimationFrame(() => { scrollTicking = false; evaluate(); });
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    document.addEventListener('scroll', onScroll, { passive: true, capture: true });

    /* ==================================================================
       INPUT
       ================================================================== */
    if (nextBtn) nextBtn.addEventListener('click', () => rows.forEach(r => r.nudge(r.dir)));
    if (prevBtn) prevBtn.addEventListener('click', () => rows.forEach(r => r.nudge(-r.dir)));

    // Hovering deliberately does NOT pause the drift.

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) stopLoop();
      else if (active) startLoop();
    });

    const onMotionChange = () => {
      reduceMotion = motionQuery.matches;
      if (reduceMotion) rows.forEach(r => r.pause());
      else if (active) rows.forEach(r => r.drift());
    };
    if (motionQuery.addEventListener) motionQuery.addEventListener('change', onMotionChange);
    else if (motionQuery.addListener) motionQuery.addListener(onMotionChange);

    let resizeTimer = null;
    const remeasure = () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => { rows.forEach(r => r.measure()); lastActive = -1; evaluate(); }, 150);
    };
    window.addEventListener('resize', remeasure, { passive: true });
    if ('ResizeObserver' in window) new ResizeObserver(remeasure).observe(viewports[0]);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(remeasure);
    window.addEventListener('load', () => { remeasure(); evaluate(); }, { once: true });

    [300, 1200, 3000].forEach(ms => setTimeout(evaluate, ms));

    ensureMeasured();
    evaluate();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
// speaker section ended

        // focus area 

      (function() {
        // --- DATA ---
        const focusAreas = [
          { id: 'financial', title: 'Financial Fraud & Cybercrime', icon: 'landmark' },
          { id: 'cloud', title: 'Cloud & Mobile Security', icon: 'cloud' },
          { id: 'threat', title: 'Threat Intelligence', icon: 'shield-alert' },
          { id: 'digital', title: 'Digital Forensics', icon: 'fingerprint' },
          { id: 'landscape', title: 'Cybercrime Landscape', icon: 'globe-lock' },
          { id: 'privacy', title: 'Data Privacy & Protection', icon: 'file-lock-2' },
          { id: 'crypto', title: 'Blockchain Forensics', icon: 'link' },
          { id: 'ai', title: 'AI in Cyber Defense', icon: 'cpu' }
        ];

        let activeId = 'digital';

        const wrapper = document.getElementById('focusAreasComponent');
        const row1Data = focusAreas.slice(0, 4);
        const row2Data = focusAreas.slice(4, 8);

        const row1Container = document.getElementById('fa-row1');
        const row2Container = document.getElementById('fa-row2');

        function createCardHTML(data, key) {
          const isActive = data.id === activeId ? 'active' : '';
          return `
            <div class="fa-glow-card ${isActive}" data-id="${data.id}">
              <div class="fa-glow-border-layer"></div>
              <div class="fa-static-border-layer"></div>
              <div class="fa-inner-bg">
                <div class="fa-inner-glow"></div>
                <div class="fa-card-content">
                  <div class="fa-card-icon">
                    <i data-lucide="${data.icon}" width="32" height="32" stroke-width="1.5"></i>
                  </div>
                  <h3 class="fa-card-title">${data.title}</h3>
                </div>
              </div>
            </div>
          `;
        }

        function populateRow(container, dataArray) {
          let htmlString = '';
          const repeatedData = [...dataArray, ...dataArray, ...dataArray];
          repeatedData.forEach((item, index) => {
            htmlString += createCardHTML(item, `${item.id}-${index}`);
          });
          container.innerHTML = htmlString;
        }

        populateRow(row1Container, row1Data);
        populateRow(row2Container, row2Data);

        // Initialize Lucide Icons if available globally
        if (typeof lucide !== 'undefined') {
          lucide.createIcons();
        }

        const allCards = wrapper.querySelectorAll('.fa-glow-card');

        allCards.forEach(card => {
          card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);
          });

          card.addEventListener('click', () => {
            activeId = card.getAttribute('data-id');
            allCards.forEach(c => {
              if (c.getAttribute('data-id') === activeId) {
                c.classList.add('active');
              } else {
                c.classList.remove('active');
              }
            });
          });
        });
      })();





        