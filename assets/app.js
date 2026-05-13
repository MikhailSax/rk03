import './bootstrap.js';
import './styles/app.css';
import './js/header';
import './js/auth/register';
import { initPageMotion } from './js/motion';
import { initHomeMosaicLazy } from './js/home-mosaic';

import Swiper from 'swiper';
import { Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

// Vue
import { createApp } from 'vue';

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
