  // 1. Initialize Lucide Icons
// 1. Initialize Lucide Icons
        lucide.createIcons();

        // 2. Navbar Scroll Effect
        const navbarContainer = document.getElementById('navbar-container');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbarContainer.classList.add('is-scrolled');
            } else {
                navbarContainer.classList.remove('is-scrolled');
            }
        });

        // 3. Mobile Menu Toggle
        const menuBtn = document.getElementById('menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuWrapper = document.getElementById('menu-wrapper');
        let isMenuOpen = false;

        menuBtn.addEventListener('click', () => {
            isMenuOpen = !isMenuOpen;
            if (isMenuOpen) {
                mobileMenu.classList.add('is-open');
                menuWrapper.classList.add('menu-active');
            } else {
                mobileMenu.classList.remove('is-open');
                menuWrapper.classList.remove('menu-active');
            }
        });





        // speaker section 


const stalwartsData = [
    {
        name: "Maj Gen Sandeep Sharma (Retd.)",
        role: "",
        company: "",
        topic: "",
        imgUrl: "assets/img/jury/Maj Gen Sandeep  Sharma (Retd.).webp"
    },
    {
        name: "AVM (Dr.) Devesh Vatsa VSM (Retd.)",
        role: "Advisor",
        company: "Data Security Council of India",
        topic: "",
        imgUrl: "assets/img/jury/Devesh vatsa.webp"
    },
    {
        name: "Dr. Vikram Singh",
        role: "Former DGP, UP & Chancellor",
        company: "Noida International University",
        topic: "",
        imgUrl: "assets/img/jury/Vikram singh.webp"
    },
    {
        name: "Arun Kumar",
        role: "Former DG",
        company: "Railway Protection Force (RPF)",
        topic: "",
        imgUrl: "assets/img/jury/Arun kumar.webp"
    },
    {
        name: "Dr. Gulshan Rai",
        role: "Former DG",
        company: "CERT-In",
        topic: "",
        imgUrl: "assets/img/jury/Gulshan rai.webp"
    },
    {
        name: "Dr. Pavan Duggal",
        role: "Advocate",
        company: "Supreme Court of India",
        topic: "",
        imgUrl: "assets/img/jury/Pavan Duggal.webp"
    }
];

        const carousel = document.getElementById('carousel');

        // Render Cards with new Pure CSS classes
        function createCard(data) {
            const div = document.createElement('div');
            div.className = "card";
            
            div.innerHTML = `
                <div class="card-img-container">
                    <img src="${data.imgUrl}" alt="${data.name}">
                </div>
                <div class="card-info">
                    <h3 class="card-name">${data.name}</h3>
                    <p class="card-role">${data.role}</p>
                    <div class="card-company">${data.company}</div>
                    <div class="card-footer">
                        <p class="card-topic">"${data.topic}"</p>
                    </div>
                </div>
            `;
            return div;
        }

        function renderCards() {
            stalwartsData.forEach(item => carousel.appendChild(createCard(item)));
            // Duplicate for seamless infinite loop
            stalwartsData.forEach(item => carousel.appendChild(createCard(item)));
        }

        renderCards();

        // --------------------------------------------------
        // Momentum Scrolling Logic
        // --------------------------------------------------
        let isDown = false;
        let startX;
        let scrollLeft;
        let isAutoScrolling = true;
        const autoScrollSpeed = 0.3; 
        let floatScroll = 0;
        let velocity = 0;
        let prevX = 0;
        let momentumID;
        const friction = 0.96;

        function autoScroll() {
            if (isAutoScrolling) {
                floatScroll += autoScrollSpeed;
                if (floatScroll >= carousel.scrollWidth / 2) {
                    floatScroll -= carousel.scrollWidth / 2;
                }
                carousel.scrollLeft = floatScroll;
            }
            requestAnimationFrame(autoScroll);
        }
        
        requestAnimationFrame(autoScroll);

        function applyMomentum() {
            isAutoScrolling = false;
            function step() {
                if (Math.abs(velocity) > 0.2) {
                    carousel.scrollLeft -= velocity;
                    velocity *= friction;
                    if (carousel.scrollLeft <= 0) { carousel.scrollLeft += carousel.scrollWidth / 2; }
                    else if (carousel.scrollLeft >= carousel.scrollWidth / 2) { carousel.scrollLeft -= carousel.scrollWidth / 2; }
                    floatScroll = carousel.scrollLeft;
                    momentumID = requestAnimationFrame(step);
                } else {
                    isAutoScrolling = true;
                    floatScroll = carousel.scrollLeft;
                }
            }
            momentumID = requestAnimationFrame(step);
        }

        carousel.addEventListener('mousedown', (e) => {
            isDown = true;
            isAutoScrolling = false;
            cancelAnimationFrame(momentumID);
            startX = e.pageX - carousel.offsetLeft;
            scrollLeft = carousel.scrollLeft;
            floatScroll = carousel.scrollLeft;
            prevX = e.pageX;
            velocity = 0;
        });

        carousel.addEventListener('mouseleave', () => { if (isDown) { isDown = false; applyMomentum(); } });
        carousel.addEventListener('mouseup', () => { if (isDown) { isDown = false; applyMomentum(); } });

        carousel.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - carousel.offsetLeft;
            const walk = (x - startX);
            carousel.scrollLeft = scrollLeft - walk;
            velocity = (e.pageX - prevX);
            prevX = e.pageX;
            floatScroll = carousel.scrollLeft;
        });

        carousel.addEventListener('touchstart', (e) => {
            isDown = true;
            isAutoScrolling = false;
            cancelAnimationFrame(momentumID);
            startX = e.touches[0].pageX - carousel.offsetLeft;
            scrollLeft = carousel.scrollLeft;
            floatScroll = carousel.scrollLeft;
            prevX = e.touches[0].pageX;
            velocity = 0;
        });

        carousel.addEventListener('touchend', () => { if (isDown) { isDown = false; applyMomentum(); } });
        carousel.addEventListener('touchmove', (e) => {
            if (!isDown) return;
            const x = e.touches[0].pageX - carousel.offsetLeft;
            const walk = (x - startX);
            carousel.scrollLeft = scrollLeft - walk;
            velocity = (e.touches[0].pageX - prevX);
            prevX = e.touches[0].pageX;
            floatScroll = carousel.scrollLeft;
        });



        // arrow section and footer 


  // 1. Dynamic Footer Spacing & Guaranteed Visibility Fix
        function setupFooterReveal() {
            const footer = document.getElementById('reveal-footer');
            const main = document.getElementById('main-content');
            
            if (!footer || !main) return;

            // Creates the gap at the bottom for the fixed footer to show through
            function updateGap() {
                main.style.marginBottom = `${footer.offsetHeight}px`;
            }
            window.addEventListener('resize', updateGap);
            updateGap(); // Initial call
            
            // CRITICAL FIX: Use IntersectionObserver instead of scroll events.
            // This guarantees it works inside React/Angular/Canvas custom scroll containers!
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
                        footer.classList.add('is-visible'); // Triggers slide-up animation
                    } else {
                        // Only hide if scrolling UP past the trigger
                        if (entry.boundingClientRect.top > 0) {
                            footer.style.visibility = 'hidden';
                            footer.classList.remove('is-visible');
                        }
                    }
                });
            }, { rootMargin: '50px' });

            observer.observe(trigger);
        }

        // Initialize setup safely
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupFooterReveal);
        } else {
            setupFooterReveal();
        }

        // 2. Live World Clocks
        function updateClocks() {
            const now = new Date();
            const options = { hour: '2-digit', minute: '2-digit', hour12: true };

            const timeIndia = document.getElementById('time-india');
            if (timeIndia) {
                timeIndia.textContent = now.toLocaleTimeString('en-IN', { ...options, timeZone: 'Asia/Kolkata' });
            }

            const timeLondon = document.getElementById('time-london');
            if (timeLondon) {
                timeLondon.textContent = now.toLocaleTimeString('en-GB', { ...options, timeZone: 'Europe/London' }).toUpperCase();
            }

            const timeAmsterdam = document.getElementById('time-amsterdam');
            if (timeAmsterdam) {
                timeAmsterdam.textContent = now.toLocaleTimeString('en-NL', { ...options, timeZone: 'Europe/Amsterdam' }).toUpperCase();
            }
        }

        updateClocks();
        setInterval(updateClocks, 1000);

        // 3. Arrow Tracking Logic
        const arrow = document.getElementById('tracking-arrow');
        if (arrow) {
            let currentAngle = 0;
            let targetAngle = 0;
            let mouseX = window.innerWidth / 2;
            let mouseY = window.innerHeight / 2;

            window.addEventListener('mousemove', (e) => {
                mouseX = e.clientX;
                mouseY = e.clientY;
            });

            function animateArrow() {
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

                requestAnimationFrame(animateArrow);
            }
            
            animateArrow();
        }



       function openAgModal(id) {
            const modal = document.getElementById(id);
            modal.style.display = 'flex';
            setTimeout(() => { modal.classList.add('active'); }, 10);
            document.body.style.overflow = 'hidden'; 
        }

        function closeAgModal(id) {
            const modal = document.getElementById(id);
            modal.classList.remove('active');
            setTimeout(() => { modal.style.display = 'none'; }, 600); 
            document.body.style.overflow = ''; 
        }

        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.classList.remove('active');
                setTimeout(() => { event.target.style.display = 'none'; }, 600);
                document.body.style.overflow = '';
            }
        });



        // scroll up 


          lucide.createIcons();

        document.addEventListener('DOMContentLoaded', () => {
            const scrollUpBtn = document.getElementById('scrollUpBtn');

            // 1. Show/Hide based on scroll position
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    scrollUpBtn.classList.add('show');
                } else {
                    scrollUpBtn.classList.remove('show');
                }
            }, { passive: true });

            // 2. Immediate Smooth Scroll Logic
            scrollUpBtn.addEventListener('click', () => {
                const start = window.scrollY;
                const startTime = performance.now();
                const duration = 500; // 0.5 seconds for snappy feel
                
                function animate(time) {
                    const elapsed = time - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    
                    // Ease Out Quart: Starts fast, slows down at the end
                    const ease = 1 - Math.pow(1 - progress, 4);
                    
                    window.scrollTo(0, start - (start * ease));
                    
                    if (elapsed < duration) {
                        requestAnimationFrame(animate);
                    }
                }
                requestAnimationFrame(animate);
            });
        });