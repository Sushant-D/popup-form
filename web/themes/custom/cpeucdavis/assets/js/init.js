 const navToggler = document.getElementById('navToggler');
        const mobileMenu = document.getElementById('mobileMenu');
        const closeMenu = document.getElementById('closeMenu');

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
        const mobileNavLinks = document.querySelectorAll('.mobile-menu .nav-link');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileMenu.classList.remove('active');
                navToggler.classList.remove('active');
                navToggler.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            });
        });

        // Optional: Make navbar sticky on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 0) {
                navbar.classList.add('sticky');
            } else {
                navbar.classList.remove('sticky');
            }
        });

const video = document.getElementById('videoPlayer');
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
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }

        // Play/Pause functionality
        playPauseBtn.addEventListener('click', () => {
            if (video.paused) {
                video.play();
                playPauseBtn.setAttribute('aria-label', 'Pause video');
            } else {
                video.pause();
                playPauseBtn.setAttribute('aria-label', 'Play video');
            }
        });

        // Keyboard support for video (Space to play/pause)
        video.addEventListener('keydown', (e) => {
            if (e.key === ' ') {
                e.preventDefault();
                playPauseBtn.click();
            }
        });

        // Update progress bar
        video.addEventListener('timeupdate', () => {
            const percent = (video.currentTime / video.duration) * 100;
            progressFilled.style.width = percent + '%';
            progressInput.value = percent;
            progressInput.setAttribute('aria-valuenow', Math.floor(percent));
            progressInput.setAttribute('aria-valuetext', 
                `${formatTime(video.currentTime)} of ${formatTime(video.duration)}`);
            currentTimeDisplay.textContent = formatTime(video.currentTime);
        });

        // Load video metadata
        video.addEventListener('loadedmetadata', () => {
            durationDisplay.textContent = formatTime(video.duration);
            progressInput.setAttribute('aria-valuemax', Math.floor(video.duration));
        });

        // Seek functionality
        progressInput.addEventListener('input', () => {
            const time = (progressInput.value / 100) * video.duration;
            video.currentTime = time;
        });

        // Volume control
        volumeInput.addEventListener('input', () => {
            const volume = volumeInput.value / 100;
            video.volume = volume;
            volumeFilled.style.width = volumeInput.value + '%';
            volumeInput.setAttribute('aria-valuenow', volumeInput.value);
            volumeInput.setAttribute('aria-valuetext', `Volume ${volumeInput.value}%`);
            
            updateVolumeIcon(volume);
        });

        // Mute/Unmute
        muteBtn.addEventListener('click', () => {
            video.muted = !video.muted;
            if (video.muted) {
                muteBtn.setAttribute('aria-label', 'Unmute');
                volumeFilled.style.width = '0%';
                updateVolumeIcon(0);
            } else {
                muteBtn.setAttribute('aria-label', 'Mute');
                volumeFilled.style.width = volumeInput.value + '%';
                updateVolumeIcon(video.volume);
            }
        });

        // Update volume icon
        function updateVolumeIcon(volume) {
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

        // Fullscreen
        fullscreenBtn.addEventListener('click', () => {
            const container = document.querySelector('.video-container');
            if (!document.fullscreenElement) {
                container.requestFullscreen().catch(err => {
                    console.log('Fullscreen error:', err);
                });
                fullscreenBtn.setAttribute('aria-label', 'Exit fullscreen');
            } else {
                document.exitFullscreen();
                fullscreenBtn.setAttribute('aria-label', 'Enter fullscreen');
            }
        });

        // Handle fullscreen change
        document.addEventListener('fullscreenchange', () => {
            if (!document.fullscreenElement) {
                fullscreenBtn.setAttribute('aria-label', 'Enter fullscreen');
            }
        });