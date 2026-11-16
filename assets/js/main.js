
document.addEventListener('DOMContentLoaded', () => {

    // =========================================
    // MAISONÉ — MOBILE NAVIGATION
    // =========================================

    const menuButton = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('.navigation');

    if (menuButton && navigation) {

        menuButton.setAttribute('aria-expanded', 'false');

        menuButton.addEventListener('click', () => {

            navigation.classList.toggle('active');

            const isOpen = navigation.classList.contains('active');

            menuButton.setAttribute('aria-expanded', String(isOpen));

        });

        navigation.querySelectorAll('a').forEach((link) => {

            link.addEventListener('click', () => {

                navigation.classList.remove('active');
                menuButton.setAttribute('aria-expanded', 'false');

            });

        });

    }


    // =========================================
    // MAISONÉ — CHECKOUT QUANTITY SYSTEM
    // =========================================

    document.addEventListener('click', async (event) => {

        const button = event.target.closest('.summary-qty-btn');

        if (!button) return;

        const item = button.closest('.summary-item');

        if (!item) return;

        const quantityElement = item.querySelector('.summary-qty');

        if (!quantityElement) return;

        const productId = item.dataset.productId;
        const currentQuantity = parseInt(quantityElement.textContent, 10);

        if (!productId || Number.isNaN(currentQuantity)) return;

        let newQuantity = currentQuantity;

        if (button.dataset.action === 'plus') {
            newQuantity++;
        } else if (button.dataset.action === 'minus') {
            newQuantity--;
        }

        if (newQuantity < 1) return;

        button.disabled = true;

        try {

            const formData = new FormData();

            formData.append('product_id', productId);
            formData.append('quantity', newQuantity);

            const response = await fetch(
                '/MAISONE/pages/update-checkout-quantity.php',
                {
                    method: 'POST',
                    body: formData
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Update failed');
            }

            quantityElement.textContent = data.quantity;

            const subtotalElement = item.querySelector('.summary-subtotal');

            if (subtotalElement) {
                subtotalElement.textContent =
                    '€' + Number(data.subtotal).toFixed(2);
            }

            const totalElement = document.querySelector(
                '.checkout-grand-total'
            );

            if (totalElement) {
                totalElement.textContent =
                    '€' + Number(data.total).toFixed(2);
            }

        } catch (error) {

            console.error('Checkout quantity error:', error);
            alert(error.message || 'Something went wrong.');

        } finally {

            button.disabled = false;

        }

    });


    // =========================================
    // MAISONÉ — MOTION SETTINGS
    // =========================================

    const prefersReducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)'
    ).matches;

    if (prefersReducedMotion) return;


    // =========================================
    // MAISONÉ — CINEMATIC PAGE ENTRANCE
    // =========================================

    const entranceElements = document.querySelectorAll(
        'header, main > *, footer'
    );

    entranceElements.forEach((element, index) => {

        element.classList.add('page-enter');

        element.style.setProperty(
            '--enter-delay',
            `${Math.min(index * 100, 500)}ms`
        );

    });

    requestAnimationFrame(() => {

        document.body.classList.add('page-ready');

    });


    // =========================================
    // MAISONÉ — SCROLL REVEAL
    // =========================================

    if (!('IntersectionObserver' in window)) return;

    const revealSelectors = [
        'main .section-heading',
        'main .category-card',
        'main .product-card',
        'main .brand-message',
        'main .product-detail-info',
        'main .shop-heading',
        'main .cart-heading',
        'main .checkout-heading'
    ];

    const revealElements = document.querySelectorAll(
        revealSelectors.join(',')
    );

    console.log(
        'MAISONÉ Reveal Elements:',
        revealElements.length
    );

    const observer = new IntersectionObserver(
        (entries, observer) => {

            entries.forEach((entry) => {

                if (entry.isIntersecting) {

                    entry.target.classList.add('is-visible');

                    observer.unobserve(entry.target);

                }

            });

        },
        {
            threshold: 0.08,
            rootMargin: '0px 0px -30px 0px'
        }
    );

    revealElements.forEach((element, index) => {

        if (element.closest('.motion-reveal')) return;

        element.classList.add('motion-reveal');

        if (
            element.classList.contains('product-card') ||
            element.classList.contains('category-card')
        ) {

            element.style.transitionDelay =
                `${(index % 4) * 100}ms`;

        }

        observer.observe(element);

    });

});
