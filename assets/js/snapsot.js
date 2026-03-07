  document.addEventListener('DOMContentLoaded', () => {
            const galleryObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('active');
                    }
                });
            }, { threshold: 0.2 }); 

            const rows = document.querySelectorAll('.gallery-row');
            rows.forEach((row) => galleryObserver.observe(row));
        });

        (function() {
            function initMoUSection() {
                var items = document.querySelectorAll('.mo-u-reveal-item');
                
                if ('IntersectionObserver' in window) {
                    
                    // Add 'ready' class to prepare animations (hides text, clips image)
                    items.forEach(function(el) {
                        el.classList.add('mo-u-js-ready');
                    });

                    var observer = new IntersectionObserver(function(entries, obs) {
                        entries.forEach(function(entry) {
                            if (entry.isIntersecting) {
                                // Trigger Animation
                                entry.target.classList.add('mo-u-js-active');
                                obs.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.1 }); 

                    items.forEach(function(el) { observer.observe(el); });
                
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initMoUSection);
            } else {
                initMoUSection();
            }
        })();