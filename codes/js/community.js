document.addEventListener("DOMContentLoaded", function () {
    // Meal sharing form toggle
    const showFormBtn = document.getElementById("showShareForm");
    const shareMealForm = document.getElementById("shareMealForm");
    const cancelShareBtn = document.getElementById("cancelShare");

    showFormBtn.addEventListener("click", function () {
        shareMealForm.style.display = "block";
        showFormBtn.style.display = "none";
    });

    cancelShareBtn.addEventListener("click", function () {
        shareMealForm.style.display = "none";
        showFormBtn.style.display = "block";
    });

    // Progress bar animation
    function animateProgressBars() {
        const progressBars = document.querySelectorAll(".progress-bar");
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const targetWidth = entry.target.getAttribute("data-width");
                    entry.target.style.width = "0";
                    entry.target.offsetWidth; // Trigger reflow
                    setTimeout(() => {
                        entry.target.style.width = targetWidth;
                    }, 200);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        progressBars.forEach((bar) => observer.observe(bar));
    }

    // Carousel functionality
    const carouselWrapper = document.querySelector(".meal-carousel-container");
    const carousel = document.querySelector(".meal-carousel");
    const prevBtn = document.querySelector(".prev-btn");
    const nextBtn = document.querySelector(".next-btn");

    if (carousel && prevBtn && nextBtn) {
        prevBtn.addEventListener("click", () => {
            carousel.scrollBy({ left: -300, behavior: "smooth" });
        });

        nextBtn.addEventListener("click", () => {
            carousel.scrollBy({ left: 300, behavior: "smooth" });
        });

        function updateButtonVisibility() {
            prevBtn.style.display = carousel.scrollLeft > 0 ? "flex" : "none";
            nextBtn.style.display =
                carousel.scrollLeft + carousel.clientWidth < carousel.scrollWidth
                    ? "flex"
                    : "none";
        }

        carousel.addEventListener("scroll", updateButtonVisibility);
        window.addEventListener("resize", updateButtonVisibility);

        // Ensure buttons are updated on load
        updateButtonVisibility();
    } else {
        console.error("Carousel elements not found.");
    }

    // Start progress bar animations
    animateProgressBars();
});