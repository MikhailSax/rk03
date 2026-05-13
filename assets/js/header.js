function initHeaderInteractions() {
    // Выпадающие меню по клику
    document.querySelectorAll('.dropdown').forEach((dropdown) => {
        if (dropdown.dataset.headerBound === '1') return;

        const toggle = dropdown.querySelector('.dropdown-toggle');
        const menu = dropdown.querySelector('.dropdown-menu');
        if (!toggle || !menu) return;

        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            document.querySelectorAll('.dropdown-menu').forEach((openedMenu) => {
                if (openedMenu !== menu) openedMenu.classList.add('hidden');
            });
            menu.classList.toggle('hidden');
        });

        menu.addEventListener('click', (e) => e.stopPropagation());
        dropdown.dataset.headerBound = '1';
    });

    if (!document.body.dataset.headerOutsideBound) {
        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown-menu').forEach((menu) => {
                menu.classList.add('hidden');
            });
        });
        document.body.dataset.headerOutsideBound = '1';
    }

    // Мобильное меню (бургер)
    const mobileBtn = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileBtn && mobileMenu && mobileBtn.dataset.headerBound !== '1') {
        mobileBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        mobileMenu.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => mobileMenu.classList.add('hidden'));
        });

        mobileBtn.dataset.headerBound = '1';
    }
}

document.addEventListener('DOMContentLoaded', initHeaderInteractions);
document.addEventListener('turbo:load', initHeaderInteractions);
