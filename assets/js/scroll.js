 (function() {
      // Logic uses IDs scoped to this widget to avoid conflict
      const section = document.getElementById('ds-trigger-section');
      const root = document.getElementById('ds-widget-root');
      
      // Safety check: ensure widget exists
      if (!section || !root) return;
      
      const cards = root.querySelectorAll('.ds-card');
      const totalCards = cards.length;
      
      let currentProgress = 0;
      let targetProgress = 0;
      // 'ease' controls smoothness. Lower = smoother/slower.
      const ease = 0.08; 

      function animate() {
        const sectionRect = section.getBoundingClientRect();
        const sectionTop = sectionRect.top;
        const windowHeight = window.innerHeight;
        const sectionHeight = sectionRect.height;
        
        // Calculate 0.0 to 1.0 progress based on scroll position
        const scrollDistance = -sectionTop;
        const maxDistance = sectionHeight - windowHeight;
        
        // Avoid division by zero
        if (maxDistance <= 0) {
            requestAnimationFrame(animate);
            return;
        }

        let rawTarget = scrollDistance / maxDistance;
        rawTarget = Math.max(0, Math.min(1, rawTarget));
        
        targetProgress = rawTarget;
        
        // Smooth interpolation
        currentProgress += (targetProgress - currentProgress) * ease;
        
        // Stop calculating if negligible difference (Performance opt)
        if (Math.abs(targetProgress - currentProgress) < 0.0001) {
             currentProgress = targetProgress;
        }

        const transitionCount = totalCards - 1; 
        const rawStep = currentProgress * transitionCount;

        cards.forEach((card, index) => {
            if (index === 0) {
              const scaleAmount = Math.min(Math.max(rawStep, 0), 1); 
              const scale = 1 - (scaleAmount * 0.05); 
              card.style.transform = `scale(${scale})`;
            } else {
              const cardStartStep = index - 1;
              const cardProgress = Math.max(0, Math.min(1, rawStep - cardStartStep));
              
              const startPos = 110; 
              const endPos = index * 4; // Visual offset for stacked look
              const currentY = startPos - (cardProgress * (startPos - endPos));
              
              const nextCardProgress = Math.max(0, Math.min(1, rawStep - index));
              const scale = 1 - (nextCardProgress * 0.05);

              card.style.transform = `translateY(${currentY}vh) scale(${scale})`;
            }
        });

        requestAnimationFrame(animate);
      }

      requestAnimationFrame(animate);
    })();




  

    