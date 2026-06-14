     // State Management
        let activeDay = 1;
        let activeHall = 'main';


const scheduleData = {
    1: {
        date: "Day 1 (August 6th)",
        sessions: [
            // --- MAIN HALL ---
            {
                hall: "main",
                time: "9:30 AM - 10:30 AM",
                location: "Main Hall",
                title: "Securing Tomorrow: Future Crime Readiness in the Age of AI",
                description: "A high-level inaugural session bringing together senior government officials, law-enforcement leaders, cybersecurity experts and industry representatives to discuss future crime readiness.",
                type: "Opening Ceremony"
            },
            {
                hall: "main",
                time: "10:30 AM - 11:15 AM",
                location: "Main Hall",
                title: "Crime at Machine Speed: AI-Powered Cybercrime and the New Threat Landscape",
                description: "Exploring how AI, automation, deepfakes and synthetic identities are reshaping cybercrime and social engineering attacks.",
                type: "Panel Discussion"
            },
            {
                hall: "main",
                time: "11:15 AM - 11:45 AM",
                location: "Main Hall",
                title: "From the Dark Web to the Money Trail: Intelligence-Led Disruption of Cybercrime",
                description: "A partner spotlight session on using cyber-threat intelligence to disrupt dark-web markets, cryptocurrency-enabled crime and organised fraud networks.",
                type: "Partner Presentation"
            },
            {
                hall: "main",
                time: "12:00 PM - 12:45 PM",
                location: "Main Hall",
                title: "Defending the Digital Backbone: AI, Critical Infrastructure and Cyber Resilience",
                description: "Discussion on protecting critical infrastructure, enterprise systems and government networks against evolving cyber threats.",
                type: "Panel Discussion"
            },
            {
                hall: "main",
                time: "12:45 PM - 1:30 PM",
                location: "Main Hall",
                title: "Breaking the Fraud Chain: A Unified Regulatory and Industry Response",
                description: "Regulators, banks, telecom companies and law enforcement discuss coordinated responses to digital fraud.",
                type: "Panel Discussion"
            },
            {
                hall: "main",
                time: "2:15 PM - 3:00 PM",
                location: "Main Hall",
                title: "Follow the Money: Digital Fraud, Financial Crime and AML/CFT Intelligence",
                description: "Examining financial crime investigations involving phishing, mule accounts, cryptocurrency tracing and AML intelligence.",
                type: "Panel Discussion"
            },
            {
                hall: "main",
                time: "3:00 PM - 3:45 PM",
                location: "Main Hall",
                title: "From Data to Action: Predictive Policing, OSINT and AI-Led Investigation",
                description: "Exploring responsible use of AI, OSINT and data analytics to improve investigative outcomes.",
                type: "Panel Discussion"
            },
            {
                hall: "main",
                time: "4:00 PM - 4:45 PM",
                location: "Main Hall",
                title: "Evidence That Stands: Digital Forensics, Chain of Custody and Courtroom Admissibility",
                description: "Connecting digital forensic practices with evidentiary integrity, legal requirements and courtroom presentation.",
                type: "Panel Discussion"
            },
            {
                hall: "main",
                time: "4:45 PM - 5:30 PM",
                location: "Main Hall",
                title: "One Network, One Response: Building a National Alliance Against Cyber Fraud",
                description: "A leadership discussion focused on strengthening cooperation among stakeholders involved in cyber fraud response.",
                type: "Closing Panel"
            },

            // --- SMALL HALL ---
            {
                hall: "small",
                time: "10:30 AM - 11:15 AM",
                location: "Workshop & Innovation Hall",
                title: "The First 60 Minutes: Cyber Fraud Triage and Incident Response Simulation",
                description: "Scenario-based workshop covering immediate response steps following a cyber fraud complaint.",
                type: "Workshop"
            },
            {
                hall: "small",
                time: "11:15 AM - 11:45 AM",
                location: "Workshop & Innovation Hall",
                title: "Preserve First, Investigate Next: Mobile, Email and Network Evidence",
                description: "Technical briefing on identifying and preserving crucial evidence during the initial stages of investigations.",
                type: "Technical Briefing"
            },
            {
                hall: "small",
                time: "12:00 PM - 12:45 PM",
                location: "Workshop & Innovation Hall",
                title: "Finding the Digital Footprint: OSINT and Social Media Intelligence",
                description: "Practical skill lab covering ethical OSINT workflows and social media intelligence techniques.",
                type: "Skill Lab"
            },
            {
                hall: "small",
                time: "12:45 PM - 1:30 PM",
                location: "Workshop & Innovation Hall",
                title: "Inside the Attack: Malware, Reverse Engineering and Web Evidence",
                description: "Live demonstration showcasing how malware artefacts and web evidence support investigations.",
                type: "Live Demonstration"
            },
            {
                hall: "small",
                time: "2:15 PM - 3:00 PM",
                location: "Workshop & Innovation Hall",
                title: "From Complaint to Court: FIR Drafting, Evidence Preservation and Chain of Custody",
                description: "Practical clinic on transforming cyber incidents into legally sustainable investigations.",
                type: "Case Clinic"
            },
            {
                hall: "small",
                time: "3:00 PM - 3:45 PM",
                location: "Workshop & Innovation Hall",
                title: "Investigator 2.0: Building an AI-Assisted Investigation Workflow",
                description: "Hands-on workshop demonstrating responsible AI applications in modern investigations.",
                type: "Workshop"
            },
            {
                hall: "small",
                time: "4:00 PM - 4:45 PM",
                location: "Workshop & Innovation Hall",
                title: "Tracing the Invisible: Blockchain Forensics and Cryptocurrency Investigations",
                description: "Technical roundtable focused on cryptocurrency tracing and blockchain investigation challenges.",
                type: "Roundtable"
            },
            {
                hall: "small",
                time: "4:45 PM - 5:30 PM",
                location: "Workshop & Innovation Hall",
                title: "Building the Frontline: Modern Cyber Cells and District-Level Forensic Capacity",
                description: "Discussion on strengthening cyber investigation infrastructure and forensic capabilities.",
                type: "Roundtable"
            }
        ]
    },

    2: {
        date: "Day 2 (August 7th)",
        sessions: [
            // --- MAIN HALL ---
            {
                hall: "main",
                time: "9:30 AM - 10:30 AM",
                location: "Main Hall",
                title: "A Resilient Digital India: Leadership, Public Safety and the Future of Investigation",
                description: "Leadership session focused on cyber resilience, public safety and the future of investigations.",
                type: "Leadership Session"
            },
            {
                hall: "main",
                time: "10:30 AM - 11:15 AM",
                location: "Main Hall",
                title: "Beyond the Screen: Hybrid Scams, Human Exploitation and Platform Abuse",
                description: "Examining cybercrimes involving emotional manipulation, platform misuse and victim exploitation.",
                type: "Panel Discussion"
            },
            {
                hall: "main",
                time: "11:15 AM - 11:45 AM",
                location: "Main Hall",
                title: "Resilience by Design: Securing Enterprise Infrastructure in the AI Era",
                description: "Partner spotlight discussing enterprise security architecture and resilience strategies.",
                type: "Partner Presentation"
            },
            {
                hall: "main",
                time: "12:00 PM - 12:45 PM",
                location: "Main Hall",
                title: "Beyond Encryption: Quantum-Safe Security and the Next Digital Frontier",
                description: "Exploring the future impact of quantum computing on cybersecurity and digital identity.",
                type: "Panel Discussion"
            },
            {
                hall: "main",
                time: "12:45 PM - 1:30 PM",
                location: "Main Hall",
                title: "Protecting the Vulnerable: Child Safety, Cyberstalking and Online Victim Protection",
                description: "Discussion on combating online abuse and strengthening victim protection mechanisms.",
                type: "Panel Discussion"
            },
            {
                hall: "main",
                time: "2:15 PM - 3:00 PM",
                location: "Main Hall",
                title: "Law Without Borders: Cyber Law, Data Protection and International Cooperation",
                description: "Examining legal frameworks and international cooperation mechanisms for cybercrime.",
                type: "Panel Discussion"
            },
            {
                hall: "main",
                time: "3:00 PM - 3:45 PM",
                location: "Main Hall",
                title: "The Next Evidence Frontier: Cloud, Drone, Vehicle and Location Forensics",
                description: "A discussion on emerging sources of digital evidence and forensic preparedness.",
                type: "Panel Discussion"
            },
            {
                hall: "main",
                time: "4:00 PM - 5:30 PM",
                location: "Main Hall",
                title: "FCRF Excellence Awards 2026: Honouring the Guardians of the Digital Future",
                description: "National recognition ceremony celebrating excellence in cyber policing, forensics and public safety.",
                type: "Awards Ceremony"
            },

            // --- SMALL HALL ---
            {
                hall: "small",
                time: "10:30 AM - 11:15 AM",
                location: "Workshop & Innovation Hall",
                title: "Evidence in the Cloud: Web, SaaS and Incident Artefact Collection",
                description: "Practical skill lab on preserving and collecting evidence from cloud and SaaS environments.",
                type: "Skill Lab"
            },
            {
                hall: "small",
                time: "11:15 AM - 11:45 AM",
                location: "Workshop & Innovation Hall",
                title: "Recovering the Unrecoverable: Disk, Memory, Mobile and Damaged Media Forensics",
                description: "Technical briefing on recovering evidence from encrypted, deleted and damaged sources.",
                type: "Technical Briefing"
            },
            {
                hall: "small",
                time: "12:00 PM - 12:45 PM",
                location: "Workshop & Innovation Hall",
                title: "Proving Authenticity: E-Discovery, Metadata and Document Tampering",
                description: "Panel discussion on electronic document authenticity and evidentiary integrity.",
                type: "Expert Panel"
            },
            {
                hall: "small",
                time: "12:45 PM - 1:30 PM",
                location: "Workshop & Innovation Hall",
                title: "Truth in the Age of Synthetic Media: Deepfake and Multimedia Forensics",
                description: "Live demonstration examining methods for detecting manipulated digital media.",
                type: "Live Demonstration"
            },
            {
                hall: "small",
                time: "2:15 PM - 3:00 PM",
                location: "Workshop & Innovation Hall",
                title: "Breaking the Silos: A Multi-Agency Cyber Fraud Response Exercise",
                description: "Tabletop exercise simulating coordinated responses to cyber fraud incidents.",
                type: "Tabletop Exercise"
            },
            {
                hall: "small",
                time: "3:00 PM - 3:45 PM",
                location: "Workshop & Innovation Hall",
                title: "Ready Before the Breach: Forensic Readiness and Cyber Investigation Capacity",
                description: "Capacity-building workshop focused on investigation readiness and institutional resilience.",
                type: "Capacity Lab"
            }
        ]
    }
};

        // Functions
        function switchDay(day) {
            activeDay = day;
            
            // Update Day Tabs
            document.querySelectorAll('.ss-day-tab').forEach(t => t.classList.remove('active'));
            document.getElementById(`btn-day-${day}`).classList.add('active');
            
            // Update Date Indicator
            document.getElementById('display-date').innerText = scheduleData[day].date;

            renderSchedule();
        }

        function switchHall(hall) {
            activeHall = hall;
            
            // Update Hall Toggle Buttons
            document.querySelectorAll('.ss-hall-btn').forEach(t => t.classList.remove('active'));
            document.getElementById(`btn-hall-${hall}`).classList.add('active');

            renderSchedule();
        }

        function renderSchedule() {
            const container = document.getElementById('schedule-container');
            container.innerHTML = '';

            // Filter sessions by the currently active hall
            const filteredSessions = scheduleData[activeDay].sessions.filter(s => s.hall === activeHall);

            if (filteredSessions.length === 0) {
                container.innerHTML = `
                    <div class="ss-empty-state">
                        <p>No sessions are currently scheduled for this hall on this date.</p>
                    </div>
                `;
                return;
            }

            // Render filtered cards
            filteredSessions.forEach((session, idx) => {
                const card = document.createElement('div');
                card.className = 'ss-schedule-card';
                card.style.animationDelay = `${idx * 0.08}s`;

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
                    </div>
                `;
                container.appendChild(card);
            });
        }

        // Bootstrapping
        window.onload = () => {
            switchDay(1); // Initializes rendering with default activeDay and activeHall
        };



     // bg javascript 
    (function initFutureCrimeHero() {
      
      const slidesData = [
        {
          videoUrl: "assets/video/summit-video.mp4",
          placeholderImg: "assets/img/banner/home-page-preloader.webp", // Image to load instantly
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
            <img src="${slide.placeholderImg}" class="fc-slide-placeholder" alt="Loading media..." />
            <video src="${slide.videoUrl}" loop muted playsinline preload="auto"></video>
            <div class="fc-slide-overlay-tint" style="background-color: ${slide.overlayTint};"></div>
            <div class="fc-slide-overlay-grad"></div>
          `;
          
          const videoEl = videoDiv.querySelector('video');
          const placeholderEl = videoDiv.querySelector('.fc-slide-placeholder');

          // STRONG LOGIC: Instead of guessing when it's ready, we wait for the exact moment 
          // the video time progresses > 0, meaning frame 1 is actively painted on screen.
          videoEl.addEventListener('timeupdate', function onTimeUpdate() {
            if (videoEl.currentTime > 0) {
              placeholderEl.style.display = 'none'; // Instant removal on the spot
              videoEl.removeEventListener('timeupdate', onTimeUpdate); // Run only once
            }
          });

          // Autoplay execution
          if (index === currentSlideIndex) {
            videoEl.play().catch(e => console.warn("Autoplay blocked by browser:", e));
          }

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
        
        const prevVideo = cachedSlides[currentSlideIndex].querySelector('video');
        if(prevVideo) prevVideo.pause();

        cachedSlides[currentSlideIndex].classList.remove('fc-active');
        if(cachedDots[currentSlideIndex]) cachedDots[currentSlideIndex].classList.remove('fc-active');

        cachedSlides[index].classList.add('fc-active');
        if(cachedDots[index]) cachedDots[index].classList.add('fc-active');
        
        const nextVideo = cachedSlides[index].querySelector('video');
        if(nextVideo) {
             nextVideo.play().catch(e => console.warn("Playback blocked by browser:", e));
        }

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

        if (mobileToggle && mobileMenu) {
            mobileToggle.addEventListener('click', () => {
              mobileMenu.classList.toggle('fc-open');
            });

            document.addEventListener('click', (e) => {
              if (!header.contains(e.target) && mobileMenu.classList.contains('fc-open')) {
                mobileMenu.classList.remove('fc-open');
              }
            });
        }
      }

      // Initialize the page immediately since preloader is removed
      buildSlider();
      initInteractions();

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
                   
                   
                    image: "assets/img/sponsor/image01.jpeg",
                   
                },
                {
                  
                    image: "assets/img/sponsor/image02.jpeg",
                   
                },
                {
                  
                    image: "assets/img/sponsor/image03.jpeg",
                  
                },
                {
                   
                    image: "assets/img/sponsor/image04.jpeg",
                  
                },
                {
                  
                    image: "assets/img/sponsor/image05.png",
                  
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



        // timmer section 

 (function() {
            // Updated to August 6th, 2026 at 9:00 AM
            const TARGET_DATE = new Date('2026-08-06T09:00:00').getTime();
            
            const elDays = document.getElementById('ct-days');
            const elHours = document.getElementById('ct-hours');
            const elMins = document.getElementById('ct-mins');
            const elSecs = document.getElementById('ct-secs');

            function update() {
                const now = new Date().getTime();
                const diff = TARGET_DATE - now;

                if (diff > 0) {
                    const d = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const h = Math.floor((diff / (1000 * 60 * 60)) % 24);
                    const m = Math.floor((diff / (1000 * 60)) % 60);
                    const s = Math.floor((diff / 1000) % 60);

                    elDays.textContent = String(d).padStart(2, '0');
                    elHours.textContent = String(h).padStart(2, '0');
                    elMins.textContent = String(m).padStart(2, '0');
                    elSecs.textContent = String(s).padStart(2, '0');
                } else {
                    elDays.textContent = "00";
                    elHours.textContent = "00";
                    elMins.textContent = "00";
                    elSecs.textContent = "00";
                }
            }

            update();
            setInterval(update, 1000);
        })();


        // ------------------------------- sponsore 

              // SPONSOR DATA ARRAY
       // SPONSOR DATA ARRAY (Expanded for all tiers)
        const sponsorData = [
            // Platinum (0, 1)
            {
                tier: "Platinum Sponsor",
                name: "Resecurity",
                logo: "assets/img/sponsors/Resecurity.jpeg",
                description: "Resecurity is a global cybersecurity company delivering advanced threat intelligence, risk management, and endpoint protection solutions. Leveraging AI, big data, and human-curated intelligence, Resecurity helps enterprises and governments detect, prevent, and respond to sophisticated cyber threats in real time.",
                website: "https://www.resecurity.com"
            },
            {
                tier: "Platinum Sponsor",
                name: "Binary",
                logo: "assets/img/sponsors/Binary.jpeg",
                description: "Binary is a leading innovator in software architecture and secure digital infrastructure. Specializing in high-performance computing and threat defense mechanisms, they help government agencies and private enterprises build resilient digital ecosystems.",
                website: "https://binaryglobal.com/"
            },
            // Gold (2, 3)
            {
                tier: "Gold Sponsor", name: "MH Service", logo: "assets/img/sponsors/mh services.webp",
                description: "MH Service is a globally recognized leader in digital forensics and cyber investigation technologies. Leveraging over three decades of expertise, the company delivers cutting-edge forensic hardware, software, mobile laboratories, and incident response solutions that enable organizations to uncover digital evidence, combat cybercrime, and accelerate investigations. Trusted by law enforcement agencies, government bodies, and enterprise security teams worldwide, MH Service combines innovation, performance, and reliability to advance the future of digital investigations.", website: "https://mh-service.de/en/"
            },
            {
                tier: "Gold Sponsor", name: "ProDiscover", logo: "assets/img/sponsors/prodiscover.webp",
                description: "ProDiscover is a digital forensics and cybersecurity technology company offering advanced solutions for cyber investigations, incident response, and digital evidence analysis. Developed by Hyderabad-based DotC Technologies, its trusted forensic platform supports law enforcement, defense, corporate, and legal organizations worldwide in uncovering digital evidence and combating cyber threats effectively.", website: "https://prodiscover.com/"
            },
            // Silver (4, 5)
            {
                tier: "Silver Sponsor", name: "INNEFU Labs", logo: "assets/img/sponsors/innefu.webp",
                description: "INNEFU Labs is at the forefront of AI-driven national security and cybersecurity innovation. The company delivers intelligent platforms for predictive policing, digital forensics, intelligence fusion, fraud analytics, video intelligence, and cyber threat management. Serving defense agencies, law enforcement organizations, financial institutions, and global enterprises, INNEFU combines artificial intelligence, big data analytics, and domain expertise to provide actionable insights that strengthen security, accelerate investigations, and enable smarter decision-making in an increasingly complex digital world.", website: "https://innefu.com/"
            },
            {
                tier: "Silver Sponsor", name: "Starlight Data Solutions", logo: "assets/img/sponsors/starlight data solutions.png",
                description: "Starlight Data Solutions empowers organizations with cutting-edge cybersecurity and IT solutions designed to secure critical assets, automate workflows, and drive business innovation. With capabilities spanning digital forensics, cyber defense, cloud security, threat intelligence, infrastructure modernization, and compliance automation, the company delivers tailored, end-to-end technology services for enterprises and public sector organizations. Through deep technical expertise, strategic partnerships, and a commitment to excellence, Starlight Data Solutions helps organizations build resilient, future-ready digital ecosystems.", website: "https://www.starlightdata.in/"
            }
        ];

        const overlay = document.getElementById('dss-modal-overlay');
        const modalLogo = document.getElementById('dss-modal-logo');
        const modalName = document.getElementById('dss-modal-name');
        const modalTier = document.getElementById('dss-modal-tier');
        const modalText = document.getElementById('dss-modal-text');
        const modalUrl = document.getElementById('dss-modal-url');

        // Function to Open Modal
        window.openSponsorModal = function(index) {
            const data = sponsorData[index];
            if (!data) return;

            // Handle fallback for broken local images when testing
            modalLogo.src = data.logo;
            modalLogo.onerror = function() {
                this.src = `https://placehold.co/300x120/ffffff/000000?text=${data.name}`;
            };
            
            modalTier.innerText = data.tier;
            modalName.innerText = data.name;
            modalText.innerText = data.description;
            modalUrl.href = data.website;

            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden'; 
        };

        // Function to Close Modal
        window.closeSponsorModal = function(e) {
            overlay.style.display = 'none';
            document.body.style.overflow = 'auto'; 
        };

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSponsorModal();
        });





          // price section 

       // --- Feature Lists Data Injection ---
        const lists = {
            regular: [
                "Access to all sessions",
                "Keynotes",
                "Meals on both days",
                "Delegate kit"
            ]
        };

        function renderList(id, items) {
            const ul = document.getElementById(id);
            items.forEach(item => {
                // Replaced span with strong in the injected HTML
                ul.innerHTML += `
                    <li class="feature-item">
                        <strong class="feature-bullet"></strong>
                        <strong class="feature-text">${item}</strong>
                    </li>
                `;
            });
        }
        renderList('list-regular', lists.regular);


        // --- Animated Title Setup (Vertical Cut Reveal) ---
        const titleContainer = document.getElementById('animated-title');
        const text = "Registration Options for FutureCrime Summit 2026";
        const words = text.split(" ");
        let staggerDuration = 0.15;

        words.forEach((word, wordIndex) => {
            // Replaced span with strong for word wrappers
            const wordWrapper = document.createElement('strong');
            wordWrapper.className = 'word-wrapper';
            
            const chars = word.split('');
            chars.forEach((char, charIndex) => {
                // Replaced span with strong for char wrappers
                const charEl = document.createElement('strong');
                charEl.className = 'char-wrapper';
                
                const innerAnimSpan = document.createElement('strong');
                innerAnimSpan.textContent = char;
                innerAnimSpan.className = 'text-reveal-char';
                
                const delay = (wordIndex * staggerDuration) + (charIndex * 0.02);
                innerAnimSpan.style.animationDelay = `${delay}s`;
                
                charEl.appendChild(innerAnimSpan);
                wordWrapper.appendChild(charEl);
            });

            titleContainer.appendChild(wordWrapper);
        });


        // --- Sparkles Background Generation ---
        const sparklesContainer = document.getElementById('sparkles-container');
        for (let i = 0; i < 150; i++) {
            const particle = document.createElement('div');
            particle.className = 'sparkle-particle sparkle-anim';
            
            const size = Math.random() * 2.5 + 1.5; 
            const x = Math.random() * 100;
            const y = Math.random() * 100;
            const opacity = Math.random() * 0.6 + 0.4; 
            const animDuration = Math.random() * 3 + 2;
            const animDelay = Math.random() * -5;

            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.left = `${x}%`;
            particle.style.top = `${y}%`;
            particle.style.setProperty('--base-opacity', opacity);
            particle.style.animationDuration = `${animDuration}s`;
            particle.style.animationDelay = `${animDelay}s`;

            sparklesContainer.appendChild(particle);
        }


        // --- Currency Switch Logic ---
        let currentCurrency = 'inr';
        function setCurrency(currency) {
            currentCurrency = currency;
            
            const btnInr = document.getElementById('btn-inr');
            const btnUsd = document.getElementById('btn-usd');
            const switchPill = document.getElementById('switch-pill');
            const priceVals = document.querySelectorAll('.price-val');
            const currencySymbols = document.querySelectorAll('.currency-symbol');

            if (currentCurrency === 'usd') {
                switchPill.style.transform = `translateX(${btnInr.offsetWidth}px)`;
                switchPill.style.width = `${btnUsd.offsetWidth}px`;
                
                btnUsd.classList.remove('text-gray-400');
                btnUsd.classList.add('text-white');
                btnInr.classList.remove('text-white');
                btnInr.classList.add('text-gray-400');
            } else {
                switchPill.style.transform = `translateX(0px)`;
                switchPill.style.width = `${btnInr.offsetWidth}px`;
                
                btnInr.classList.remove('text-gray-400');
                btnInr.classList.add('text-white');
                btnUsd.classList.remove('text-white');
                btnUsd.classList.add('text-gray-400');
            }

            priceVals.forEach(el => {
                const startPrice = parseInt(el.textContent);
                const endPrice = currentCurrency === 'usd' ? parseInt(el.getAttribute('data-usd')) : parseInt(el.getAttribute('data-inr'));
                
                animateNumber(el, startPrice, endPrice, 300);
            });

            currencySymbols.forEach(el => {
                el.textContent = currentCurrency === 'usd' ? '$' : '₹';
            });
        }

        function animateNumber(element, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                element.textContent = Math.floor(progress * (end - start) + start);
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    element.textContent = end;
                }
            };
            window.requestAnimationFrame(step);
        }

        window.addEventListener('DOMContentLoaded', () => {
            const btnInr = document.getElementById('btn-inr');
            const switchPill = document.getElementById('switch-pill');
            switchPill.style.width = `${btnInr.offsetWidth}px`;
        });


        // --- Scroll Reveal Logic (Intersection Observer) ---
        const revealElements = document.querySelectorAll('.reveal-target');
        const revealOptions = {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        };

        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const delay = entry.target.getAttribute('data-delay') || 0;
                    entry.target.style.transitionDelay = `${delay}s`;
                    
                    void entry.target.offsetWidth;
                    
                    entry.target.classList.add('revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, revealOptions);

        revealElements.forEach(el => revealObserver.observe(el));





        
//   question and answer 


  (function() {
    const scope = document.getElementById('faq-component-scope');
    if(!scope) return;

    // Tabs Logic
    const tabs = scope.querySelectorAll(".faq-iso-tab");
    tabs.forEach(tab => {
      tab.addEventListener("click", () => {
        tabs.forEach(t => t.classList.remove("faq-iso-active"));
        scope.querySelectorAll(".faq-content").forEach(c => c.classList.remove("faq-iso-active"));

        tab.classList.add("faq-iso-active");
        const targetId = tab.getAttribute('data-tab');
        const targetContent = scope.querySelector(`#${targetId}`);
        if(targetContent) targetContent.classList.add("faq-iso-active");
      });
    });

    // Accordion Logic
    const questions = scope.querySelectorAll(".faq-question");
    questions.forEach(question => {
      question.addEventListener("click", () => {
        const item = question.parentElement;
        const isActive = item.classList.contains("faq-iso-active");
        
        // Close siblings
        const parent = item.parentElement;
        parent.querySelectorAll('.faq-item').forEach(i => i.classList.remove("faq-iso-active"));

        if(!isActive) {
            item.classList.add("faq-iso-active");
        }
      });
    });

    // Scroll Animation Logic
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('faq-iso-visible');
        }
      });
    }, { threshold: 0.1 });

    scope.querySelectorAll('.faq-iso-fade').forEach(el => {
      observer.observe(el);
    });
  })();

  // Initialize Icons
  if(typeof lucide !== 'undefined') lucide.createIcons();


   // Initialize Icons
        lucide.createIcons();

        document.addEventListener('DOMContentLoaded', () => {
            /* --- SCROLL UP LOGIC --- */
            const scrollUpBtn = document.getElementById('scrollUpBtn');
            let isButtonVisible = false;

            window.addEventListener('scroll', () => {
                const shouldShow = window.scrollY > 300;
                if (shouldShow !== isButtonVisible) {
                    isButtonVisible = shouldShow;
                    if (isButtonVisible) {
                        scrollUpBtn.classList.add('show');
                    } else {
                        scrollUpBtn.classList.remove('show');
                    }
                }
            }, { passive: true });

            scrollUpBtn.addEventListener('click', () => {
                const startPosition = window.scrollY;
                if (startPosition === 0) return;

                const duration = 500; 
                const startTime = performance.now();

                function scrollAnimation(currentTime) {
                    const timeElapsed = currentTime - startTime;
                    const progress = Math.min(timeElapsed / duration, 1);
                    const ease = 1 - Math.pow(1 - progress, 4); // Immediate feel

                    window.scrollTo(0, startPosition - (startPosition * ease));

                    if (timeElapsed < duration) {
                        requestAnimationFrame(scrollAnimation);
                    }
                }
                requestAnimationFrame(scrollAnimation);
            });
        });
