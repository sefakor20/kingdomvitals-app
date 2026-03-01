import Chart from 'chart.js/auto';
import { Html5Qrcode } from 'html5-qrcode';

// Make Chart available globally for Alpine.js components
window.Chart = Chart;

// Make Html5Qrcode available globally for Alpine.js components
window.Html5Qrcode = Html5Qrcode;

// Scroll reveal animation using IntersectionObserver
document.addEventListener('DOMContentLoaded', () => {
    const scrollRevealElements = document.querySelectorAll('.scroll-reveal');

    if (scrollRevealElements.length === 0) return;

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px',
        }
    );

    scrollRevealElements.forEach((el) => {
        observer.observe(el);
    });
});
