  const scheduleData = {
           1: {
        date: "Day 1",
        sessions: [
            {
                time: "11:30 AM - 12:30 PM",
                location: "Main Conference Hall",
                title: "National Cyber Security: Policy, Preparedness and AI-Enabled Threats",
                description: "Discussion on national cybersecurity strategy, AI-enabled threats, and policy preparedness.",
                type: "Conference Session",
                speakers: []
            },
            {
                time: "2:15 PM - 3:00 PM",
                location: "Main Conference Hall",
                title: "Digital Forensics and Cyber Investigations",
                description: "Tracing criminals in a borderless digital ecosystem using modern digital forensics techniques.",
                type: "Conference Session",
                speakers: []
            },
            {
                time: "3:00 PM - 3:45 PM",
                location: "Main Conference Hall",
                title: "Securing Critical Infrastructure",
                description: "Cyber resilience strategies for protecting essential national services and infrastructure.",
                type: "Conference Session",
                speakers: []
            },
            {
                time: "3:45 PM - 4:30 PM",
                location: "Main Conference Hall",
                title: "Blockchain, Cryptocurrency and Web3 Investigations",
                description: "Investigating crypto-related crimes and challenges in decentralized ecosystems.",
                type: "Conference Session",
                speakers: []
            },
            {
                time: "4:30 PM - 5:15 PM",
                location: "Main Conference Hall",
                title: "Securing the Internet of Things",
                description: "Security risks and protection strategies for IoT, smart devices, and connected ecosystems.",
                type: "Conference Session",
                speakers: []
            }
        ]
    },

    2: {
        date: "Day 2",
        sessions: [
            {
                time: "10:00 AM - 10:45 AM",
                location: "Main Conference Hall",
                title: "Combating Digital Threats",
                description: "Addressing sextortion, digital arrest scams, identity fraud and AI-driven deception.",
                type: "Conference Session",
                speakers: []
            },
            {
                time: "11:30 AM - 12:15 PM",
                location: "Main Conference Hall",
                title: "BFSI Crime in 2026",
                description: "Financial frauds, payment abuse and trust exploitation in the banking ecosystem.",
                type: "Conference Session",
                speakers: []
            },
            {
                time: "12:15 PM - 1:00 PM",
                location: "Main Conference Hall",
                title: "Fighting Ransomware, Malware and Data Breaches",
                description: "Strategies for prevention, detection and response to modern cyber attacks.",
                type: "Conference Session",
                speakers: []
            },
            {
                time: "2:00 PM - 2:30 PM",
                location: "Main Conference Hall",
                title: "CISO Forum",
                description: "Governance, crisis readiness and securing the AI-enabled enterprise.",
                type: "Panel",
                speakers: []
            },
            {
                time: "2:30 PM - 3:00 PM",
                location: "Main Conference Hall",
                title: "Synthetic Threats",
                description: "Deepfakes, voice cloning and AI-generated scams in the modern threat landscape.",
                type: "Conference Session",
                speakers: []
            },
            {
                time: "3:00 PM - 3:30 PM",
                location: "Main Conference Hall",
                title: "Combating CSAM and Online Abuse",
                description: "Addressing online harms and protecting digital communities.",
                type: "Conference Session",
                speakers: []
            },
            {
                time: "3:30 PM - 4:00 PM",
                location: "Main Conference Hall",
                title: "Privacy, Data Protection and Compliance",
                description: "Understanding DPDP, IT Act regulations and the future of responsible innovation.",
                type: "Conference Session",
                speakers: []
            }
        ]
    }
};

        function switchDay(day) {
            // UI Update: Active Tab
            document.querySelectorAll('.ss-day-tab').forEach(t => t.classList.remove('active'));
            document.getElementById(`btn-day-${day}`).classList.add('active');
            
            // UI Update: Date Display
            document.getElementById('display-date').innerText = scheduleData[day].date;

            // Render List
            const container = document.getElementById('schedule-container');
            container.innerHTML = '';

            scheduleData[day].sessions.forEach((session, idx) => {
                const card = document.createElement('div');
                card.className = 'ss-schedule-card';
                card.style.animationDelay = `${idx * 0.1}s`;

                const speakerHTML = session.speakers.map(s => `
                    <div class="ss-speaker-item">
                        <div class="ss-speaker-info">
                            <h4>${s.name}</h4>
                            <p>${s.role}</p>
                        </div>
                    </div>
                `).join('');

                card.innerHTML = `
                    <div class="ss-card-left">
                        <div class="ss-time-row">
                            <div class="ss-time-accent"></div>
                            <span class="ss-time-text">${session.time}</span>
                        </div>
                        <div class="ss-card-info-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                            ${session.location}
                        </div>
                        <span class="ss-session-tag">${session.type}</span>
                    </div>
                    <div class="ss-card-content">
                        <h3>${session.title}</h3>
                        <p>${session.description}</p>
                        <div class="ss-speaker-row">
                            ${speakerHTML}
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        // Initialize
        window.onload = () => switchDay(1);




        // bg javascript 
      (function initFutureCrimeHero() {
      // Updated the text array to feature the exact text from the new image in Slide 1
      const slidesData = [
        {
          videoUrl: "assets/video/summit-video.mp4",
          text: "The FutureCrime Summit 2026, organized by the Future Crime Research Foundation (FCRF), is India's largest conference focused on tackling technology-driven crime.",
          overlayTint: "rgba(20, 83, 45, 0.3)" 
        }
      ];

      let currentSlideIndex = 0;
      let slideInterval;
      
      const cachedSlides = [];
      const cachedDots = [];

      const moduleContainer = document.getElementById('fc-hero-module');
      if (!moduleContainer) return;

      const videoLayer = moduleContainer.querySelector('#fc-video-layer');
      const dotsLayer = moduleContainer.querySelector('#fc-dots-layer');
      const descText = moduleContainer.querySelector('#fc-desc-text');
      const header = moduleContainer.querySelector('#fc-main-header');
      const mobileToggle = moduleContainer.querySelector('#fc-mobile-toggle');
      const mobileMenu = moduleContainer.querySelector('#fc-mobile-menu');

      function buildSlider() {
        const videoFragment = document.createDocumentFragment();
        const dotFragment = document.createDocumentFragment();

        slidesData.forEach((slide, index) => {
          const videoDiv = document.createElement('div');
          videoDiv.className = `fc-slide ${index === 0 ? 'fc-active' : ''}`;
          videoDiv.innerHTML = `
            <video src="${slide.videoUrl}" autoplay loop muted playsinline></video>
            <div class="fc-slide-overlay-tint" style="background-color: ${slide.overlayTint};"></div>
            <div class="fc-slide-overlay-grad"></div>
          `;
          cachedSlides.push(videoDiv);
          videoFragment.appendChild(videoDiv);

          const dotBtn = document.createElement('button');
          dotBtn.className = "fc-dot-btn";
          dotBtn.setAttribute('aria-label', `Go to slide ${index + 1}`);
          
          const dotStrong = document.createElement('strong');
          dotStrong.className = `fc-dot-strong ${index === 0 ? 'fc-active' : ''}`;
          
          dotBtn.appendChild(dotStrong);
          dotBtn.addEventListener('click', () => goToSlide(index));
          
          cachedDots.push(dotStrong);
          dotFragment.appendChild(dotBtn);
        });

        videoLayer.appendChild(videoFragment);
        dotsLayer.appendChild(dotFragment);

        updateTypography(0);
      }

      function goToSlide(index) {
        clearInterval(slideInterval); 
        
        cachedSlides[currentSlideIndex].classList.remove('fc-active');
        cachedDots[currentSlideIndex].classList.remove('fc-active');

        cachedSlides[index].classList.add('fc-active');
        cachedDots[index].classList.add('fc-active');

        updateTypography(index);
        
        currentSlideIndex = index;
        startAutoSlide();
      }

      function updateTypography(index) {
        if(descText) {
          descText.style.opacity = '0';
          setTimeout(() => {
            descText.innerText = slidesData[index].text;
            descText.style.opacity = '1';
          }, 200);
        }
      }

      function startAutoSlide() {
        slideInterval = setInterval(() => {
          const nextIndex = (currentSlideIndex + 1) % slidesData.length;
          goToSlide(nextIndex);
        }, 8000); 
      }

      function initInteractions() {
        let ticking = false;
        window.addEventListener('scroll', () => {
          if (!ticking) {
            window.requestAnimationFrame(() => {
              if (window.scrollY > 50) {
                header.classList.add('fc-scrolled');
              } else {
                header.classList.remove('fc-scrolled');
              }
              ticking = false;
            });
            ticking = true;
          }
        });

        mobileToggle.addEventListener('click', () => {
          mobileMenu.classList.toggle('fc-open');
        });

        document.addEventListener('click', (e) => {
          if (!header.contains(e.target) && mobileMenu.classList.contains('fc-open')) {
            mobileMenu.classList.remove('fc-open');
          }
        });
      }

      function runPreloaderAndInit() {
        var timeline = gsap.timeline();

        timeline.to(".mil-preloader-animation", { opacity: 1 });

        timeline.fromTo(".mil-animation-1 .mil-h3", {
            y: "30px", opacity: 0
        }, {
            y: "0px", opacity: 1, stagger: 0.4, duration: 0.5
        });

        timeline.to(".mil-animation-1 .mil-h3", {
            opacity: 0, y: '-30', duration: 0.5
        }, "+=0.3");

        timeline.set(".mil-reveal-box", { scaleX: 0, transformOrigin: "left center" });
        timeline.to(".mil-reveal-box", { duration: 0.45, scaleX: 1, opacity: 1, ease: "power2.inOut" });
        
        timeline.set(".mil-reveal-box", { transformOrigin: "right center" }); 
        
        timeline.to(".mil-reveal-box", { duration: 0.45, scaleX: 0, ease: "power2.inOut" });
        
        timeline.fromTo(".mil-animation-2 .mil-h3", { opacity: 0 }, { opacity: 1, duration: 0.5 }, "-=0.5");
        timeline.to(".mil-animation-2 .mil-h3", { duration: 0.6, opacity: 0, y: '-30' }, "+=0.5");
        
        timeline.to(".mil-preloader", { duration: 0.8, opacity: 0, ease: 'sine' }, "+=0.2");

        timeline.fromTo(".mil-up", {
            opacity: 0, y: 40, scale: 0.98
        }, {
            duration: 0.8, y: 0, opacity: 1, scale: 1, ease: 'sine',
            clearProps: "transform", 
            onComplete: function () {
                document.querySelector('.mil-preloader').classList.add("mil-hidden");
                buildSlider();
                initInteractions();
            }
        }, "-=1");
      }

      runPreloaderAndInit();

    })();



    // hightlight js 

        (function initInteractiveFCRF() {
      const letters = document.querySelectorAll('.fc-letter-group');
      const container = document.getElementById('fc-word-container');
      const backdrop = document.getElementById('fc-backdrop');
      
      // Much safer way to check if we should behave like mobile (Click) or Desktop (Hover)
      function isMobileView() {
        return window.innerWidth <= 768;
      }

      function clearAll() {
        letters.forEach(l => l.classList.remove('is-hovered'));
        container.classList.remove('has-hover');
        backdrop.classList.remove('is-active');
      }

      letters.forEach(group => {
        // --- DESKTOP LOGIC (Hover) ---
        group.addEventListener('mouseenter', () => {
          if (!isMobileView()) {
            clearAll();
            group.classList.add('is-hovered');
            container.classList.add('has-hover');
            backdrop.classList.add('is-active');
          }
        });

        group.addEventListener('mouseleave', () => {
          if (!isMobileView()) {
            clearAll();
          }
        });

        // --- MOBILE LOGIC (Click/Tap) ---
        group.addEventListener('click', (e) => {
          if (isMobileView()) {
            e.stopPropagation(); 
            
            if (group.classList.contains('is-hovered')) {
              clearAll(); // Click again to close
            } else {
              clearAll(); // Close others, open this one
              group.classList.add('is-hovered');
              container.classList.add('has-hover');
              backdrop.classList.add('is-active');
            }
          }
        });
      });

      // Close the card when clicking anywhere else on the screen (Mobile)
      document.addEventListener('click', (e) => {
        if (isMobileView() && !e.target.closest('.fc-popup-card') && !e.target.closest('.fc-letter-group')) {
           clearAll();
        }
      });
      
      // Auto-clear states if user resizes window past the mobile breakpoint to prevent bugs
      window.addEventListener('resize', () => {
         clearAll(); 
      });

    })();


    // who attend js 

     (function initBentoSpotlight() {
      const grid = document.getElementById('fc-bento-grid');
      const cards = grid.querySelectorAll('.fc-bento-card');

      grid.addEventListener('mousemove', (e) => {
        for (const card of cards) {
          const rect = card.getBoundingClientRect();
          // Calculate mouse position relative to the specific card
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;

          // Set CSS variables that the ::before pseudo-element uses for the gradient
          card.style.setProperty('--mouse-x', `${x}px`);
          card.style.setProperty('--mouse-y', `${y}px`);
        }
      });
    })();



    // count down 

      document.addEventListener('DOMContentLoaded', () => {
            // Set the target date for the launch
            const targetDate = new Date("august 6, 2026 09:00:00").getTime();

            const elDays = document.getElementById("ai-cd-days");
            const elHours = document.getElementById("ai-cd-hours");
            const elMinutes = document.getElementById("ai-cd-minutes");
            const elSeconds = document.getElementById("ai-cd-seconds");

            const updateTimer = () => {
                const now = new Date().getTime();
                const distance = targetDate - now;

                if (distance < 0) {
                    elDays.innerText = "00";
                    elHours.innerText = "00";
                    elMinutes.innerText = "00";
                    elSeconds.innerText = "00";
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // Update UI with padded numbers (e.g., "04" instead of "4")
                elDays.innerText = days.toString().padStart(2, '0');
                elHours.innerText = hours.toString().padStart(2, '0');
                elMinutes.innerText = minutes.toString().padStart(2, '0');
                elSeconds.innerText = seconds.toString().padStart(2, '0');
            };

            // Initialize immediately and update every second
            updateTimer();
            setInterval(updateTimer, 1000);
        });



        // galary js 


          document.addEventListener('DOMContentLoaded', () => {
            
            // 1. Define the Data Array (Included Lucide-style SVG icons)
            const options = [
                {
                    title: "FutureCrime Summit Opening",
                    description: "The inaugural ceremony of FutureCrime Summit 2026 bringing together global leaders, investigators, policymakers, and cybersecurity experts.",
                    image: "assets/img/sponsor/image01.jpeg",
                    icon: `<svg class="is-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M12 3v18"/><path d="m3 21 9-18 9 18"/><path d="m12 9-4.5 12"/><path d="m12 9 4.5 12"/></svg>`
                },
                {
                    title: "Cybersecurity Keynote Session",
                    description: "Industry experts and thought leaders discussing emerging cyber threats, AI-driven attacks, and the future of global cyber defense.",
                    image: "assets/img/sponsor/image02.jpeg",
                    icon: `<svg class="is-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>`
                },
                {
                    title: "Digital Forensics & Investigation",
                    description: "Specialized sessions exploring digital evidence analysis, cybercrime investigations, and advanced DFIR techniques used by investigators.",
                    image: "assets/img/sponsor/image03.jpeg",
                    icon: `<svg class="is-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>`
                },
                {
                    title: "Expert Panel Discussions",
                    description: "Interactive discussions on ransomware, financial fraud, cyber law, data protection, and national security challenges.",
                    image: "assets/img/sponsor/image04.jpeg",
                    icon: `<svg class="is-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12h20"/><path d="M20 12v3a6 6 0 0 1-6 6H10a6 6 0 0 1-6-6v-3"/><path d="M10 5v2"/><path d="M14 4v3"/><path d="M6 6v1"/></svg>`
                },
                {
                    title: "Networking & Collaboration",
                    description: "Professionals, researchers, and cybersecurity leaders collaborating to strengthen global efforts against cybercrime.",
                    image: "assets/img/sponsor/image05.jpeg",
                    icon: `<svg class="is-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m8 3 4 8 5-5 5 15H2L8 3z"/></svg>`
                }
            ];

            const container = document.getElementById('is-gallery-container');
            let activeIndex = 0; // First item active by default

            // 2. Generate HTML for Cards
            options.forEach((option, index) => {
                const card = document.createElement('div');
                card.className = `is-card ${index === activeIndex ? 'active' : ''}`;
                card.style.backgroundImage = `url('${option.image}')`;
                card.dataset.index = index;

                card.innerHTML = `
                    <div class="is-card-shadow"></div>
                    <div class="is-card-content">
                        <div class="is-icon">${option.icon}</div>
                        <div class="is-text-wrap">
                            <div class="is-title">${option.title}</div>
                            <div class="is-desc">${option.description}</div>
                        </div>
                    </div>
                `;

                // Add Click Event Listener
                card.addEventListener('click', () => {
                    if (activeIndex === index) return;
                    
                    // Remove active class from old
                    container.children[activeIndex].classList.remove('active');
                    
                    // Update index and add active class to new
                    activeIndex = index;
                    card.classList.add('active');
                });

                container.appendChild(card);
            });

            // 3. Staggered Entrance Animation
            const cards = container.querySelectorAll('.is-card');
            cards.forEach((card, i) => {
                setTimeout(() => {
                    card.classList.add('entered');
                }, 180 * i); // 180ms delay between each card matching the React code
            });
        });