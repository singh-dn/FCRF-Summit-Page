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




// speaker section 
        // Optimized Smooth Scroll, Dynamic Tapering Dots & AUTO-SCROLL Logic
       document.addEventListener("DOMContentLoaded", () => {
            // Isolate JS selection to only within this specific section container!
            const expertSection = document.getElementById('isolated-expert-module');
            if (!expertSection) return;

            const carousel = expertSection.querySelector('#expert-carousel-track');
            const prevBtn = expertSection.querySelector('#expert-prev-btn');
            const nextBtn = expertSection.querySelector('#expert-next-btn');
            const dotsContainer = expertSection.querySelector('#expert-pagination-container');
            const cards = expertSection.querySelectorAll('.expert-card');
            
            let itemWidth = 0;
            let isTicking = false;
            let dots = [];
            let lastActiveIndex = -1; 
            
            // Stepped auto-scroll variables
            let autoScrollInterval = null; // handle for the step timer
            const autoScrollDelay = 2500;  // ms a card rests before the next step
            const stepDuration = 1000;     // ms for one card's glide (the "scroll in 1 sec")

            // Custom scroll-animation handle (lets us cancel/replace in-flight animations)
            let scrollRAF = null;

            // 1. DYNAMICALLY GENERATE DOTS (based on the original cards only)
            const originalCards = Array.from(cards);
            dotsContainer.innerHTML = ''; 
            originalCards.forEach(() => {
                const dot = document.createElement('span');
                dot.className = 'expert-dot expert-dot-xsmall'; 
                dotsContainer.appendChild(dot);
            });
            dots = expertSection.querySelectorAll('.expert-dot');

            // 1b. CLONE CARDS for a seamless, never-ending loop.
            // When we scroll past the original set, the clones occupy the exact
            // same visual position, so we silently reset and the loop is invisible.
            originalCards.forEach((card) => {
                const clone = card.cloneNode(true);
                clone.setAttribute('aria-hidden', 'true');
                clone.classList.add('expert-card-clone');
                carousel.appendChild(clone);
            });

            // 2. COMPUTE WIDTHS
            const getGapWidth = () => {
                const style = window.getComputedStyle(carousel);
                return parseInt(style.gap) || 24; 
            };

            const calculateWidth = () => {
                if (carousel.firstElementChild) {
                    itemWidth = carousel.firstElementChild.offsetWidth + getGapWidth();
                }
            };

            calculateWidth();
            window.addEventListener('resize', calculateWidth, { passive: true });

            // 2b. CUSTOM rAF SMOOTH SCROLL
            // Buttery, consistent easing across browsers. Snap is briefly disabled
            // during the animation so mandatory snapping never fights the motion,
            // then restored once we land exactly on a card.
            const maxScroll = () => carousel.scrollWidth - carousel.clientWidth;
            const easeInOutCubic = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);

            const smoothScrollTo = (target, duration = 600, onComplete = null) => {
                if (scrollRAF) cancelAnimationFrame(scrollRAF);

                target = Math.max(0, Math.min(target, maxScroll()));
                const start = carousel.scrollLeft;
                const distance = target - start;
                if (Math.abs(distance) < 1) { if (onComplete) onComplete(); return; }

                carousel.style.scrollSnapType = 'none'; // avoid snap fighting the animation
                let startTime = null;

                const step = (now) => {
                    if (startTime === null) startTime = now;
                    const progress = Math.min((now - startTime) / duration, 1);
                    carousel.scrollLeft = start + distance * easeInOutCubic(progress);

                    if (progress < 1) {
                        scrollRAF = requestAnimationFrame(step);
                    } else {
                        carousel.style.scrollSnapType = ''; // restore CSS snap (we're already on a card)
                        scrollRAF = null;
                        if (onComplete) onComplete();
                    }
                };
                scrollRAF = requestAnimationFrame(step);
            };

            // Width of one full set of original cards = the seamless-loop reset distance
            const loopWidth = () => originalCards.length * itemWidth;

            const currentIndex = () => {
                if (itemWidth === 0) return 0;
                const raw = Math.round(carousel.scrollLeft / itemWidth);
                // Wrap into the original card range so buttons/dots stay consistent
                return ((raw % originalCards.length) + originalCards.length) % originalCards.length;
            };

            const goToIndex = (i, duration) => {
                smoothScrollTo(i * itemWidth, duration);
            };

            // 3. DOT HIGHLIGHTING LOGIC
            const updateDots = () => {
                if (itemWidth === 0 || dots.length === 0) {
                    isTicking = false;
                    return;
                }
                
                let activeIndex = Math.round(carousel.scrollLeft / itemWidth);
                // Wrap into the original dot range (scrollLeft now runs through clones too)
                activeIndex = ((activeIndex % dots.length) + dots.length) % dots.length;

                // Only update the DOM if the active slide actually changed
                if (activeIndex !== lastActiveIndex) {
                    dots.forEach((dot, index) => {
                        const distance = Math.abs(index - activeIndex);
                        
                        dot.className = 'expert-dot';
                        
                        if (distance === 0) {
                            dot.classList.add('expert-dot-mid'); 
                        } else if (distance === 1) {
                            dot.classList.add('expert-dot-small'); 
                        } else {
                            dot.classList.add('expert-dot-xsmall'); 
                        }
                    });
                    lastActiveIndex = activeIndex;
                }

                isTicking = false;
            };

            // 4. BUTTON CLICKS
            nextBtn.addEventListener('click', () => {
                goToIndex(currentIndex() + 1, 600);
            });

            prevBtn.addEventListener('click', () => {
                goToIndex(currentIndex() - 1, 600);
            });

            // 5. SCROLL LISTENER
            carousel.addEventListener('scroll', () => {
                if (!isTicking) {
                    window.requestAnimationFrame(updateDots);
                    isTicking = true;
                }
            }, { passive: true });

            // 6. STEPPED AUTO-SCROLL — glides one full card per step (~1s each)
            const autoStep = () => {
                if (itemWidth === 0) return;
                // Next card's position from wherever we currently rest
                const nextLeft = Math.round(carousel.scrollLeft / itemWidth) * itemWidth + itemWidth;

                smoothScrollTo(nextLeft, stepDuration, () => {
                    // Seamless loop: if that glide carried us into the cloned set,
                    // jump back by one full set — invisible because clones are identical
                    if (loopWidth() > 0 && carousel.scrollLeft >= loopWidth() - 1) {
                        carousel.style.scrollSnapType = 'none';
                        carousel.scrollLeft -= loopWidth();
                        carousel.style.scrollSnapType = '';
                    }
                });
            };

            const startAutoScroll = () => {
                stopAutoScroll(); // clear any existing timer first
                // cadence = glide time + rest time, so each card advances cleanly one by one
                autoScrollInterval = setInterval(autoStep, stepDuration + autoScrollDelay);
            };

            const stopAutoScroll = () => {
                if (autoScrollInterval) {
                    clearInterval(autoScrollInterval);
                    autoScrollInterval = null;
                }
            };

            // Pause auto-scroll while the user interacts, then resume
            expertSection.addEventListener('mouseenter', stopAutoScroll);
            expertSection.addEventListener('mouseleave', startAutoScroll);
            expertSection.addEventListener('touchstart', stopAutoScroll, { passive: true });
            expertSection.addEventListener('touchend', startAutoScroll, { passive: true });

            // INITIALIZE
            setTimeout(() => {
                calculateWidth();
                updateDots();
                startAutoScroll(); // Start the auto-scrolling
            }, 50); 
        });

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





        