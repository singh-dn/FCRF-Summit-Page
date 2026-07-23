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
   Two-row marquee engine, transform-driven.
   ========================================================================= */

(() => {
  'use strict';

  const SPEED       = 45;    // px per second of drift
  const START_DELAY = 450;   // pause after arriving before the drift begins
  const RESUME_MS   = 1200;  // idle time after an interaction before it resumes
  const NUDGE_MS    = 620;   // prev/next glide duration
  const FRICTION     = 0.94; // inertia decay per 16.7ms after a drag
  const DRAG_SLOP   = 6;     // px of travel before a drag suppresses a click

  const easeOutCubic = t => 1 - Math.pow(1 - t, 3);
  const now = () => performance.now();

  const init = () => {
    const section = document.getElementById('isolated-expert-module');
    if (!section) return;

    // Guard against this file being included twice, or running alongside the
    // older build — double engines on one track look exactly like "broken".
    if (section.dataset.marqueeReady === '1') return;
    section.dataset.marqueeReady = '1';

    const viewports = Array.from(section.querySelectorAll('.expert-carousel'));
    const prevBtn   = section.querySelector('#expert-prev-btn');
    const nextBtn   = section.querySelector('#expert-next-btn');
    const dotsBox   = section.querySelector('#expert-pagination-container');
    if (!viewports.length) return;

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

      /* -- clones: one extra full set makes the wrap invisible ---------- */
      const frag = document.createDocumentFragment();
      cards.forEach(card => {
        const clone = card.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        clone.classList.add('expert-card-clone');
        clone.querySelectorAll('img').forEach(img => {
          img.loading  = 'lazy';
          img.decoding = 'async';
          img.removeAttribute('fetchpriority');
        });
        clone.querySelectorAll('a, button').forEach(el => el.setAttribute('tabindex', '-1'));
        frag.appendChild(clone);
      });
      track.appendChild(frag);

      cards.forEach((card, i) => {
        const img = card.querySelector('img');
        if (!img) return;
        img.decoding = 'async';
        if (i < 2) { img.loading = 'eager'; img.setAttribute('fetchpriority', 'high'); }
      });

      /* -- state -------------------------------------------------------- */
      let step = 0, loop = 0;
      let pos = 0, drawn = NaN;
      let mode = 'idle';            // idle | drift | drag | inertia | glide
      let velocity = 0, resumeAt = 0;
      let gFrom = 0, gTo = 0, gStart = 0;

      const measure = () => {
        const cs  = getComputedStyle(track);
        const gap = parseFloat(cs.columnGap) || parseFloat(cs.gap) || 24;
        const w   = cards[0].getBoundingClientRect().width;
        if (!w) return false;                       // layout not ready yet
        const old = loop;
        step = w + gap;
        loop = step * count;
        if (old > 0) pos = (pos / old) * loop;      // keep relative place on resize
        drawn = NaN;                                // force a repaint
        return true;
      };

      const wrap = () => {
        if (loop <= 0) return;
        pos = ((pos % loop) + loop) % loop;
      };

      const render = () => {
        wrap();
        if (pos === drawn) return;                  // skip identical frames
        drawn = pos;
        track.style.transform = 'translate3d(' + (-pos).toFixed(2) + 'px,0,0)';
      };

      const row = {
        viewport, track, dir, count, measure,
        get ready() { return loop > 0; },
        get index() {
          return step > 0 ? ((Math.round(pos / step) % count) + count) % count : 0;
        },

        reset() { pos = 0; render(); },             // always lands on card 1

        drift() { if (!reduceMotion && loop > 0) mode = 'drift'; },
        pause() { mode = 'idle'; velocity = 0; resumeAt = 0; },

        holdThenDrift(delay = RESUME_MS) {
          mode = 'idle';
          resumeAt = now() + delay;
        },

        nudge(steps) {
          if (step <= 0) return;
          gFrom  = pos;
          gTo    = Math.round(pos / step) * step + steps * step;
          if (reduceMotion) { pos = gTo; render(); return; }
          gStart = now();
          mode   = 'glide';
        },

        shove(dx) {                                 // trackpad / wheel
          pos += dx;
          render();
          row.holdThenDrift();
        },

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

      /* -- drag: one code path for mouse, pen AND touch ------------------ */
      /* `touch-action: pan-y` on the viewport lets the page still scroll
         vertically while we own the horizontal axis, so touch and mouse now
         feel identical instead of one being native and one simulated. */
      let dragging = false, startX = 0, startPos = 0, moved = 0;
      let lastX = 0, lastT = 0;

      viewport.addEventListener('pointerdown', (e) => {
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        dragging = true;
        moved    = 0;
        startX   = lastX = e.clientX;
        lastT    = now();
        startPos = pos;
        velocity = 0;
        mode     = 'drag';
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

        const t  = now();
        const dt = t - lastT;
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

      // a drag must never fire the link underneath it
      viewport.addEventListener('click', (e) => {
        if (moved > DRAG_SLOP) { e.preventDefault(); e.stopPropagation(); moved = 0; }
      }, true);

      // horizontal trackpad gestures, since there is no native scroller now
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
    let hasEntered = false;
    let active     = false;
    let ioSays     = false;
    let startTimer = null;

    // Measurement can fail on the first pass (fonts, lazy CSS, hidden parent).
    // Keep retrying on animation frames until the cards report a real width.
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
        // not measurable yet — try again next frame rather than giving up
        requestAnimationFrame(() => { active = false; setActive(true); });
        return;
      }

      if (!hasEntered) {
        hasEntered = true;
        rows.forEach(r => r.reset());          // arrive on card 1, every time
      }

      startLoop();
      clearTimeout(startTimer);
      startTimer = setTimeout(() => {
        if (active) rows.forEach(r => r.drift());
      }, hasEntered ? START_DELAY : 0);
    };

    const evaluate = () => setActive(ioSays || rectVisible());

    if ('IntersectionObserver' in window) {
      new IntersectionObserver((entries) => {
        for (const entry of entries) ioSays = entry.isIntersecting;
        evaluate();
      }, { threshold: [0, 0.01, 0.15] }).observe(section);
    }

    // Independent fallback. Cheap: one rect read per animation frame at most,
    // and only while the user is actually scrolling.
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

    // NOTE: hovering the section no longer pauses the drift — removed on request.

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

    /* Late safety sweep: if something upstream delayed layout, this catches
       the case where the section is plainly on screen but never started. */
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





        