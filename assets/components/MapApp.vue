<template>
    <div class="flex min-h-0 flex-1 flex-col bg-[#ececee] text-gray-900 lg:overflow-hidden">
        <header class="relative shrink-0 overflow-hidden bg-gradient-to-br from-[#05299E] via-[#05299E] to-[#041d6b] text-white">
            <div class="pointer-events-none absolute inset-0 opacity-40 bg-[radial-gradient(ellipse_70%_80%_at_100%_0%,rgba(232,93,76,0.35),transparent)]"></div>
            <div class="relative mx-auto max-w-[1600px] px-4 py-5 sm:px-6 sm:py-6 lg:px-10 lg:py-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="site-header-font text-[10px] font-semibold uppercase tracking-[0.32em] text-white/50">Каталог и карта</p>
                        <h1 class="home-display home-display-tight mt-2 max-w-3xl text-[clamp(1.65rem,3.5vw,2.5rem)] font-bold uppercase leading-tight tracking-tight text-white">
                            Найдите
                            <span class="text-[0.92em] font-normal normal-case italic text-[#e85d4c]">идеальные</span>
                            конструкции
                        </h1>
                        <p class="mt-3 max-w-xl text-sm leading-relaxed text-white/70 sm:text-base">
                            Фильтруйте по типу продукции и формату, смотрите доступность по датам и открывайте карточку на карте.
                        </p>
                    </div>
                    <a
                        href="/cart"
                        class="site-header-font mt-1 hidden shrink-0 border border-white/30 bg-white/10 px-3 py-2 text-[10px] font-semibold uppercase tracking-[0.15em] text-white transition hover:border-white/60 hover:bg-white/20 lg:block"
                    >
                        Корзина ({{ cartItems.length }})
                    </a>
                </div>
            </div>
        </header>

        <div class="sticky top-16 z-20 flex gap-0 border-b border-gray-200 bg-white shadow-sm lg:hidden">
            <button
                type="button"
                class="site-header-font flex-1 border-b-[3px] px-5 py-4 text-[13px] font-semibold uppercase tracking-[0.14em] transition"
                :class="mobileView === 'list' ? 'border-[#e85d4c] bg-white text-[#05299E]' : 'border-transparent bg-gray-50 text-gray-500'"
                @click="mobileView = 'list'"
            >
                Фильтры
            </button>
            <button
                type="button"
                class="site-header-font flex-1 border-b-[3px] px-5 py-4 text-[13px] font-semibold uppercase tracking-[0.14em] transition"
                :class="mobileView === 'map' ? 'border-[#e85d4c] bg-white text-[#05299E]' : 'border-transparent bg-gray-50 text-gray-500'"
                @click="mobileView = 'map'"
            >
                Карта
            </button>
        </div>

        <div class="flex min-h-0 flex-1 flex-col lg:flex-row lg:overflow-hidden">
            <aside
                class="w-full border-b border-gray-200 bg-white lg:w-[min(100%,320px)] xl:w-[360px] lg:shrink-0 lg:border-b-0 lg:border-r lg:border-gray-200"
                :class="mobileView === 'map' ? 'hidden lg:block' : 'block'"
            >
                <div class="flex max-h-[calc(100dvh-11.5rem)] min-h-[calc(100dvh-11.5rem)] flex-col lg:h-full lg:max-h-none lg:min-h-0">
                    <div class="border-b-4 border-[#e85d4c] px-5 py-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="site-header-font text-lg font-bold uppercase tracking-[0.12em] text-gray-900">Инвентарь</h2>
                                <p class="mt-1 text-xs text-gray-500">Параметры подбора поверхностей</p>
                            </div>
                            <span
                                v-if="hasActiveFilters"
                                class="site-header-font shrink-0 border border-[#05299E]/20 bg-[#05299E]/5 px-2 py-1 text-[9px] font-semibold uppercase tracking-wider text-[#05299E]"
                            >
                                Фильтр
                            </span>
                        </div>
                    </div>

                    <div class="max-h-[46dvh] space-y-5 overflow-y-auto border-b border-gray-100 bg-[#f7f7f8] p-5 lg:max-h-none lg:overflow-visible">
                        <div>
                            <label class="site-header-font mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.16em] text-gray-500">Категория Конструкции</label>
                            <select
                                v-model="filters.productType"
                                class="w-full border border-gray-300 bg-white px-3 py-3 text-base focus:border-[#05299E] focus:outline-none focus:ring-2 focus:ring-[#05299E]/15"
                            >
                                <option value="">Все типы конструкций</option>
                                <option v-for="item in productTypes" :key="item.id" :value="String(item.id)">
                                    {{ item.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="site-header-font mb-1.5 block text-[12px] font-semibold uppercase tracking-[0.16em] text-gray-500">Тип конструкции</label>
                            <select
                                v-model="filters.constrTypeId"
                                :disabled="isLoadingFilters"
                                class="w-full border border-gray-300 bg-white px-3 py-3 text-base focus:border-[#05299E] focus:outline-none focus:ring-2 focus:ring-[#05299E]/15 disabled:cursor-not-allowed disabled:bg-gray-100"
                            >
                                <option value="">Все типы конструкций</option>
                                <option v-for="item in constrTypes" :key="item.id" :value="String(item.id)">
                                    {{ item.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <p class="site-header-font mb-2 text-[12px] font-semibold uppercase tracking-[0.16em] text-gray-500">Период</p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                <button
                                    v-for="preset in datePresets"
                                    :key="preset.key"
                                    type="button"
                                    class="site-header-font border px-3 py-3 text-[12px] font-semibold uppercase tracking-wide transition"
                                    :class="
                                        activeDatePreset === preset.key
                                            ? 'border-[#05299E] bg-[#05299E] text-white'
                                            : 'border-gray-300 bg-white text-gray-600 hover:border-[#05299E]/40 hover:text-[#05299E]'
                                    "
                                    @click="applyDatePreset(preset.key)"
                                >
                                    {{ preset.label }}
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <label class="text-xs">
                                <span class="site-header-font mb-1 block text-[10px] font-semibold uppercase tracking-[0.15em] text-gray-500">Свободно с</span>
                                <input v-model="filters.bookingFrom" type="date" class="w-full border border-gray-300 bg-white px-3 py-3 text-base" />
                            </label>
                            <label class="text-xs">
                                <span class="site-header-font mb-1 block text-[10px] font-semibold uppercase tracking-[0.15em] text-gray-500">Свободно до</span>
                                <input v-model="filters.bookingTo" type="date" class="w-full border border-gray-300 bg-white px-3 py-3 text-base" />
                            </label>
                        </div>

                        <div class="flex gap-2 pt-1">
                            <button
                                type="button"
                                class="site-header-font flex-1 border border-gray-400 bg-white px-3 py-3 text-[12px] font-semibold uppercase tracking-wider text-gray-700 hover:border-[#05299E] hover:text-[#05299E]"
                                @click="resetFilters"
                            >
                                Сбросить
                            </button>
                            <button
                                type="button"
                                class="site-header-font flex-1 bg-[#e85d4c] px-3 py-3 text-[12px] font-semibold uppercase tracking-wider text-white hover:bg-[#d64d3e]"
                                @click="applyFilters"
                            >
                                Подобрать
                            </button>
                        </div>
                    </div>

                    <div class="site-header-font flex items-center justify-between border-b border-gray-100 bg-white px-5 py-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-600">
                        <span>Найдено</span>
                        <span class="bg-[#05299E] px-3 py-1 text-white">{{ objects.length }}</span>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto p-3 lg:overflow-y-auto">
                        <div v-if="isLoadingObjects" class="p-4 text-sm text-gray-500">Загрузка объектов…</div>
                        <div
                            v-else-if="objects.length === 0"
                            class="border border-dashed border-gray-300 bg-white p-4 text-sm text-gray-500"
                        >
                            По выбранным фильтрам ничего не найдено.
                        </div>

                        <button
                            v-for="item in objects"
                            :key="item.id"
                            type="button"
                            class="mb-3 w-full border bg-white p-4 text-left shadow-sm transition hover:border-[#05299E]/35 hover:shadow-md"
                            :class="activeObjectId === item.id ? 'border-[#e85d4c] ring-1 ring-[#e85d4c]/40' : 'border-gray-200'"
                            @click="focusObject(item.id)"
                        >
                            <div class="mb-1 flex items-start justify-between gap-3">
                                <h3 class="line-clamp-2 text-sm font-semibold text-gray-900">
                                    {{ item.address || 'Адрес не указан' }}
                                </h3>
                                <span class="site-header-font shrink-0 bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-gray-600">#{{ item.id }}</span>
                            </div>
                            <p class="text-xs text-gray-600">
                                {{ item.category || '—' }} • {{ item.type || '—' }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500">Стороны: {{ formatSides(item.sides) }}</p>
                            <p
                                class="site-header-font mt-2 text-[10px] font-semibold uppercase tracking-wide"
                                :class="getItemStatus(item, bookingRange.from, bookingRange.to).toneClass"
                            >
                                {{ getItemStatus(item, bookingRange.from, bookingRange.to).text }}
                            </p>
                        </button>
                    </div>
                </div>
            </aside>

            <section
                class="relative flex min-h-0 flex-1 flex-col overflow-hidden p-1.5 sm:p-2 lg:p-3"
                :class="mobileView === 'list' ? 'hidden lg:flex' : 'flex'"
            >
                <div
                    v-if="mapError"
                    class="flex min-h-[280px] flex-1 items-center justify-center border border-red-200 bg-red-50 p-6 text-center text-sm text-red-800"
                >
                    {{ mapError }}
                </div>

                <div v-else class="relative min-h-[320px] w-full flex-1 max-lg:min-h-[48vh] lg:min-h-0">
                    <div
                        ref="mapContainer"
                        class="absolute inset-0 border border-gray-300 bg-gray-200 shadow-[0_20px_50px_-20px_rgba(0,0,0,0.25)]"
                    ></div>
                    <div
                        v-show="!isMapLoaded"
                        class="site-header-font absolute inset-0 z-10 flex items-center justify-center border border-gray-200 bg-white text-xs font-semibold uppercase tracking-[0.2em] text-gray-400"
                    >
                        Загрузка карты…
                    </div>
                </div>

                <article
                    v-if="activeObject && activeSide"
                    class="absolute inset-0 z-30 overflow-y-auto border-t-4 border-[#e85d4c] bg-white shadow-[0_24px_48px_-12px_rgba(0,0,0,0.35)] sm:inset-auto sm:right-4 sm:top-4 sm:bottom-auto sm:max-h-[calc(100%-2rem)] sm:w-[520px] sm:max-w-[calc(100%-2rem)] lg:right-5 lg:top-1/2 lg:max-h-[calc(100%-2.5rem)] lg:w-[520px] lg:max-w-[calc(100%-40px)] lg:-translate-y-1/2"
                >
                    <div class="relative">
                        <div class="absolute left-3 top-3 z-10 flex max-w-[calc(100%-90px)] gap-1 overflow-x-auto bg-white/95 p-1 shadow-md sm:left-4 sm:top-4">
                            <button
                                v-for="side in activeObject.side_details"
                                :key="side.code"
                                type="button"
                                class="site-header-font min-w-[40px] px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide transition"
                                :class="activeSideCode === side.code ? 'bg-[#05299E] text-white' : getSideStatus(activeObject, side.code, bookingRange.from, bookingRange.to).toneClass"
                                @click="selectSide(side.code)"
                            >
                                {{ side.code }}
                            </button>
                        </div>

                        <button
                            type="button"
                            class="absolute right-3 top-3 z-10 flex h-9 w-9 items-center justify-center border border-gray-200 bg-white text-xl text-gray-600 shadow-sm hover:bg-gray-50 sm:right-4 sm:top-4"
                            @click="closeCard"
                        >
                            ×
                        </button>

                        <img
                            :src="getMainSideImage(activeSide)"
                            alt="Фото стороны"
                            class="h-44 w-full object-cover sm:h-56 lg:h-64"
                        />
                        <button
                            v-if="activeSide.night_image_url"
                            type="button"
                            class="site-header-font absolute bottom-3 right-3 bg-white/95 px-3 py-1 text-[10px] font-semibold uppercase tracking-wider text-gray-800"
                            @click="isNightPhoto = !isNightPhoto"
                        >
                            {{ isNightPhoto ? 'Днём' : 'Ночью' }}
                        </button>
                    </div>

                    <div class="space-y-4 p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-bold leading-tight text-gray-900 sm:text-xl">{{ activeObject.address }}</h3>
                                <p class="site-header-font mt-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-400">GID {{ activeObject.id }}</p>
                            </div>
                        </div>

                        <dl class="grid grid-cols-[1fr_auto] gap-x-4 gap-y-2 border-t border-gray-100 pt-3 text-sm">
                            <dt class="border-b border-gray-100 pb-2 text-gray-500">Формат</dt>
                            <dd class="border-b border-gray-100 pb-2 text-right font-semibold text-gray-900">{{ activeObject.type }}</dd>
                            <dt class="border-b border-gray-100 pb-2 text-gray-500">Сторона</dt>
                            <dd class="border-b border-gray-100 pb-2 text-right font-semibold text-gray-900">{{ activeSide.code }}</dd>
                            <dt class="border-b border-gray-100 pb-2 text-gray-500">Описание</dt>
                            <dd class="border-b border-gray-100 pb-2 text-right text-xs text-gray-700 sm:text-sm">
                                {{ activeSide.description || '—' }}
                            </dd>
                        </dl>

                        <section v-if="activeObject.side_details?.length" class="space-y-2 border-t border-gray-100 pt-3">
                            <p class="site-header-font text-[10px] font-semibold uppercase tracking-[0.15em] text-gray-500">
                                Фотографии всех сторон
                            </p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                <button
                                    v-for="side in activeObject.side_details"
                                    :key="`photo-${side.code}`"
                                    type="button"
                                    class="group text-left"
                                    @click="selectSide(side.code)"
                                >
                                    <div
                                        class="relative aspect-[4/3] overflow-hidden border transition"
                                        :class="activeSideCode === side.code ? 'border-[#05299E] ring-1 ring-[#05299E]/35' : 'border-gray-200 hover:border-[#05299E]/40'"
                                    >
                                        <img
                                            :src="getPreviewSideImage(side)"
                                            :alt="`Сторона ${side.code}`"
                                            class="h-full w-full object-cover"
                                        />
                                        <span class="site-header-font absolute left-1.5 top-1.5 bg-white/90 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-800">
                                            {{ side.code }}
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </section>

                        <p
                            v-if="activeSideStatus"
                            class="site-header-font text-[11px] font-semibold uppercase tracking-wide"
                            :class="activeSideStatus.toneClass"
                        >
                            {{ activeSideStatus.text }}
                        </p>

                        <button
                            type="button"
                            class="site-header-font w-full bg-[#e85d4c] px-4 py-3 text-[12px] font-semibold uppercase tracking-wider text-white hover:bg-[#d64d3e]"
                            @click="addToCart"
                        >
                            В корзину
                        </button>
                        <button
                            type="button"
                            class="site-header-font w-full border border-[#05299E] px-4 py-3 text-[12px] font-semibold uppercase tracking-wider text-[#05299E] hover:bg-[#05299E]/5"
                            @click="goToCart"
                        >
                            Перейти в корзину ({{ cartItems.length }})
                        </button>
                        <a
                            :href="`/catalog/construction/${activeObject.id}`"
                            class="site-header-font flex w-full items-center justify-center border border-gray-300 bg-white px-4 py-3 text-[12px] font-semibold uppercase tracking-wider text-gray-800 transition hover:border-[#05299E] hover:text-[#05299E]"
                        >
                            Детальная карточка
                        </a>
                    </div>
                </article>

                <a
                    v-if="cartItems.length"
                    href="/cart"
                    class="site-header-font absolute bottom-4 left-1/2 z-20 flex -translate-x-1/2 items-center gap-2 bg-[#05299E] px-4 py-2.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-white shadow-lg lg:hidden"
                >
                    <span>Корзина</span>
                    <span class="bg-white px-2 py-0.5 text-[#05299E]">{{ cartItems.length }}</span>
                </a>

                <aside
                    v-if="false && isCartOpen"
                    class="absolute inset-x-2 bottom-2 z-30 max-h-[72vh] overflow-y-auto border-t-4 border-[#05299E] bg-white shadow-[0_24px_48px_-12px_rgba(0,0,0,0.35)] sm:right-4 sm:top-4 sm:inset-x-auto sm:w-[420px] sm:max-w-[calc(100%-2rem)] lg:bottom-5 lg:right-5 lg:top-auto lg:max-h-[calc(100%-2.5rem)]"
                >
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                        <h3 class="site-header-font text-[11px] font-semibold uppercase tracking-[0.15em] text-gray-900">Корзина</h3>
                        <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-700" @click="isCartOpen = false">×</button>
                    </div>

                    <div class="max-h-[46vh] space-y-2 overflow-y-auto p-4">
                        <article
                            v-for="(item, index) in cartItems"
                            :key="`${item.advertisementId}-${item.side}-${item.startDate}-${item.endDate}`"
                            class="border border-gray-200 bg-[#fafafa] p-3"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ item.address }}</p>
                                    <p class="mt-1 text-xs text-gray-600">Сторона {{ item.side }} • {{ item.startDate }}—{{ item.endDate }}</p>
                                    <p class="mt-1 text-xs font-semibold text-[#05299E]">{{ formatPrice(item.price) }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="site-header-font shrink-0 border border-gray-300 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-600 hover:border-[#e85d4c] hover:text-[#e85d4c]"
                                    @click="removeCartItem(index)"
                                >
                                    Удалить
                                </button>
                            </div>
                        </article>
                        <p v-if="!cartItems.length" class="border border-dashed border-gray-300 p-4 text-sm text-gray-500">Корзина пуста.</p>
                    </div>

                    <div class="space-y-3 border-t border-gray-100 bg-white px-4 py-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Позиций</span>
                            <span class="font-semibold text-gray-900">{{ cartItems.length }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Итого (без НДС)</span>
                            <span class="text-base font-bold text-gray-900">{{ formatPrice(cartTotal) }}</span>
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="site-header-font flex-1 border border-gray-400 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-700 hover:bg-gray-50"
                                :disabled="!cartItems.length"
                                @click="clearCart"
                            >
                                Очистить
                            </button>
                            <button
                                type="button"
                                class="site-header-font flex-1 bg-[#05299E] py-2.5 text-[11px] font-semibold uppercase tracking-wider text-white hover:bg-[#041d6b] disabled:opacity-50"
                                :disabled="!cartItems.length"
                                @click="openOrderModal"
                            >
                                Оформить
                            </button>
                        </div>
                    </div>
                </aside>

                <div v-if="isOrderModalOpen" class="absolute inset-0 z-30 flex items-center justify-center bg-[#0d1117]/60 p-4 backdrop-blur-[2px]">
                    <div class="w-full max-w-xl border-t-4 border-[#e85d4c] bg-white p-6 shadow-2xl">
                        <div class="mb-4 flex items-start justify-between gap-4">
                            <div>
                                <h4 class="site-header-font text-lg font-bold uppercase tracking-wide text-gray-900">Заказ</h4>
                                <p class="mt-1 text-xs text-gray-500">Бронь — 24 часа после отправки</p>
                            </div>
                            <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-800" @click="closeOrderModal">×</button>
                        </div>

                        <form class="space-y-4" @submit.prevent="submitOrder">
                            <div class="max-h-36 space-y-2 overflow-auto border border-gray-200 bg-[#f9fafb] p-3">
                                <div
                                    v-for="(item, index) in cartItems"
                                    :key="`${item.advertisementId}-${item.side}-${index}`"
                                    class="flex items-start justify-between gap-2 text-sm"
                                >
                                    <span class="text-gray-700">{{ item.address }} • {{ item.side }} • {{ item.startDate }}—{{ item.endDate }} • {{ formatPrice(item.price) }}</span>
                                    <button type="button" class="shrink-0 text-[#e85d4c] hover:underline" @click="removeCartItem(index)">Удалить</button>
                                </div>
                                <p v-if="!cartItems.length" class="text-sm text-gray-500">Корзина пуста</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label class="text-sm">
                                    <span class="site-header-font mb-1 block text-[10px] font-semibold uppercase tracking-wider text-gray-500">Имя</span>
                                    <input v-model.trim="orderForm.name" :readonly="isAuthenticated" required class="w-full border border-gray-300 px-3 py-2" />
                                </label>
                                <label class="text-sm">
                                    <span class="site-header-font mb-1 block text-[10px] font-semibold uppercase tracking-wider text-gray-500">Телефон</span>
                                    <input v-model.trim="orderForm.phone" :readonly="isAuthenticated" required class="w-full border border-gray-300 px-3 py-2" />
                                </label>
                            </div>

                            <p v-if="isAuthenticated" class="text-xs text-gray-500">Данные из аккаунта.</p>

                            <label class="block text-sm">
                                <span class="site-header-font mb-1 block text-[10px] font-semibold uppercase tracking-wider text-gray-500">Комментарий</span>
                                <textarea v-model.trim="orderForm.comment" rows="3" class="w-full border border-gray-300 px-3 py-2"></textarea>
                            </label>
                            <input v-model="orderForm.website" type="text" autocomplete="off" class="hidden" tabindex="-1" />

                            <p v-if="orderStatusMessage" class="border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ orderStatusMessage }}</p>

                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="site-header-font flex-1 border border-gray-400 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-700 hover:bg-gray-50"
                                    @click="closeOrderModal"
                                >
                                    Отмена
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSubmittingOrder || !cartItems.length"
                                    class="site-header-font flex-1 bg-[#05299E] py-2.5 text-[11px] font-semibold uppercase tracking-wider text-white hover:bg-[#041d6b] disabled:opacity-50"
                                >
                                    {{ isSubmittingOrder ? 'Отправка…' : 'Подтвердить' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'

const props = defineProps({
    filtersUrl: { type: String, required: true },
    advertisementsUrl: { type: String, required: true },
    ordersUrl: { type: String, required: true },
    cartUrl: { type: String, required: true },
    authUser: {
        type: Object,
        required: true,
    },
})

// --- Состояние ---
const productTypes = ref([])
const constrTypes = ref([])
const objects = ref([])
const filters = reactive({ productType: '', constrTypeId: '', bookingFrom: '', bookingTo: '' })
const isLoadingFilters = ref(false)
const isLoadingObjects = ref(false)
const mobileView = ref('list')
const activeDatePreset = ref('')

const mapContainer = ref(null)
const isMapLoaded = ref(false)
const mapError = ref('')
const activeObjectId = ref(null)
const activeSideCode = ref('')
const isOrderModalOpen = ref(false)
const isSubmittingOrder = ref(false)
const orderStatusMessage = ref('')
const orderForm = reactive({ name: '', phone: '', comment: '', website: '', startedAt: 0 })
const cartItems = ref([])
const isCartOpen = ref(false)
const isNightPhoto = ref(false)

let map = null
let placemarks = new Map()
let applyTimer = null

const datePresets = [
    { key: 'week', label: '7 дней' },
    { key: 'month', label: '30 дней' },
]

// --- Вычисляемые свойства ---
const bookingRange = computed(() => {
    const from = parseDate(filters.bookingFrom)
    const to = parseDate(filters.bookingTo)
    if (from && to && to < from) return { from: to, to: from }
    return { from, to }
})

const activeObject = computed(() =>
    objects.value.find(o => String(o.id) === String(activeObjectId.value)) || null,
)

const activeSide = computed(() => {
    if (!activeObject.value) return null
    return activeObject.value.side_details.find(s => s.code === activeSideCode.value) || activeObject.value.side_details[0] || null
})

const activeSideStatus = computed(() => {
    if (!activeObject.value || !activeSide.value) return null
    return getSideStatus(activeObject.value, activeSide.value.code, bookingRange.value.from, bookingRange.value.to)
})

const normalizedOrdersUrl = computed(() => String(props.ordersUrl || '').replace(/\/+$/, ''))
const isAuthenticated = computed(() => Boolean(props.authUser?.isAuthenticated))
const hasActiveFilters = computed(() => Boolean(filters.productType || filters.constrTypeId || filters.bookingFrom || filters.bookingTo))
const cartTotal = computed(() => cartItems.value.reduce((sum, item) => sum + (Number(item.price) || 0), 0))

// --- Вспомогательные функции ---
function parseDate(value) {
    if (!value) return null
    const date = new Date(`${value}T00:00:00`)
    return isNaN(date.getTime()) ? null : date
}

function toInputDate(date) {
    return date.toISOString().slice(0, 10)
}

function applyDatePreset(presetKey) {
    activeDatePreset.value = presetKey
    const now = new Date()

    if (presetKey === 'clear') {
        filters.bookingFrom = ''
        filters.bookingTo = ''
        return
    }

    const from = new Date(now)
    const to = new Date(now)
    if (presetKey === 'week') to.setDate(now.getDate() + 7)
    if (presetKey === 'month') to.setDate(now.getDate() + 30)

    filters.bookingFrom = toInputDate(from)
    filters.bookingTo = toInputDate(to)
}

function scheduleApplyFilters() {
    clearTimeout(applyTimer)
    applyTimer = setTimeout(() => {
        applyFilters()
    }, 250)
}

function formatSides(sides) {
    return Array.isArray(sides) && sides.length > 0 ? sides.join(', ') : '—'
}

function formatPrice(price) {
    if (!price) return 'По запросу'
    return `${new Intl.NumberFormat('ru-RU').format(price)} ₽`
}

function normalizeCloudMailImageUrl(url) {
    if (!url) return null
    const source = String(url).trim()
    const match = source.match(/^https?:\/\/cloud\.mail\.ru\/public\/([^/]+)\/([^/?#]+)/i)
    if (!match) return source
    return `https://thumb.cloud.mail.ru/weblink/thumb/xw0/${match[1]}/${match[2]}?wm=true`
}

function resolveSideImageUrl(side, useNight = false) {
    if (!side) return '/images/orig.png'

    const preferred = useNight
        ? (side.night_image_url || side.night_image)
        : (side.image_url || side.image)

    const fallback = useNight
        ? (side.image_url || side.image)
        : (side.night_image_url || side.night_image)

    return normalizeCloudMailImageUrl(preferred) || normalizeCloudMailImageUrl(fallback) || '/images/orig.png'
}

function getMainSideImage(side) {
    return resolveSideImageUrl(side, isNightPhoto.value)
}

function getPreviewSideImage(side) {
    return resolveSideImageUrl(side, false)
}

function normalizeSideDetails(item) {
    const sides = Array.isArray(item?.sides) ? item.sides : []
    if (Array.isArray(item?.side_details) && item.side_details.length) {
        return item.side_details
            .filter(side => side && typeof side === 'object')
            .map(side => ({
                ...side,
                code: String(side.code || '').toUpperCase(),
            }))
            .filter(side => side.code !== '')
    }

    return sides.map(code => ({ code: String(code).toUpperCase(), price: null, image_url: null, night_image_url: null }))
}

function getSideStatus(item, sideCode, fromDate, toDate) {
    const bookings = (item?.bookings || []).filter(b => b.side_code === sideCode)
    const from = fromDate || new Date()
    const to = toDate || from

    const overlap = bookings.find(b => {
        const start = parseDate(b.start_date)
        const end = parseDate(b.end_date)
        return start && end && start <= to && end >= from
    })

    if (overlap) {
        const d = parseDate(overlap.end_date)
        d.setDate(d.getDate() + 1)
        const kind = overlap.booking_kind ?? overlap.bookingKind ?? 'firm'
        const dateText = d.toLocaleDateString('ru-RU')
        if (kind === 'hold') {
            return {
                busy: true,
                kind,
                text: `Занята (ожидание оплаты) до ${dateText}`,
                toneClass: 'bg-amber-50 text-amber-700',
            }
        }

        return {
            busy: true,
            kind,
            text: `Занята до ${dateText}`,
            toneClass: 'bg-red-50 text-red-700',
        }
    }

    return { busy: false, kind: 'free', text: 'Свободна', toneClass: 'text-emerald-800 hover:bg-emerald-50' }
}

function getItemStatus(item, from, to) {
    const sideDetails = Array.isArray(item?.side_details) ? item.side_details : []
    const statuses = sideDetails.map(s => getSideStatus(item, s.code, from, to))
    if (!statuses.length) {
        return { busy: false, kind: 'free', text: 'Есть свободные стороны', toneClass: 'text-emerald-700' }
    }
    const busyAll = statuses.every(s => s.busy)
    if (!busyAll) {
        return { busy: false, kind: 'free', text: 'Есть свободные стороны', toneClass: 'text-emerald-700' }
    }

    const anyHold = statuses.some(s => s.kind === 'hold')
    if (anyHold) {
        return { busy: true, kind: 'hold', text: 'Занята (ожидание оплаты)', toneClass: 'text-amber-600' }
    }

    return { busy: true, kind: 'firm', text: 'Занята', toneClass: 'text-red-600' }
}

// --- API ---
async function loadFilters() {
    isLoadingFilters.value = true
    try {
        const res = await fetch(`${props.filtersUrl}${filters.productType ? '?productType=' + filters.productType : ''}`)
        const data = await res.json()
        productTypes.value = data.productTypes || []
        constrTypes.value = data.constrTypes || []
    } finally { isLoadingFilters.value = false }
}

async function loadAdvertisements() {
    isLoadingObjects.value = true
    try {
        const params = new URLSearchParams()
        if (filters.productType) params.append('productType', filters.productType)
        if (filters.constrTypeId) params.append('constrTypeId', filters.constrTypeId)

        const res = await fetch(`${props.advertisementsUrl}?${params.toString()}`)
        const data = await res.json()
        const items = Array.isArray(data) ? data : (Array.isArray(data?.items) ? data.items : [])

        objects.value = items.map(item => ({
            ...item,
            side_details: normalizeSideDetails(item),
            sides: normalizeSideDetails(item).map(s => s.code),
            bookings: Array.isArray(item?.bookings) ? item.bookings : [],
        }))
        syncMapPlacemarks()
    } finally { isLoadingObjects.value = false }
}

function applyCartPayload(data) {
    cartItems.value = Array.isArray(data?.items) ? data.items : []
}

async function loadCart() {
    try {
        const response = await fetch(props.cartUrl)
        if (!response.ok) {
            throw new Error()
        }
        const data = await response.json()
        applyCartPayload(data)
    } catch {
        orderStatusMessage.value = 'Не удалось загрузить корзину.'
    }
}

// --- Карта ---
function syncMapPlacemarks() {
    if (!map) return
    placemarks.forEach(p => map.geoObjects.remove(p))
    placemarks.clear()

    objects.value.forEach(item => {
        if (!item.location?.latitude) return
        const p = new window.ymaps.Placemark(
            [item.location.latitude, item.location.longitude],
            {},
            { preset: 'islands#redCircleDotIcon' }
        )
        p.events.add('click', () => focusObject(item.id))
        placemarks.set(item.id, p)
        map.geoObjects.add(p)
    })
}

function focusObject(id) {
    activeObjectId.value = id
    const item = objects.value.find(o => o.id === id)
    activeSideCode.value = item?.sides[0] || ''
    isNightPhoto.value = false
    if (map && item?.location) {
        map.setCenter([item.location.latitude, item.location.longitude], 15, { duration: 300 })
    }
}

// --- Действия ---
function applyFilters() {
    activeObjectId.value = null
    loadAdvertisements()
}

function resetFilters() {
    Object.assign(filters, { productType: '', constrTypeId: '', bookingFrom: '', bookingTo: '' })
    activeDatePreset.value = ''
    applyFilters()
}

function selectSide(code) {
    activeSideCode.value = code
    isNightPhoto.value = false
}

function closeCard() {
    activeObjectId.value = null
}

function goToCart() {
    window.location.href = '/cart'
}

async function addToCart() {
    if (!activeObject.value || !activeSide.value) return
    const startDate = filters.bookingFrom || toInputDate(new Date())
    const end = new Date()
    end.setDate(end.getDate() + 30)
    const endDate = filters.bookingTo || toInputDate(end)
    try {
        const response = await fetch(`${props.cartUrl}/items`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                advertisementId: activeObject.value.id,
                side: activeSide.value.code,
                startDate,
                endDate,
            }),
        })
        const data = await response.json()
        if (!response.ok) {
            orderStatusMessage.value = data?.message || 'Не удалось добавить позицию в корзину.'
            return
        }

        applyCartPayload(data)
        orderStatusMessage.value = data?.message || 'Позиция добавлена в корзину.'
        isCartOpen.value = true
    } catch {
        orderStatusMessage.value = 'Не удалось добавить позицию в корзину.'
    }
}

async function removeCartItem(index) {
    try {
        const response = await fetch(`${props.cartUrl}/items/${index}`, { method: 'DELETE' })
        const data = await response.json()
        if (!response.ok) {
            orderStatusMessage.value = data?.message || 'Не удалось удалить позицию.'
            return
        }
        applyCartPayload(data)
        orderStatusMessage.value = data?.message || 'Позиция удалена.'
    } catch {
        orderStatusMessage.value = 'Не удалось удалить позицию.'
    }
}

async function clearCart() {
    try {
        const response = await fetch(props.cartUrl, { method: 'DELETE' })
        const data = await response.json()
        if (!response.ok) {
            orderStatusMessage.value = data?.message || 'Не удалось очистить корзину.'
            return
        }
        applyCartPayload(data)
        orderStatusMessage.value = data?.message || 'Корзина очищена.'
    } catch {
        orderStatusMessage.value = 'Не удалось очистить корзину.'
    }
}

function openOrderModal() {
    if (!cartItems.value.length) {
        orderStatusMessage.value = 'Добавьте хотя бы одну позицию в корзину.'
        return
    }
    orderStatusMessage.value = ''
    orderForm.startedAt = Date.now()

    if (isAuthenticated.value) {
        orderForm.name = props.authUser?.name || orderForm.name
        orderForm.phone = props.authUser?.phone || orderForm.phone
    }

    isOrderModalOpen.value = true
    isCartOpen.value = false
}

function closeOrderModal() { isOrderModalOpen.value = false }

async function submitOrder() {
    isSubmittingOrder.value = true
    try {
        const response = await fetch(normalizedOrdersUrl.value || '/api/orders', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                website: orderForm.website,
                formStartedAt: orderForm.startedAt,
                contactName: orderForm.name,
                contactPhone: orderForm.phone,
                comment: orderForm.comment || null,
                items: cartItems.value,
                userId: props.authUser?.id ?? null,
                userEmail: props.authUser?.email || null,
                isAuthenticated: isAuthenticated.value,
            })
        })
        if (response.ok) {
            orderStatusMessage.value = 'Заказ отправлен. Бронь активна 24 часа.'
            await loadCart()
            setTimeout(closeOrderModal, 1500)
        } else {
            throw new Error()
        }
    } catch {
        orderStatusMessage.value = 'Ошибка при отправке.'
    } finally {
        isSubmittingOrder.value = false
    }
}

onMounted(async () => {
    await loadFilters()
    await loadAdvertisements()
    await loadCart()

    if (!window.ymaps) {
        const script = document.createElement('script')
        script.src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU'
        script.onload = () => window.ymaps.ready(initMap)
        document.head.appendChild(script)
    } else {
        window.ymaps.ready(initMap)
    }
})

function initMap() {
    if (!mapContainer.value) {
        mapError.value = 'Контейнер карты не найден.'
        return
    }
    try {
        map = new window.ymaps.Map(mapContainer.value, {
            center: [51.8335, 107.5841],
            zoom: 10,
            controls: ['zoomControl'],
        })
    } catch {
        mapError.value = 'Не удалось отобразить карту. Обновите страницу.'
        return
    }
    isMapLoaded.value = true
    syncMapPlacemarks()
    void nextTick(() => {
        try {
            map?.container?.fitToViewport()
        } catch {
            /* ymaps может отсутствовать в тестах */
        }
    })
}

watch(mobileView, async () => {
    await nextTick()
    const refit = () => {
        try {
            map?.container?.fitToViewport()
        } catch {
            /* noop */
        }
    }
    refit()
    requestAnimationFrame(refit)
    setTimeout(refit, 120)
})

onBeforeUnmount(() => {
    map?.destroy()
    clearTimeout(applyTimer)
})

watch(() => filters.productType, () => {
    filters.constrTypeId = ''
    loadFilters()
})

watch(() => filters.constrTypeId, scheduleApplyFilters)
watch(() => [filters.bookingFrom, filters.bookingTo], ([from, to]) => {
    if (from && to && parseDate(to) < parseDate(from)) {
        filters.bookingTo = from
    }
    if (!from && !to) activeDatePreset.value = ''
    scheduleApplyFilters()
})
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
