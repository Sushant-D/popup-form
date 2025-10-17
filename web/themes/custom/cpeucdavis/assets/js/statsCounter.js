/**
 * @file
 * Animated counter for statistics section.
 */

(function ($, Drupal, once) {
    'use strict';
  
    Drupal.behaviors.statsCounter = {
      attach: function (context, settings) {
        
        /**
         * Animate counter from 0 to target value.
         */
        function animateCounter(element, target, suffix, duration) {
          const startTime = performance.now();
          const startValue = 0;
          
          function updateCounter(currentTime) {
            const elapsedTime = currentTime - startTime;
            const progress = Math.min(elapsedTime / duration, 1);
            
            // Easing function for smooth animation (easeOutQuart)
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const currentValue = Math.floor(startValue + (target - startValue) * easeOutQuart);
            
            element.textContent = currentValue + suffix;
            
            if (progress < 1) {
              requestAnimationFrame(updateCounter);
            } else {
              // Ensure final value is exact
              element.textContent = target + suffix;
            }
          }
          
          requestAnimationFrame(updateCounter);
        }
  
        /**
         * Initialize stats counter with Intersection Observer.
         */
        const elements = once('statsCounter', '.stat-number', context);
        
        elements.forEach(function(element) {
          const $element = $(element);
          const text = $element.text().trim();
          
          // Extract number and suffix from text
          const matches = text.match(/^(\d+)(.*)$/);
          
          if (!matches) {
            console.warn('Stats Counter: Could not parse value from', text);
            return;
          }
          
          const target = parseInt(matches[1], 10);
          const suffix = matches[2] || '';
          const duration = $element.data('duration') || 2000;
          
          // Store original values
          $element.data('original-target', target);
          $element.data('original-suffix', suffix);
          
          // Set initial display to 0
          element.textContent = '0' + suffix;
          
          // Debug log
          console.log('Stats Counter initialized:', {
            element: element,
            target: target,
            suffix: suffix,
            duration: duration
          });
          
          // Check if IntersectionObserver is supported
          if ('IntersectionObserver' in window) {
            const observerOptions = {
              threshold: 0.5, // Trigger when 50% of element is visible
              rootMargin: '0px'
            };
  
            const observer = new IntersectionObserver(function(entries) {
              entries.forEach(function(entry) {
                if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                  console.log('Stats Counter: Element visible, starting animation');
                  
                  const elem = entry.target;
                  const $elem = $(elem);
                  const finalTarget = $elem.data('original-target');
                  const finalSuffix = $elem.data('original-suffix');
                  const animDuration = $elem.data('duration') || 2000;
                  
                  // Start animation
                  animateCounter(elem, finalTarget, finalSuffix, animDuration);
                  
                  // Mark as counted
                  elem.classList.add('counted');
                  
                  // Stop observing this element
                  observer.unobserve(elem);
                }
              });
            }, observerOptions);
  
            observer.observe(element);
            
          } else {
            // Fallback for older browsers - use scroll event
            console.log('Stats Counter: Using scroll fallback');
            
            function checkVisibility() {
              if (element.classList.contains('counted')) {
                return;
              }
              
              const rect = element.getBoundingClientRect();
              const windowHeight = window.innerHeight || document.documentElement.clientHeight;
              
              // Check if element is in viewport
              if (rect.top <= windowHeight * 0.75 && rect.bottom >= 0) {
                console.log('Stats Counter: Element visible (scroll fallback)');
                animateCounter(element, target, suffix, duration);
                element.classList.add('counted');
                $(window).off('scroll.statsCounter resize.statsCounter');
              }
            }
            
            $(window).on('scroll.statsCounter resize.statsCounter', checkVisibility);
            checkVisibility(); // Check on load
          }
        });
      }
    };
  
  })(jQuery, Drupal, once);