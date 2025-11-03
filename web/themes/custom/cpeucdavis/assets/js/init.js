/**
 * @file
 * Main JavaScript functionality for the theme.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * jQuery 4.0 compatibility fix for Owl Carousel.
   * Adds back functions that were removed in jQuery 4.0.
   */
  if (typeof $.camelCase === 'undefined') {
    $.camelCase = function(string) {
      return string.replace(/-([a-z])/g, function(match, letter) {
        return letter.toUpperCase();
      });
    };
  }

  // Additional jQuery 4.0 compatibility fixes
  if (typeof $.fn.size === 'undefined') {
    $.fn.size = function() {
      return this.length;
    };
  }

  // Fix for jQuery.type function
  if (typeof $.type === 'undefined') {
    $.type = function(obj) {
      if (obj == null) {
        return obj + "";
      }
      
      // Support: Android <=2.3 only (functionish RegExp)
      return typeof obj === "object" || typeof obj === "function" ?
        Object.prototype.toString.call(obj).slice(8, -1).toLowerCase() || "object" :
        typeof obj;
    };
  }

  // Fix for jQuery.isFunction (removed in jQuery 4.0)
  if (typeof $.isFunction === 'undefined') {
    $.isFunction = function(obj) {
      return typeof obj === "function";
    };
  }

  // Fix for jQuery.isArray (removed in jQuery 4.0)
  if (typeof $.isArray === 'undefined') {
    $.isArray = Array.isArray;
  }

  // Fix for jQuery.isNumeric (removed in jQuery 4.0)
  if (typeof $.isNumeric === 'undefined') {
    $.isNumeric = function(obj) {
      var type = $.type(obj);
      return (type === "number" || type === "string") &&
        !isNaN(obj - parseFloat(obj));
    };
  }

  /**
   * Mobile Navigation behavior.
   */
  Drupal.behaviors.mobileNavigation = {
    attach: function (context, settings) {
      const elements = once('mobileNavigation', 'body', context);
      
      elements.forEach(function(element) {
        const navToggler = element.querySelector('#navToggler');
        const mobileMenu = element.querySelector('#mobileMenu');
        const closeMenu = element.querySelector('#closeMenu');

        if (navToggler && mobileMenu && closeMenu) {
          navToggler.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            navToggler.classList.add('active');
            navToggler.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
          });

          closeMenu.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            navToggler.classList.remove('active');
            navToggler.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
          });

          // Close mobile menu when clicking on a nav link
          const mobileNavLinks = element.querySelectorAll('.ucd-mobile-menu .ucd-nav-link');
          mobileNavLinks.forEach(function(link) {
            link.addEventListener('click', function() {
              mobileMenu.classList.remove('active');
              navToggler.classList.remove('active');
              navToggler.setAttribute('aria-expanded', 'false');
              document.body.style.overflow = '';
            });
          });
        }
      });
    }
  };

  /**
   * Sticky Navbar behavior.
   */
  Drupal.behaviors.stickyNavbar = {
    attach: function (context, settings) {
      const elements = once('stickyNavbar', '.ucd-navbar', context);
      
      elements.forEach(function(navbar) {
        function handleScroll() {
          if (window.scrollY > 0) {
            navbar.classList.add('sticky');
          } else {
            navbar.classList.remove('sticky');
          }
        }
        
        window.addEventListener('scroll', handleScroll);
      });
    }
  };

  /**
   * Video Player Controls behavior.
   */
  Drupal.behaviors.videoPlayer = {
    attach: function (context, settings) {
      const elements = once('videoPlayer', '#videoPlayer', context);
      
      elements.forEach(function(video) {
        const playPauseBtn = document.getElementById('playPauseBtn');
        const progressInput = document.getElementById('progressInput');
        const progressFilled = document.getElementById('progressFilled');
        const currentTimeDisplay = document.getElementById('currentTime');
        const durationDisplay = document.getElementById('duration');
        const muteBtn = document.getElementById('muteBtn');
        const volumeInput = document.getElementById('volumeInput');
        const volumeFilled = document.getElementById('volumeFilled');
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        const volumeHigh1 = document.getElementById('volumeHigh1');
        const volumeHigh2 = document.getElementById('volumeHigh2');

        // Format time helper
        function formatTime(seconds) {
          const mins = Math.floor(seconds / 60);
          const secs = Math.floor(seconds % 60);
          return mins + ':' + secs.toString().padStart(2, '0');
        }

        // Update volume icon
        function updateVolumeIcon(volume) {
          if (volumeHigh1 && volumeHigh2) {
            if (volume === 0 || video.muted) {
              volumeHigh1.style.display = 'none';
              volumeHigh2.style.display = 'none';
            } else if (volume < 0.5) {
              volumeHigh1.style.display = 'none';
              volumeHigh2.style.display = 'block';
            } else {
              volumeHigh1.style.display = 'block';
              volumeHigh2.style.display = 'block';
            }
          }
        }

        if (playPauseBtn) {
          // Play/Pause functionality
          playPauseBtn.addEventListener('click', function() {
            if (video.paused) {
              video.play();
              playPauseBtn.setAttribute('aria-label', 'Pause video');
            } else {
              video.pause();
              playPauseBtn.setAttribute('aria-label', 'Play video');
            }
          });

          // Keyboard support for video (Space to play/pause)
          video.addEventListener('keydown', function(e) {
            if (e.key === ' ') {
              e.preventDefault();
              playPauseBtn.click();
            }
          });

          // Update progress bar
          video.addEventListener('timeupdate', function() {
            const percent = (video.currentTime / video.duration) * 100;
            if (progressFilled) progressFilled.style.width = percent + '%';
            if (progressInput) {
              progressInput.value = percent;
              progressInput.setAttribute('aria-valuenow', Math.floor(percent));
              progressInput.setAttribute('aria-valuetext', 
                formatTime(video.currentTime) + ' of ' + formatTime(video.duration));
            }
            if (currentTimeDisplay) currentTimeDisplay.textContent = formatTime(video.currentTime);
          });

          // Load video metadata
          video.addEventListener('loadedmetadata', function() {
            if (durationDisplay) durationDisplay.textContent = formatTime(video.duration);
            if (progressInput) progressInput.setAttribute('aria-valuemax', Math.floor(video.duration));
          });

          // Seek functionality
          if (progressInput) {
            progressInput.addEventListener('input', function() {
              const time = (progressInput.value / 100) * video.duration;
              video.currentTime = time;
            });
          }

          // Volume control
          if (volumeInput) {
            volumeInput.addEventListener('input', function() {
              const volume = volumeInput.value / 100;
              video.volume = volume;
              if (volumeFilled) volumeFilled.style.width = volumeInput.value + '%';
              volumeInput.setAttribute('aria-valuenow', volumeInput.value);
              volumeInput.setAttribute('aria-valuetext', 'Volume ' + volumeInput.value + '%');
              updateVolumeIcon(volume);
            });
          }

          // Mute/Unmute
          if (muteBtn) {
            muteBtn.addEventListener('click', function() {
              video.muted = !video.muted;
              if (video.muted) {
                muteBtn.setAttribute('aria-label', 'Unmute');
                if (volumeFilled) volumeFilled.style.width = '0%';
                updateVolumeIcon(0);
              } else {
                muteBtn.setAttribute('aria-label', 'Mute');
                if (volumeFilled && volumeInput) volumeFilled.style.width = volumeInput.value + '%';
                updateVolumeIcon(video.volume);
              }
            });
          }

          // Fullscreen
          if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', function() {
              const container = document.querySelector('.video-container');
              if (container) {
                if (!document.fullscreenElement) {
                  container.requestFullscreen().catch(function(err) {
                    console.log('Fullscreen error:', err);
                  });
                  fullscreenBtn.setAttribute('aria-label', 'Exit fullscreen');
                } else {
                  document.exitFullscreen();
                  fullscreenBtn.setAttribute('aria-label', 'Enter fullscreen');
                }
              }
            });
          }

          // Handle fullscreen change
          document.addEventListener('fullscreenchange', function() {
            if (fullscreenBtn && !document.fullscreenElement) {
              fullscreenBtn.setAttribute('aria-label', 'Enter fullscreen');
            }
          });
        }
      });
    }
  };

  /**
   * Instagram Carousel behavior.
   */
  Drupal.behaviors.instagramCarousel = {
    attach: function (context, settings) {
      const elements = once('instagramCarousel', '.instagram-slider', context);
      
      elements.forEach(function(element) {
        const $element = $(element);
        
        if ($element.length && typeof $.fn.owlCarousel === 'function') {
          try {
            $element.owlCarousel({
              items: 3,
              loop: false,
              margin: 30,
              nav: false,
              navText: ['‹', '›'],
              dots: false,
              autoplay: true,
              autoplayTimeout: 5000,
              autoplayHoverPause: true,
              responsive: {
                0: {
                  items: 1,
                  margin: 20
                },
                768: {
                  items: 2,
                  margin: 20
                },
                1024: {
                  items: 3,
                  margin: 30
                }
              }
            });
          } catch (error) {
            console.warn('Instagram Carousel initialization failed:', error);
          }
        } else {
          console.warn('Owl Carousel not available or element not found');
        }
      });
    }
  };

  /**
   * Testimonial Carousel behavior.
   */
  Drupal.behaviors.testimonialCarousel = {
    attach: function (context, settings) {
      // Target the correct selector from the template
      const elements = once('testimonialCarousel', '#testimonial-carousel', context);
      
      elements.forEach(function(element) {
        const $element = $(element);
        
        // Debug logging
        console.log('Testimonial Carousel: Element found:', $element.length > 0);
        console.log('Testimonial Carousel: Owl Carousel available:', typeof $.fn.owlCarousel === 'function');
        
        if (!$element.length) {
          console.warn('Testimonial Carousel: No carousel element found');
          return;
        }
        
        if (typeof $.fn.owlCarousel !== 'function') {
          console.warn('Testimonial Carousel: Owl Carousel not available');
          return;
        }
        
        try {
          // Initialize Owl Carousel
          const carousel = $element.owlCarousel({
            items: 1,
            loop: true,
            nav: false,
            dots: false,
            autoplay: true,
            autoplayTimeout: 7000,
            autoplayHoverPause: true,
            animateOut: 'fadeOut',
            animateIn: 'fadeIn',
            smartSpeed: 600,
            margin: 0,
            stagePadding: 0,
            responsive: {
              0: {
                nav: false,
                dots: false
              },
              768: {
                nav: false,
                dots: false
              }
            }
          });

          console.log('Testimonial Carousel: Initialized successfully');

          // Update carousel status for screen readers
          carousel.on('changed.owl.carousel', function(event) {
            if (event && event.item) {
              const currentIndex = event.item.index - event.relativePos + 1;
              const totalItems = event.item.count;
              // $('.carousel-status').text(`Showing testimonial ${currentIndex} of ${totalItems}`);
              
              // Update dots with proper labels
              $('.owl-dot').each(function(index) {
                $(this).attr('aria-label', `Go to testimonial ${index + 1}`);
              });
            }
          });

          // Set initial carousel status after a short delay to ensure dots are rendered
          setTimeout(function() {
            // $('.carousel-status').text('Showing testimonial 1 of 2');
            
            $('.owl-dot').each(function(index) {
              $(this).attr('aria-label', `Go to testimonial ${index + 1}`);
              $(this).attr('role', 'button');
            });
          }, 100);

          // Video play/pause functionality - scoped to this carousel
          $element.find('.testimonial-play-button').off('click.testimonialVideo').on('click.testimonialVideo', function() {
            const video = $(this).siblings('video')[0];
            const overlay = $(this).siblings('.media-overlay');
            
            if (video) {
              if (video.paused) {
                // Play video
                video.play().catch(function(error) {
                  console.warn('Video play failed:', error);
                });
                $(this).addClass('playing');
                $(this).attr('aria-label', 'Pause video testimonial');
                $(this).find('.sr-only').text('Pause video');
                overlay.addClass('hidden');
                
                // Pause carousel when video is playing
                carousel.trigger('stop.owl.autoplay');
              } else {
                // Pause video
                video.pause();
                $(this).removeClass('playing');
                $(this).attr('aria-label', 'Play video testimonial');
                $(this).find('.sr-only').text('Play video');
                overlay.removeClass('hidden');
                
                // Resume carousel
                carousel.trigger('play.owl.autoplay');
              }
            }
          });

          // Keyboard support for play button
          $element.find('.testimonial-play-button').off('keydown.testimonialVideo').on('keydown.testimonialVideo', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              $(this).click();
            }
          });

          // Handle video ended event
          $element.find('.testimonial-video').off('ended.testimonialVideo').on('ended.testimonialVideo', function() {
            const playButton = $(this).siblings('.testimonial-play-button');
            const overlay = $(this).siblings('.media-overlay');
            
            playButton.removeClass('playing');
            playButton.attr('aria-label', 'Play video testimonial');
            playButton.find('.sr-only').text('Play video');
            overlay.removeClass('hidden');
            
            // Resume carousel
            carousel.trigger('play.owl.autoplay');
          });

          // Pause video when changing slides
          carousel.on('changed.owl.carousel', function(event) {
            $element.find('.testimonial-video').each(function() {
              if (!this.paused) {
                this.pause();
                this.currentTime = 0;
                $(this).siblings('.testimonial-play-button').removeClass('playing');
                $(this).siblings('.testimonial-play-button').attr('aria-label', 'Play video testimonial');
                $(this).siblings('.testimonial-play-button').find('.sr-only').text('Play video');
                $(this).siblings('.media-overlay').removeClass('hidden');
              }
            });
          });

          // Handle video click to toggle play/pause
          $element.find('.testimonial-video').off('click.testimonialVideo').on('click.testimonialVideo', function() {
            $(this).siblings('.testimonial-play-button').click();
          });

          // Keyboard navigation for carousel - scoped to this element
          $(document).off('keydown.testimonialCarousel').on('keydown.testimonialCarousel', function(e) {
            if ($(e.target).closest('#testimonial-carousel').length) {
              if (e.key === 'ArrowLeft') {
                carousel.trigger('prev.owl.carousel');
              } else if (e.key === 'ArrowRight') {
                carousel.trigger('next.owl.carousel');
              }
            }
          });
          
        } catch (error) {
          console.error('Testimonial Carousel initialization failed:', error);
        }
      });
    }
  };

  /**
   * Custom Dropdown behavior.
   */
  Drupal.behaviors.customDropdown = {
    attach: function (context, settings) {
      
      /**
       * Custom Dropdown Class
       */
      class CustomDropdown {
        constructor(element) {
          this.dropdown = element;
          this.trigger = element.querySelector('.uc-custom-dropdown-trigger');
          this.menu = element.querySelector('.uc-custom-dropdown-menu');
          this.options = element.querySelectorAll('.uc-custom-dropdown-option');
          this.select = element.querySelector('select');
          this.textElement = this.trigger ? this.trigger.querySelector('.uc-dropdown-text') : null;
          this.currentIndex = -1;
          this.boundCloseHandler = null;
          
          if (this.trigger && this.menu && this.options.length && this.select && this.textElement) {
            this.init();
          }
        }
        
        init() {
          // Toggle dropdown
          this.trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
          });
          
          // Option selection
          this.options.forEach((option, index) => {
            option.addEventListener('click', (e) => {
              e.stopPropagation();
              this.selectOption(option);
            });
            option.addEventListener('mouseenter', () => {
              this.currentIndex = index;
              this.updateFocus();
            });
          });
          
          // Keyboard navigation
          this.trigger.addEventListener('keydown', (e) => this.handleKeyboard(e));
        }
        
        toggle() {
          if (this.dropdown.classList.contains('open')) {
            this.close();
          } else {
            this.open();
          }
        }
        
        open() {
          // Close other dropdowns
          document.querySelectorAll('.uc-custom-dropdown.open').forEach(dd => {
            if (dd !== this.dropdown) {
              dd.classList.remove('open');
            }
          });
          
          this.dropdown.classList.add('open');
          this.trigger.setAttribute('aria-expanded', 'true');
          this.currentIndex = -1;
          
          // Focus first option
          if (this.options.length > 0) {
            this.currentIndex = 0;
            this.updateFocus();
          }

          // Add click outside listener only when open
          setTimeout(() => {
            this.boundCloseHandler = (e) => this.handleClickOutside(e);
            document.addEventListener('click', this.boundCloseHandler);
          }, 0);
        }
        
        close() {
          this.dropdown.classList.remove('open');
          this.trigger.setAttribute('aria-expanded', 'false');
          
          // Remove click outside listener
          if (this.boundCloseHandler) {
            document.removeEventListener('click', this.boundCloseHandler);
            this.boundCloseHandler = null;
          }
        }

        handleClickOutside(e) {
          if (!this.dropdown.contains(e.target)) {
            this.close();
          }
        }
        
        selectOption(option) {
          const value = option.dataset.value;
          const text = option.textContent;
          
          // Update display
          this.textElement.textContent = text;
          this.trigger.classList.remove('placeholder');
          
          // Update hidden select
          this.select.value = value;
          
          // Update selected state
          this.options.forEach(opt => opt.classList.remove('selected'));
          option.classList.add('selected');
          
          this.close();
        }
        
        handleKeyboard(e) {
          if (!this.dropdown.classList.contains('open')) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
              e.preventDefault();
              this.open();
            }
            return;
          }
          
          switch(e.key) {
            case 'ArrowDown':
              e.preventDefault();
              this.currentIndex = Math.min(this.currentIndex + 1, this.options.length - 1);
              this.updateFocus();
              break;
            case 'ArrowUp':
              e.preventDefault();
              this.currentIndex = Math.max(this.currentIndex - 1, 0);
              this.updateFocus();
              break;
            case 'Enter':
            case ' ':
              e.preventDefault();
              if (this.currentIndex >= 0) {
                this.selectOption(this.options[this.currentIndex]);
              }
              break;
            case 'Escape':
              e.preventDefault();
              this.close();
              break;
          }
        }
        
        updateFocus() {
          this.options.forEach((option, index) => {
            if (index === this.currentIndex) {
              option.setAttribute('tabindex', '0');
              option.focus({ preventScroll: true });
            } else {
              option.setAttribute('tabindex', '-1');
            }
          });
        }
      }

      // Initialize all custom dropdowns
      const elements = once('customDropdown', '.uc-custom-dropdown', context);
      
      elements.forEach(function(dropdown) {
        new CustomDropdown(dropdown);
      });
    }
  };

  /**
   * Modal functionality behavior.
   */
  Drupal.behaviors.modalFunctionality = {
    attach: function (context, settings) {
      const elements = once('modalFunctionality', 'body', context);
      
      elements.forEach(function(element) {
        const modalOverlay = element.querySelector('#modalOverlay');
        const openModalBtn = element.querySelector('#openModal');
        const closeModalBtn = element.querySelector('#closeModal');
        const signupForm = element.querySelector('#signupForm');

        if (!modalOverlay || !openModalBtn || !closeModalBtn || !signupForm) {
          return;
        }

        // Close modal function
        function closeModal() {
          modalOverlay.classList.remove('active');
          document.body.style.overflow = '';
          openModalBtn.focus({ preventScroll: true });
        }

        // Open modal
        openModalBtn.addEventListener('click', () => {
          modalOverlay.classList.add('active');
          document.body.style.overflow = 'hidden';
          setTimeout(() => {
            modalOverlay.scrollTop = 0;
            closeModalBtn.focus({ preventScroll: true });
          }, 10);
        });

        // Close modal
        closeModalBtn.addEventListener('click', closeModal);

        // Close on overlay click
        modalOverlay.addEventListener('click', (e) => {
          if (e.target === modalOverlay) {
            closeModal();
          }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape' && modalOverlay.classList.contains('active')) {
            closeModal();
          }
        });

        // Form submission
        signupForm.addEventListener('submit', (e) => {
          e.preventDefault();
          
          // Get form data
          const formData = new FormData(signupForm);
          const data = Object.fromEntries(formData);
          
          console.log('Form submitted:', data);
          
          // Show success message
          alert('Thank you for signing up! We\'ll be in touch soon.');
          
          // Reset form and close modal
          signupForm.reset();
          closeModal();
        });

        // Trap focus within modal when open
        modalOverlay.addEventListener('keydown', (e) => {
          if (!modalOverlay.classList.contains('active')) return;
          
          if (e.key === 'Tab') {
            const focusableElements = modalOverlay.querySelectorAll(
              'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (e.shiftKey && document.activeElement === firstElement) {
              e.preventDefault();
              lastElement.focus({ preventScroll: true });
            } else if (!e.shiftKey && document.activeElement === lastElement) {
              e.preventDefault();
              firstElement.focus({ preventScroll: true });
            }
          }
        });
      });
    }
  };

})(jQuery, Drupal, once);