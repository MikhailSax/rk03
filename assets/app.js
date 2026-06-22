import './bootstrap.js';
import './styles/app.css';
import './js/header';
import './js/auth/register';
import {initPageMotion} from './js/motion';
import {initHomeMosaicLazy} from './js/home-mosaic';

import Swiper from 'swiper';
import {Navigation, Pagination} from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

// Vue
import {createApp} from 'vue';

import App from './components/App.vue';
import CartPage from './components/CartPage.vue';
import AdvertisementDetailPage from './components/AdvertisementDetailPage.vue';

// Простая инициализация Vue
function initVue() {
    const mapElement = document.getElementById('map-app');
    if (mapElement && !mapElement._vueApp) {
        try {
            const app = createApp(App, {
                filtersUrl: mapElement.dataset.filtersUrl || '/api/filters',
                advertisementsUrl: mapElement.dataset.advertisementsUrl || '/api/advertisements',
                ordersUrl: mapElement.dataset.ordersUrl || '/api/orders',
                cartUrl: mapElement.dataset.cartUrl || '/api/cart',
                authUser: {
                    isAuthenticated: mapElement.dataset.isAuthenticated === '1',
                    id: mapElement.dataset.userId ? Number(mapElement.dataset.userId) : null,
                    name: mapElement.dataset.userName || '',
                    phone: mapElement.dataset.userPhone || '',
                    email: mapElement.dataset.userEmail || '',
                },
            });
            app.mount('#map-app');
            mapElement._vueApp = app;
        } catch (error) {
            console.error('Vue app mounting error:', error);
        }
    }

    const cartPageElement = document.getElementById('cart-page-app');
    if (cartPageElement && !cartPageElement._vueApp) {
        try {
            const app = createApp(CartPage, {
                cartUrl: cartPageElement.dataset.cartUrl || '/api/cart',
                ordersUrl: cartPageElement.dataset.ordersUrl || '/api/orders',
                authUser: {
                    isAuthenticated: cartPageElement.dataset.isAuthenticated === '1',
                    id: cartPageElement.dataset.userId ? Number(cartPageElement.dataset.userId) : null,
                    name: cartPageElement.dataset.userName || '',
                    phone: cartPageElement.dataset.userPhone || '',
                    email: cartPageElement.dataset.userEmail || '',
                },
            });
            app.mount('#cart-page-app');
            cartPageElement._vueApp = app;
        } catch (error) {
            console.error('Cart page mounting error:', error);
        }
    }

    const advertisementDetailEl = document.getElementById('advertisement-detail-app');
    if (advertisementDetailEl && !advertisementDetailEl._vueApp) {
        try {
            const apiUrl = advertisementDetailEl.dataset.advertisementApiUrl || '';
            if (!apiUrl) {
                console.error('advertisement-detail-app: missing data-advertisement-api-url');
            } else {
                const app = createApp(AdvertisementDetailPage, {
                    advertisementApiUrl: apiUrl,
                    cartUrl: advertisementDetailEl.dataset.cartUrl || '/api/cart',
                });
                app.mount('#advertisement-detail-app');
                advertisementDetailEl._vueApp = app;
            }
        } catch (error) {
            console.error('Advertisement detail page mounting error:', error);
        }
    }
}

// Инициализация Swiper
function initSwiper() {
    const swiperElements = document.querySelectorAll('.default-carousel');
    swiperElements.forEach((element) => {
        if (!element._swiper) {
            try {
                const swiper = new Swiper(element, {
                    modules: [Navigation, Pagination],
                    speed: 400,
                    spaceBetween: 100,
                    navigation: {
                        nextEl: element.querySelector('.swiper-button-next'),
                        prevEl: element.querySelector('.swiper-button-prev'),
                    },
                    pagination: {
                        el: element.querySelector('.swiper-pagination'),
                        clickable: true,
                    },
                });
                element._swiper = swiper;
            } catch (error) {
                console.error('Swiper initialization error:', error);
            }
        }
    });
}

// Основная функция инициализации
function initApp() {
    initVue();
    initSwiper();
    initHomeMosaicLazy();
    initPageMotion();
}

// Обработчики событий
document.addEventListener('DOMContentLoaded', initApp);
document.addEventListener('turbo:load', initApp);

// Очистка перед переходом Turbo
document.addEventListener('turbo:before-render', () => {
    const mapElement = document.getElementById('map-app');
    if (mapElement && mapElement._vueApp) {
        mapElement._vueApp.unmount();
        mapElement._vueApp = null;
    }

    const cartPageElement = document.getElementById('cart-page-app');
    if (cartPageElement && cartPageElement._vueApp) {
        cartPageElement._vueApp.unmount();
        cartPageElement._vueApp = null;
    }
});


(function () {
    var rhs = document.getElementById('hero-rhs');
    if (!rhs) return;

    var INTERVAL = 5000;
    var slides = rhs.querySelectorAll('.hs');
    var caps = rhs.querySelectorAll('.hs-cap');
    var dotsEl = document.getElementById('hero-dots');
    var pb = document.getElementById('hero-pb');
    var cur = 0, timer, pbRaf, pbStart;

    /* Создаём точки */
    Array.prototype.forEach.call(slides, function (_, i) {
        var d = document.createElement('button');
        d.className = 'hero-dot';
        d.setAttribute('aria-label', 'Слайд ' + (i + 1));
        d.addEventListener('click', function () {
            go(i);
        });
        dotsEl.appendChild(d);
    });
    var dots = dotsEl.querySelectorAll('.hero-dot');

    function activate(i) {
        slides[cur].style.opacity = '0';
        caps[cur].style.opacity = '0';
        caps[cur].style.transform = 'translateY(10px)';
        dots[cur].classList.remove('on');

        cur = (i + slides.length) % slides.length;

        slides[cur].style.opacity = '1';
        setTimeout(function () {
            caps[cur].style.opacity = '1';
            caps[cur].style.transform = 'translateY(0)';
        }, 360);
        dots[cur].classList.add('on');
    }

    function runPb() {
        cancelAnimationFrame(pbRaf);
        pbStart = null;
        pb.style.transition = 'none';
        pb.style.width = '0%';

        function tick(ts) {
            if (!pbStart) pbStart = ts;
            var p = Math.min((ts - pbStart) / INTERVAL * 100, 100);
            pb.style.width = p + '%';
            if (p < 100) pbRaf = requestAnimationFrame(tick);
        }

        pbRaf = requestAnimationFrame(tick);
    }

    function go(i) {
        clearTimeout(timer);
        cancelAnimationFrame(pbRaf);
        activate(i);
        runPb();
        timer = setTimeout(function () {
            go(cur + 1);
        }, INTERVAL);
    }

    /* Инициализация */
    slides[0].style.opacity = '1';
    caps[0].style.opacity = '1';
    caps[0].style.transform = 'translateY(0)';
    dots[0].classList.add('on');
    runPb();
    timer = setTimeout(function () {
        go(1);
    }, INTERVAL);

    /* Счётчики */
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = +el.dataset.count;
        var start = null;

        function step(ts) {
            if (!start) start = ts;
            var p = Math.min((ts - start) / 1200, 1);
            el.textContent = Math.round(p * target);
            if (p < 1) requestAnimationFrame(step);
        }

        requestAnimationFrame(step);
    });
})();
