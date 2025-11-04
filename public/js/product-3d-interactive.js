/* ═══════════════════════════════════════════════════════════════
   COPRRA - 3D Interactive Product Effects (JavaScript)
   Advanced Mouse Interaction & Dynamic Effects
   ═══════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', function() {

    // ────────────────────────────────────────────────────────────
    // 1. Interactive Tilt Effect (Mouse Following)
    // ────────────────────────────────────────────────────────────
    const productCards = document.querySelectorAll('.product-card-3d');

    productCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;

            const cardElement = card.querySelector('.card');
            if (cardElement) {
                cardElement.style.transform = `
                    rotateX(${rotateX}deg)
                    rotateY(${rotateY}deg)
                    translateZ(10px)
                `;
            }
        });

        card.addEventListener('mouseleave', () => {
            const cardElement = card.querySelector('.card');
            if (cardElement) {
                cardElement.style.transform = 'rotateX(0) rotateY(0) translateZ(0)';
            }
        });
    });

    // ────────────────────────────────────────────────────────────
    // 2. Dynamic Spotlight Following Mouse
    // ────────────────────────────────────────────────────────────
    const spotlightContainers = document.querySelectorAll('.spotlight-container');

    spotlightContainers.forEach(container => {
        container.addEventListener('mousemove', (e) => {
            const rect = container.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;

            const spotlight = container.querySelector('::before');
            container.style.setProperty('--spotlight-x', `${x}%`);
            container.style.setProperty('--spotlight-y', `${y}%`);
        });
    });

    // ────────────────────────────────────────────────────────────
    // 3. Parallax Effect on Product Hero Image
    // ────────────────────────────────────────────────────────────
    const productHero = document.querySelector('.product-hero-3d');

    if (productHero) {
        window.addEventListener('mousemove', (e) => {
            const x = (e.clientX / window.innerWidth - 0.5) * 20;
            const y = (e.clientY / window.innerHeight - 0.5) * 20;

            const heroImage = productHero.querySelector('.product-hero-image');
            if (heroImage) {
                heroImage.style.transform = `
                    rotateY(${x}deg)
                    rotateX(${-y}deg)
                    translateZ(50px)
                `;
            }
        });
    }

    // ────────────────────────────────────────────────────────────
    // 4. Lazy Load with Fade-in Animation
    // ────────────────────────────────────────────────────────────
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '50px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('product-card-enter');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    productCards.forEach(card => {
        card.style.opacity = '0';
        observer.observe(card);
    });

    // ────────────────────────────────────────────────────────────
    // 5. Image Magnifier on Hover (Product Detail Page)
    // ────────────────────────────────────────────────────────────
    const productImage = document.querySelector('.product-hero-image img');

    if (productImage) {
        const magnifier = document.createElement('div');
        magnifier.className = 'image-magnifier';
        magnifier.style.cssText = `
            position: absolute;
            width: 200px;
            height: 200px;
            border: 3px solid rgba(59, 130, 246, 0.5);
            border-radius: 50%;
            background-repeat: no-repeat;
            background-size: 400%;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 100;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        `;

        const container = productImage.parentElement;
        container.style.position = 'relative';
        container.appendChild(magnifier);

        productImage.addEventListener('mouseenter', () => {
            magnifier.style.backgroundImage = `url('${productImage.src}')`;
            magnifier.style.opacity = '1';
        });

        productImage.addEventListener('mousemove', (e) => {
            const rect = productImage.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            magnifier.style.left = (x - 100) + 'px';
            magnifier.style.top = (y - 100) + 'px';

            const bgX = (x / rect.width) * 100;
            const bgY = (y / rect.height) * 100;

            magnifier.style.backgroundPosition = `${bgX}% ${bgY}%`;
        });

        productImage.addEventListener('mouseleave', () => {
            magnifier.style.opacity = '0';
        });
    }

    // ────────────────────────────────────────────────────────────
    // 6. Smooth Scroll Parallax
    // ────────────────────────────────────────────────────────────
    let ticking = false;

    function updateParallax() {
        const scrolled = window.pageYOffset;
        const parallaxElements = document.querySelectorAll('[data-parallax]');

        parallaxElements.forEach(el => {
            const speed = el.dataset.parallax || 0.5;
            const yPos = -(scrolled * speed);
            el.style.transform = `translateY(${yPos}px) translateZ(20px)`;
        });

        ticking = false;
    }

    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(updateParallax);
            ticking = true;
        }
    });

    // ────────────────────────────────────────────────────────────
    // 7. Product Card Magnetic Effect
    // ────────────────────────────────────────────────────────────
    productCards.forEach(card => {
        const magneticElements = card.querySelectorAll('.btn-3d');

        magneticElements.forEach(btn => {
            btn.addEventListener('mousemove', (e) => {
                const rect = btn.getBoundingClientRect();
                const x = e.clientX - rect.left - rect.width / 2;
                const y = e.clientY - rect.top - rect.height / 2;

                btn.style.transform = `
                    translate(${x * 0.3}px, ${y * 0.3}px)
                    translateZ(20px)
                `;
            });

            btn.addEventListener('mouseleave', () => {
                btn.style.transform = 'translate(0, 0) translateZ(0)';
            });
        });
    });

    // ────────────────────────────────────────────────────────────
    // 8. Initialize AOS (Animate On Scroll) if available
    // ────────────────────────────────────────────────────────────
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out-cubic',
            once: true,
            offset: 100
        });
    }

    // ────────────────────────────────────────────────────────────
    // 9. Performance Monitor
    // ────────────────────────────────────────────────────────────
    if (window.performance && window.performance.memory) {
        console.log('🎨 3D Effects initialized');
        console.log('💾 Memory usage:',
            Math.round(window.performance.memory.usedJSHeapSize / 1048576), 'MB');
    }

    // ────────────────────────────────────────────────────────────
    // 10. Disable Heavy Effects on Low-End Devices
    // ────────────────────────────────────────────────────────────
    const isLowEndDevice = () => {
        return navigator.hardwareConcurrency <= 2 ||
               navigator.deviceMemory <= 2 ||
               /Android.*Mobile/.test(navigator.userAgent);
    };

    if (isLowEndDevice()) {
        console.log('⚡ Low-end device detected - reducing effects');
        document.body.classList.add('reduced-effects');

        // Disable heavy animations
        document.querySelectorAll('.product-rotating, .product-image-floating')
            .forEach(el => {
                el.style.animation = 'none';
            });
    }
});

/* ═══════════════════════════════════════════════════════════════
   Utility Functions
   ═══════════════════════════════════════════════════════════════ */

// Add 3D class to all product cards automatically
function init3DProductCards() {
    const cards = document.querySelectorAll('.product-item, .product-card');
    cards.forEach(card => {
        if (!card.classList.contains('product-card-3d')) {
            card.classList.add('product-card-3d');
        }
    });
}

// Initialize on page load
window.addEventListener('load', init3DProductCards);
