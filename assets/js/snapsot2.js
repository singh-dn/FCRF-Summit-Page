  (function() {
            function initMoUAnimations() {
                const observerOptions = {
                    threshold: 0.15, // Trigger when 15% visible
                    rootMargin: "0px"
                };

                const observer = new IntersectionObserver((entries, obs) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('mo-u-visible');
                            obs.unobserve(entry.target);
                        }
                    });
                }, observerOptions);

                // Select and observe all animated elements
                const animatedElements = document.querySelectorAll(
                    '.mo-u-slide-up, .mo-u-slide-down, .mo-u-slide-from-right, .mo-u-slide-from-left'
                );
                
                animatedElements.forEach(el => observer.observe(el));
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initMoUAnimations);
            } else {
                initMoUAnimations();
            }
        })();


        // js for html 2 page 

         // Simple Intersection Observer for Fade Up Animation
        // document.addEventListener('DOMContentLoaded', () => {
        //     const observerOptions = {
        //         root: null,
        //         rootMargin: '0px',
        //         threshold: 0.1
        //     };

        //     const observer = new IntersectionObserver((entries, observer) => {
        //         entries.forEach(entry => {
        //             if (entry.isIntersecting) {
        //                 entry.target.classList.add('mo-visible');
        //                 observer.unobserve(entry.target);
        //             }
        //         });
        //     }, observerOptions);

        //     const fadeElements = document.querySelectorAll('.mo-fade-up');
        //     fadeElements.forEach(el => observer.observe(el));
        // });