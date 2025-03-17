    // Video slider functionality
    const videoContainer = document.querySelector('.video-container');
    const indicators = document.querySelectorAll('.indicator');
    let sequence = [0, 1, 2, 1];
    let index = 0;
    function updateIndicator() {
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === sequence[index]);
        });
    }
    function autoSlide() {
        index = (index + 1) % sequence.length;
        videoContainer.scrollTo({
            left: sequence[index] * videoContainer.clientWidth,
            behavior: 'smooth'
        });
        updateIndicator();
    }
    setInterval(autoSlide, 7000);
    indicators.forEach(indicator => {
        indicator.addEventListener('click', () => {
            index = sequence.indexOf(parseInt(indicator.getAttribute('data-index')));
            videoContainer.scrollTo({
                left: sequence[index] * videoContainer.clientWidth,
                behavior: 'smooth'
            });
            updateIndicator();
        });
    });
    
    // Mobile navigation
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
    });
    document.querySelectorAll('.nav-menu a').forEach(link => {
        link.addEventListener('click', () => {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });
    
    // Scroll animation
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
            }
        });
    });
    const hiddenElements = document.querySelectorAll('.about-text, .about-image');
    hiddenElements.forEach(el => observer.observe(el));
    
// Feature cards horizontal scroll functionality
document.addEventListener('DOMContentLoaded', function() {
    // Clone feature cards for infinite scrolling effect
    const featuresGrid = document.querySelector('.features-grid');
    const cards = document.querySelectorAll('.feature-card');
    
    // Clone each card and append to the grid
    cards.forEach(card => {
        const clone = card.cloneNode(true);
        featuresGrid.appendChild(clone);
    });
    
    // Set initial position
    let position = 0;
    const cardWidth = cards[0].offsetWidth + 30; // Card width + gap
    const totalOriginalWidth = cardWidth * cards.length;
    
    // Create the scrolling animation - slower speed
    function scrollFeatures() {
        position -= 0.5; // Very slow speed (smaller value = slower)
        
        // Reset position when needed for infinite loop
        if (position <= -totalOriginalWidth) {
            position = 0;
        }
        
        featuresGrid.style.transform = `translateX(${position}px)`;
        requestAnimationFrame(scrollFeatures);
    }
    
    // Start the animation
    requestAnimationFrame(scrollFeatures);
});