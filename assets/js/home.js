/**
 * AI Study Hub - Trang chủ (Home Page)
 * Mobile Menu + Active Menu + Sticky Header
 */

document.addEventListener('DOMContentLoaded', () => {

    // =========================
    // Mobile Menu Toggle
    // =========================
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const authButtons = document.querySelector('.auth-buttons');

    if (mobileMenuToggle) {

        const icon = mobileMenuToggle.querySelector('i');

        mobileMenuToggle.addEventListener('click', () => {

            navMenu.classList.toggle('active');
            authButtons.classList.toggle('active');

            if (navMenu.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-xmark');
            } else {
                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars');
            }

        });

    }

    // =========================
    // Active Navigation Link
    // =========================
    const navLinks = document.querySelectorAll('.nav-list a');

    navLinks.forEach(link => {

        link.addEventListener('click', function () {

            navLinks.forEach(item => {
                item.classList.remove('active');
            });

            this.classList.add('active');

            // Đóng menu mobile sau khi click
            if (window.innerWidth <= 768) {

                navMenu.classList.remove('active');
                authButtons.classList.remove('active');

                const icon =
                    document.querySelector('.mobile-menu-toggle i');

                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars');
            }

        });

    });

    // =========================
    // Sticky Header Scroll
    // =========================
    const header = document.querySelector('.header');

    window.addEventListener('scroll', () => {

        if (window.scrollY > 50) {

            header.style.boxShadow =
                '0 8px 25px rgba(0,0,0,0.08)';

            header.style.background =
                'rgba(255,255,255,0.95)';

            header.style.backdropFilter =
                'blur(12px)';

        } else {

            header.style.boxShadow = 'none';

            header.style.background =
                'rgba(255,255,255,0.90)';

        }

    });

});