<template>
    <div class="flex min-h-0 flex-1 flex-col bg-[var(--color-bg)] text-[var(--color-ink)] lg:overflow-hidden">

        <!-- toolbar -->
        <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <h1 class="home-display truncate text-lg font-bold">Карта и каталог</h1>
                <span class="u-pill shrink-0" style="background:var(--color-tint-blue);color:var(--color-blue)">
                    {{ objects.length }} конструкций
                </span>
            </div>
            <a href="/cart" class="u-btn u-btn--blue shrink-0 !px-4 !py-2 text-sm">
                Корзина ({{ cartItems.length }}) <span class="arr">→</span>
            </a>
        </div>

        <!-- mobile tabs -->
        <div class="flex gap-2 px-4 pb-2 lg:hidden">
            <button type="button"
                    class="flex-1 rounded-full py-2.5 text-[.82rem] font-bold transition"
                    :class="mobileView === 'list' ? 'bg-[var(--color-ink)] text-white' : 'bg-white text-[var(--color-muted)] shadow-[var(--shadow-soft)]'"
                    @click="mobileView = 'list'"
            >Фильтры
            </button>
            <button type="button"
                    class="flex-1 rounded-full py-2.5 text-[.82rem] font-bold transition"
                    :class="mobileView === 'map' ? 'bg-[var(--color-ink)] text-white' : 'bg-white text-[var(--color-muted)] shadow-[var(--shadow-soft)]'"
                    @click="mobileView = 'map'"
            >Карта
            </button>
        </div>

        <div class="flex min-h-0 flex-1 flex-col gap-4 p-4 pt-0 lg:flex-row lg:overflow-hidden">

            <!-- ───── sidebar ───── -->
            <aside
                class="u-card flex w-full flex-col overflow-hidden lg:w-[372px] lg:shrink-0"
                :class="mobileView === 'map' ? 'hidden lg:flex' : 'flex'"
            >
                <div class="flex items-start justify-between border-b border-[rgba(13,19,32,.08)] px-5 py-4">
                    <div>
                        <h2 class="text-lg font-extrabold tracking-tight">Инвентарь</h2>
                        <p class="mt-0.5 text-xs text-[var(--color-muted)]">Подбор поверхностей</p>
                    </div>
                    <span v-if="hasActiveFilters" class="u-pill"
                          style="background:var(--color-tint-blue);color:var(--color-blue)">Фильтр</span>
                </div>

                <div
                    class="flex max-h-[44vh] flex-col gap-4 overflow-auto border-b border-[rgba(13,19,32,.08)] p-5 lg:max-h-none">
                    <!-- search -->
                    <div>
                        <label class="u-field-label">Поиск по коду конструкции</label>
                        <div class="relative">
                            <svg
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--color-muted)]"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7"/>
                                <path d="M21 21l-4.3-4.3"/>
                            </svg>
                            <input v-model="searchQuery" type="search" inputmode="search"
                                   placeholder="Код или № площади…" class="u-input !pl-9 !pr-9"/>
                            <button v-if="searchQuery" type="button"
                                    class="absolute right-2.5 top-1/2 grid h-6 w-6 -translate-y-1/2 place-items-center rounded-full text-[var(--color-muted)] transition hover:bg-[var(--color-bg-2)]"
                                    aria-label="Очистить поиск" @click="searchQuery = ''">×
                            </button>
                        </div>
                    </div>
                    <!-- category -->
                    <div>
                        <label class="u-field-label">Категория конструкции</label>
                        <select v-model="filters.productType" class="u-select">
                            <option value="">Все категории</option>
                            <option v-for="item in productTypes" :key="item.id" :value="String(item.id)">{{
                                    item.name
                                }}
                            </option>
                        </select>
                    </div>
                    <!-- type -->
                    <div>
                        <label class="u-field-label">Тип конструкции</label>
                        <select v-model="filters.constrTypeId" :disabled="isLoadingFilters"
                                class="u-select disabled:cursor-not-allowed disabled:opacity-60">
                            <option value="">Все типы</option>
                            <option v-for="item in constrTypes" :key="item.id" :value="String(item.id)">{{
                                    item.name
                                }}
                            </option>
                        </select>
                    </div>
                    <!-- period presets -->
                    <div>
                        <label class="u-field-label">Период</label>
                        <div class="flex gap-2">
                            <button v-for="preset in datePresets" :key="preset.key" type="button"
                                    class="flex-1 rounded-full border py-2.5 text-[.78rem] font-bold transition"
                                    :class="activeDatePreset === preset.key
                                    ? 'border-[var(--color-ink)] bg-[var(--color-ink)] text-white'
                                    : 'border-[rgba(13,19,32,.12)] bg-white text-[var(--color-muted)] hover:border-[var(--color-blue)] hover:text-[var(--color-blue)]'"
                                    @click="applyDatePreset(preset.key)">{{ preset.label }}
                            </button>
                        </div>
                    </div>
                    <!-- date range -->
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="u-field-label">Свободно с</label>
                            <input v-model="filters.bookingFrom" type="date" class="u-input">
                        </div>
                        <div>
                            <label class="u-field-label">Свободно до</label>
                            <input v-model="filters.bookingTo" type="date" class="u-input">
                        </div>
                    </div>
                    <!-- actions -->
                    <div class="flex gap-2.5">
                        <button type="button" class="u-btn u-btn--soft flex-1 justify-center !py-3 text-[.82rem]"
                                @click="resetFilters">Сбросить
                        </button>
                        <button type="button" class="u-btn u-btn--blue flex-1 justify-center !py-3 text-[.82rem]"
                                @click="applyFilters">Подобрать
                        </button>
                    </div>
                </div>

                <!-- results count -->
                <div
                    class="flex items-center justify-between border-b border-[rgba(13,19,32,.08)] px-5 py-3 text-[.74rem] font-bold text-[var(--color-muted)]">
                    <span>{{ searchQuery ? 'Найдено по запросу' : 'Найдено конструкций' }}</span>
                    <span
                        class="rounded-full bg-[var(--color-tint-blue)] px-2.5 py-0.5 font-[var(--font-display)] text-[.88rem] text-[var(--color-blue)]">
                        {{ visibleObjects.length }}
                    </span>
                </div>

                <!-- list -->
                <div class="min-h-0 flex-1 overflow-y-auto p-3.5">
                    <div v-if="isLoadingObjects" class="p-4 text-sm text-[var(--color-muted)]">Загрузка объектов…</div>
                    <div v-else-if="visibleObjects.length === 0"
                         class="rounded-[var(--radius-soft)] border border-dashed border-[rgba(13,19,32,.18)] bg-white p-4 text-sm text-[var(--color-muted)]">
                        {{
                            searchQuery ? `Конструкция «${searchQuery}» не найдена.` : 'По выбранным фильтрам ничего не найдено.'
                        }}
                    </div>

                    <button v-for="item in visibleObjects" :key="item.id" type="button"
                            class="mb-3 w-full rounded-[var(--radius-soft)] border bg-white p-4 text-left transition hover:-translate-y-0.5 hover:shadow-[var(--shadow-soft)]"
                            :class="activeObjectId === item.id
                            ? 'border-[var(--color-blue)] shadow-[0_0_0_3px_rgba(42,75,247,.15)]'
                            : 'border-[rgba(13,19,32,.08)] hover:border-[rgba(42,75,247,.35)]'"
                            @click="focusObject(item.id)">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="line-clamp-2 text-[.96rem] font-bold leading-snug">
                                {{ item.address || 'Адрес не указан' }}
                            </h3>
                            <span
                                class="shrink-0 rounded-full bg-[var(--color-bg-2)] px-2 py-0.5 font-[var(--font-display)] text-[.66rem] font-bold text-[var(--color-muted)]">
                                #{{ item.id }}
                            </span>
                        </div>
                        <p v-if="constructionNumber(item)"
                           class="mt-1 text-[.74rem] font-semibold text-[var(--color-blue)]">
                            № {{ constructionNumber(item) }}
                        </p>
                        <p class="mt-1.5 text-[.82rem] text-[var(--color-muted)]">
                            {{ item.category || '—' }} • {{ item.type || '—' }}
                        </p>
                        <!-- per-side mini status row -->
                        <div class="mt-2 flex flex-wrap gap-1">
                            <span v-for="side in item.side_details" :key="`list-${item.id}-${side.code}`"
                                  class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[.68rem] font-bold"
                                  :class="getSideStatus(item, side.code, bookingRange.from, bookingRange.to).pillClass">
                                {{ side.code }}
                                <span class="opacity-80">
                                    {{ getSideStatus(item, side.code, bookingRange.from, bookingRange.to).shortText }}
                                </span>
                            </span>
                        </div>
                    </button>
                </div>
            </aside>

            <!-- ───── map ───── -->
            <section class="u-card relative flex min-h-0 flex-1 overflow-hidden"
                     :class="mobileView === 'list' ? 'hidden lg:flex' : 'flex'">
                <div v-if="mapError"
                     class="flex min-h-[280px] flex-1 items-center justify-center p-6 text-center text-sm text-[var(--color-busy)]">
                    {{ mapError }}
                </div>

                <div v-else class="relative min-h-[320px] w-full flex-1 max-lg:min-h-[52vh] lg:min-h-0">
                    <div ref="mapContainer" class="absolute inset-0 bg-[#EEF1FA]"></div>
                    <div v-show="!isMapLoaded"
                         class="absolute inset-0 z-10 flex items-center justify-center bg-[#EEF1FA] text-xs font-bold uppercase tracking-[0.18em] text-[var(--color-muted)]">
                        Загрузка карты…
                    </div>
                    <!-- map legend -->
                    <div
                        class="pointer-events-none absolute bottom-4 left-4 z-[5] flex flex-col gap-1.5 rounded-xl bg-white/92 px-3 py-2.5 shadow-[var(--shadow-soft)]">
                        <p class="text-[.6rem] font-bold uppercase tracking-[0.1em] text-[var(--color-muted)]">
                            Статус</p>
                        <div v-for="s in STATUS_LEGEND" :key="s.kind" class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full flex-shrink-0"
                                  :style="{ background: s.color }"></span>
                            <span class="text-[.7rem] font-semibold text-[var(--color-ink)]">{{ s.label }}</span>
                        </div>
                    </div>
                    <span
                        class="pointer-events-none absolute left-4 top-4 z-[5] rounded-full bg-white/92 px-3 py-1.5 text-[.64rem] font-bold tracking-[0.08em] text-[var(--color-blue)] shadow-[var(--shadow-soft)]">
                        ● Карта · live
                    </span>
                </div>

                <!-- ───── detail card ───── -->
                <transition name="detail">
                    <article v-if="activeObject && activeSide"
                             class="fixed inset-0 z-30 flex flex-col bg-white
                            sm:absolute sm:inset-auto sm:bottom-3.5 sm:right-3.5 sm:top-3.5
                            sm:w-[430px] sm:max-w-[calc(100%-28px)] sm:rounded-[var(--radius-card)]
                            sm:shadow-[var(--shadow-card)]"
                             style="overflow:hidden;">

                        <div class="h-[5px] shrink-0" style="background:var(--grad)"></div>

                        <!-- photo -->
                        <div class="relative shrink-0 bg-cover bg-center"
                             style="height: clamp(130px, 26vw, 190px);"
                             :style="{ backgroundImage: `url(${getMainSideImage(activeSide)})` }">

                            <!-- side selector buttons with status dots -->
                            <div class="absolute left-3 top-3 z-[3] flex flex-wrap gap-1.5">
                                <button v-for="side in activeObject.side_details" :key="side.code" type="button"
                                        class="flex items-center gap-1.5 rounded-full px-3 py-1.5 font-[var(--font-display)] text-[.78rem] font-bold shadow-[var(--shadow-soft)] transition"
                                        :class="activeSideCode === side.code
                                        ? 'bg-[var(--color-blue)] text-white'
                                        : 'bg-white/92 text-[var(--color-ink)]'"
                                        @click="selectSide(side.code)">
                                    <span class="inline-block h-1.5 w-1.5 flex-shrink-0 rounded-full"
                                          :class="activeSideCode === side.code
                                            ? 'bg-white/70'
                                            : sideStatusDotClass(activeObject, side.code)">
                                    </span>
                                    {{ side.code }}
                                </button>
                            </div>

                            <button type="button"
                                    class="absolute right-3 top-3 z-[3] grid h-9 w-9 place-items-center rounded-full bg-white/92 text-xl shadow-[var(--shadow-soft)]"
                                    @click="closeCard">×
                            </button>
                            <button v-if="activeSide.night_image_url" type="button"
                                    class="absolute bottom-3 right-3 z-[3] rounded-full bg-[rgba(13,19,32,.72)] px-3 py-1.5 text-[.64rem] font-bold tracking-[0.06em] text-white"
                                    @click="isNightPhoto = !isNightPhoto">
                                {{ isNightPhoto ? 'Днём' : 'Ночью' }}
                            </button>
                        </div>

                        <!-- scrollable content -->
                        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-4 sm:px-5 sm:py-5">
                            <h3 class="home-display text-[1.4rem] font-bold leading-tight">{{
                                    activeObject.address
                                }}</h3>
                            <p class="mt-1.5 text-[.68rem] font-bold uppercase tracking-[0.04em] text-[var(--color-muted)]">
                                GID {{ activeObject.id }}
                            </p>

                            <!-- details table -->
                            <dl class="mt-4 border-t border-[rgba(13,19,32,.08)]">
                                <div
                                    class="flex items-start justify-between gap-3 border-b border-[rgba(13,19,32,.08)] py-2.5 text-[.88rem]">
                                    <dt class="shrink-0 text-[var(--color-muted)]">Формат</dt>
                                    <dd class="min-w-0 break-words text-right font-semibold">{{
                                            activeObject.type || '—'
                                        }}
                                    </dd>
                                </div>
                                <div
                                    class="flex items-start justify-between gap-3 border-b border-[rgba(13,19,32,.08)] py-2.5 text-[.88rem]">
                                    <dt class="shrink-0 text-[var(--color-muted)]">Сторона</dt>
                                    <dd class="min-w-0 break-words text-right font-semibold">{{ activeSide.code }}</dd>
                                </div>
                                <div v-if="activeSide.description"
                                     class="flex items-start justify-between gap-3 border-b border-[rgba(13,19,32,.08)] py-2.5 text-[.88rem]">
                                    <dt class="shrink-0 text-[var(--color-muted)]">Описание</dt>
                                    <dd class="min-w-0 break-words text-right text-[var(--color-ink)]">
                                        {{ activeSide.description }}
                                    </dd>
                                </div>
                            </dl>

                            <!-- price + active side status -->
                            <div class="flex items-center justify-between gap-3 py-4">
                                <span class="shrink-0 text-[.88rem] text-[var(--color-muted)]">Прайс без НДС</span>
                                <b class="home-display text-[1.6rem] font-bold">{{ formatPrice(activeSide.price) }}</b>
                            </div>

                            <!-- active side status pill + free-from date -->
                            <div v-if="activeSideStatus" class="flex flex-wrap items-center gap-2">
                                <p class="u-pill" :class="activeSideStatus.pillClass">
                                    {{ activeSideStatus.text }}
                                </p>
                                <span v-if="activeSideStatus.freeFrom"
                                      class="text-[.78rem] text-[var(--color-muted)]">
                                    Освобождается {{ formatDateRU(activeSideStatus.freeFrom) }}
                                </span>
                                <span v-if="!activeSideStatus.busy && activeSideStatus.nextBookingDate"
                                      class="text-[.78rem] text-[var(--color-muted)]">
                                    Занята с {{ formatDateRU(activeSideStatus.nextBookingDate) }}
                                </span>
                            </div>

                            <!-- ═══ ALL SIDES — booking table ═══ -->
                            <section v-if="activeObject.side_details?.length" class="mt-5">
                                <p class="mb-2.5 text-[.7rem] font-bold uppercase tracking-[0.14em] text-[var(--color-muted)]">
                                    Доступность по сторонам
                                </p>

                                <div class="flex flex-col gap-2">
                                    <button v-for="side in activeObject.side_details" :key="`booking-${side.code}`"
                                            type="button"
                                            class="flex items-center gap-3 rounded-xl border p-3 text-left transition"
                                            :class="activeSideCode === side.code
                                            ? 'border-[var(--color-blue)] shadow-[0_0_0_3px_rgba(42,75,247,.12)]'
                                            : 'border-[rgba(13,19,32,.08)] hover:border-[rgba(42,75,247,.35)] hover:-translate-y-0.5'"
                                            @click="selectSide(side.code)">

                                        <!-- thumbnail -->
                                        <div
                                            class="relative h-14 w-[4.5rem] flex-shrink-0 overflow-hidden rounded-lg bg-[var(--color-bg-2)]">
                                            <img v-if="getPreviewSideImage(side) !== '/images/orig.png'"
                                                 :src="getPreviewSideImage(side)" :alt="`Сторона ${side.code}`"
                                                 class="h-full w-full object-cover">
                                            <span
                                                class="absolute left-1 top-1 rounded bg-white/90 px-1 text-[.58rem] font-bold text-[var(--color-ink)]">
                                                {{ side.code }}
                                            </span>
                                        </div>

                                        <!-- status + dates -->
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="inline-block h-2 w-2 flex-shrink-0 rounded-full"
                                                      :class="sideStatusDotClass(activeObject, side.code)">
                                                </span>
                                                <span class="text-[.86rem] font-bold text-[var(--color-ink)]">
                                                    {{
                                                        getSideStatus(activeObject, side.code, bookingRange.from, bookingRange.to).text
                                                    }}
                                                </span>
                                            </div>
                                            <!-- freeFrom date if busy -->
                                            <p v-if="getSideStatus(activeObject, side.code, bookingRange.from, bookingRange.to).freeFrom"
                                               class="mt-0.5 text-[.74rem] text-[var(--color-muted)]">
                                                Освободится {{
                                                    formatDateRU(getSideStatus(activeObject, side.code, bookingRange.from, bookingRange.to).freeFrom)
                                                }}
                                            </p>
                                            <!-- next booking warning if free -->
                                            <p v-else-if="getSideStatus(activeObject, side.code, bookingRange.from, bookingRange.to).nextBookingDate"
                                               class="mt-0.5 text-[.74rem] text-[var(--color-muted)]">
                                                Занята с {{
                                                    formatDateRU(getSideStatus(activeObject, side.code, bookingRange.from, bookingRange.to).nextBookingDate)
                                                }}
                                            </p>
                                            <!-- booking period hint -->
                                            <p v-if="getSideBookingRange(activeObject, side.code)"
                                               class="mt-0.5 text-[.72rem] text-[var(--color-muted)]">
                                                {{ getSideBookingRange(activeObject, side.code) }}
                                            </p>
                                            <!-- price -->
                                            <p class="mt-0.5 text-[.76rem] font-semibold text-[var(--color-blue)]">
                                                {{ formatPrice(side.price) }}
                                            </p>
                                        </div>

                                        <!-- select indicator -->
                                        <div class="flex-shrink-0">
                                            <span v-if="activeSideCode === side.code"
                                                  class="flex h-5 w-5 items-center justify-center rounded-full bg-[var(--color-blue)] text-white text-[.7rem]">✓</span>
                                        </div>
                                    </button>
                                </div>
                            </section>

                            <!-- bookings timeline for active side -->
                            <section v-if="activeSideBookings.length" class="mt-4">
                                <p class="mb-2 text-[.7rem] font-bold uppercase tracking-[0.14em] text-[var(--color-muted)]">
                                    Бронирования стороны {{ activeSide.code }}
                                </p>
                                <div class="flex flex-col gap-1.5">
                                    <div v-for="b in activeSideBookings" :key="b.id"
                                         class="flex items-center justify-between rounded-lg border border-[rgba(13,19,32,.08)] bg-white px-3 py-2 text-[.82rem]">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-block h-2 w-2 flex-shrink-0 rounded-full"
                                                  :class="b.booking_kind === 'hold' ? 'bg-[#F59E0B]' : 'bg-[#EF4444]'">
                                            </span>
                                            <span class="font-semibold text-[var(--color-ink)]">
                                                {{ formatDateRU(parseDate(b.start_date)) }}
                                                –
                                                {{ formatDateRU(parseDate(b.end_date)) }}
                                            </span>
                                        </div>
                                        <span class="text-[.74rem] text-[var(--color-muted)]">
                                            {{ b.booking_kind === 'hold' ? 'Ожидание оплаты' : 'Занята' }}
                                        </span>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <!-- ───── bottom action bar ───── -->
                        <div class="shrink-0 border-t border-[rgba(13,19,32,.08)] bg-[#fbfcfe] px-5 py-4">

                            <!-- booking conflict warning -->
                            <div v-if="activeSideStatus && activeSideStatus.busy"
                                 class="mb-3 rounded-xl border border-[rgba(239,68,68,.25)] bg-[rgba(239,68,68,.06)] px-3 py-2.5 text-[.82rem]">
                                <p class="font-semibold text-[#A12222]">
                                    Сторона {{ activeSide.code }} {{ activeSideStatus.text.toLowerCase() }}
                                </p>
                                <p v-if="activeSideStatus.freeFrom"
                                   class="mt-0.5 text-[.76rem] text-[var(--color-muted)]">
                                    Освобождается {{ formatDateRU(activeSideStatus.freeFrom) }}
                                </p>
                            </div>

                            <!-- period selected display -->
                            <div v-if="filters.bookingFrom && filters.bookingTo"
                                 class="mb-3 flex items-center justify-between rounded-xl bg-[var(--color-bg-2)] px-3 py-2 text-[.78rem]">
                                <span class="text-[var(--color-muted)]">Период бронирования</span>
                                <span class="font-semibold text-[var(--color-ink)]">
                                    {{
                                        formatDateRU(parseDate(filters.bookingFrom))
                                    }} – {{ formatDateRU(parseDate(filters.bookingTo)) }}
                                </span>
                            </div>
                            <div v-else
                                 class="mb-3 rounded-xl border border-dashed border-[rgba(13,19,32,.18)] px-3 py-2 text-[.78rem] text-[var(--color-muted)]">
                                Укажите период в фильтрах для точной проверки доступности
                            </div>

                            <p v-if="orderStatusMessage"
                               class="mb-2.5 text-[.85rem] font-semibold"
                               :class="orderStatusMessage.includes('добавлена') || orderStatusMessage.includes('Позиция') ? 'text-[var(--color-free)]' : 'text-[var(--color-busy)]'">
                                {{ orderStatusMessage }}
                            </p>

                            <button type="button"
                                    class="u-btn w-full justify-center"
                                    :class="activeSideStatus && activeSideStatus.busy ? 'u-btn--soft opacity-60' : 'u-btn--blue'"
                                    @click="addToCart">
                                <span v-if="activeSideStatus && activeSideStatus.busy">
                                    Забронировать (занята)
                                </span>
                                <span v-else>В корзину — сторона {{ activeSide.code }}</span>
                            </button>

                            <button type="button" class="u-btn u-btn--soft mt-2.5 w-full justify-center"
                                    @click="goToCart">
                                Перейти в корзину ({{ cartItems.length }})
                            </button>
                            <a :href="`/catalog/construction/${activeObject.id}`"
                               class="u-btn u-btn--soft mt-2.5 w-full justify-center">
                                Детальная карточка
                            </a>
                        </div>
                    </article>
                </transition>
            </section>
        </div>
    </div>
</template>

<script setup>
import {computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch} from 'vue'

const props = defineProps({
    filtersUrl: {type: String, required: true},
    advertisementsUrl: {type: String, required: true},
    ordersUrl: {type: String, required: true},
    cartUrl: {type: String, required: true},
    authUser: {type: Object, required: true},
})

// ─── Статусы (0 свободна, 1 занята, 3 бронь) ──────────────────────────────────

const STATUS_LEGEND = [
    {kind: 'free', label: 'Свободна', color: '#22A855'},
    {kind: 'firm', label: 'Занята', color: '#E24B4A'},
    {kind: 'hold', label: 'Бронь', color: '#F59E0B'},
]

const PLACEMARK_PRESET = {
    free: 'islands#greenCircleDotIcon',
    firm: 'islands#redCircleDotIcon',
    hold: 'islands#orangeCircleDotIcon',
}

// ─── Состояние ────────────────────────────────────────────────────────────────

const productTypes = ref([])
const constrTypes = ref([])
const objects = ref([])
const filters = reactive({productType: '', constrTypeId: '', bookingFrom: '', bookingTo: ''})
const isLoadingFilters = ref(false)
const isLoadingObjects = ref(false)
const mobileView = ref('list')
const activeDatePreset = ref('')

const mapContainer = ref(null)
const isMapLoaded = ref(false)
const mapError = ref('')
const activeObjectId = ref(null)
const activeSideCode = ref('')
const orderStatusMessage = ref('')
const cartItems = ref([])
const isNightPhoto = ref(false)
const searchQuery = ref('')

let map = null
let placemarks = new Map()
let applyTimer = null

const datePresets = [
    {key: 'week', label: '7 дней'},
    {key: 'month', label: '30 дней'},
]

// ─── Вычисляемые ──────────────────────────────────────────────────────────────

const bookingRange = computed(() => {
    const from = parseDate(filters.bookingFrom)
    const to = parseDate(filters.bookingTo)
    if (from && to && to < from) return {from: to, to: from}
    return {from, to}
})

const activeObject = computed(() =>
    objects.value.find(o => String(o.id) === String(activeObjectId.value)) || null,
)

const activeSide = computed(() => {
    if (!activeObject.value) return null
    return activeObject.value.side_details.find(s => s.code === activeSideCode.value)
        || activeObject.value.side_details[0]
        || null
})

const activeSideStatus = computed(() => {
    if (!activeObject.value || !activeSide.value) return null
    return getSideStatus(activeObject.value, activeSide.value.code, bookingRange.value.from, bookingRange.value.to)
})

/** Бронирования только для активной стороны */
const activeSideBookings = computed(() => {
    if (!activeObject.value || !activeSide.value) return []
    return (activeObject.value.bookings || []).filter(b => b.side_code === activeSide.value.code)
})

const hasActiveFilters = computed(() =>
    Boolean(filters.productType || filters.constrTypeId || filters.bookingFrom || filters.bookingTo),
)

const visibleObjects = computed(() => {
    const q = searchQuery.value.trim().toLowerCase()
    if (!q) return objects.value
    return objects.value.filter(o => {
        const fields = [o.code, o.place_number, o.placeNumber, o.address]
        return fields.some(v => v != null && String(v).toLowerCase().includes(q))
    })
})

// ─── Вспомогательные ──────────────────────────────────────────────────────────

function constructionNumber(item) {
    return item?.place_number ?? item?.placeNumber ?? item?.code ?? ''
}

function parseDate(value) {
    if (!value) return null
    const date = new Date(`${value}T00:00:00`)
    return isNaN(date.getTime()) ? null : date
}

function toInputDate(date) {
    return date.toISOString().slice(0, 10)
}

function formatDateRU(date) {
    if (!date) return ''
    return date.toLocaleDateString('ru-RU', {day: 'numeric', month: 'long', year: 'numeric'})
}

function applyDatePreset(presetKey) {
    activeDatePreset.value = presetKey
    const now = new Date()
    if (presetKey === 'clear') {
        filters.bookingFrom = '';
        filters.bookingTo = '';
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
    applyTimer = setTimeout(() => applyFilters(), 250)
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
    const preferred = useNight ? (side.night_image_url || side.night_image) : (side.image_url || side.image)
    const fallback = useNight ? (side.image_url || side.image) : (side.night_image_url || side.night_image)
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
            .filter(s => s && typeof s === 'object')
            .map(s => ({...s, code: String(s.code || '').toUpperCase()}))
            .filter(s => s.code !== '')
    }
    return sides.map(code => ({
        code: String(code).toUpperCase(), price: null,
        image_url: null, night_image_url: null,
        occupancy_status: 'free', occupancy_label: 'Свободна',
    }))
}

// ─── Статусы: бронирования с сайта + данные 1С ───────────────────────────────

/**
 * Возвращает статус стороны с учётом:
 *  1. Активных бронирований с сайта (bookings[])
 *  2. Данных из 1С (occupancy_status в side_details)
 *
 * Возвращает: { busy, kind, text, shortText, pillClass, freeFrom, nextBookingDate }
 */
function getSideStatus(item, sideCode, fromDate, toDate) {
    const bookings = (item?.bookings || []).filter(b => b.side_code === sideCode)
    const from = fromDate || new Date()
    const to = toDate || from

    // 1. Проверяем пересечение с бронированиями сайта
    const overlap = bookings.find(b => {
        const start = parseDate(b.start_date)
        const end = parseDate(b.end_date)
        return start && end && start <= to && end >= from
    })

    if (overlap) {
        const freeDate = parseDate(overlap.end_date)
        freeDate.setDate(freeDate.getDate() + 1)
        const kind = overlap.booking_kind ?? overlap.bookingKind ?? 'firm'
        const dateText = formatDateRU(freeDate)
        if (kind === 'hold') {
            return {
                busy: true, kind,
                text: `Занята (ожидание оплаты) до ${dateText}`,
                shortText: `до ${formatDateRU(parseDate(overlap.end_date))}`,
                pillClass: 'u-pill--hold',
                freeFrom: freeDate,
                nextBookingDate: null,
            }
        }
        return {
            busy: true, kind,
            text: `Занята до ${dateText}`,
            shortText: `до ${formatDateRU(parseDate(overlap.end_date))}`,
            pillClass: 'u-pill--busy',
            freeFrom: freeDate,
            nextBookingDate: null,
        }
    }

    // 2. Проверяем occupancy_status из 1С
    const sideDetail = (item?.side_details || []).find(s => s.code === sideCode)
    if (sideDetail?.occupancy_status === 'busy') {
        return {
            busy: true, kind: 'firm',
            text: 'Занята', shortText: 'Занята',
            pillClass: 'u-pill--busy',
            freeFrom: null, nextBookingDate: null,
        }
    }
    if (sideDetail?.occupancy_status === 'reserved') {
        return {
            busy: true, kind: 'hold',
            text: 'Бронь', shortText: 'Бронь',
            pillClass: 'u-pill--hold',
            freeFrom: null, nextBookingDate: null,
        }
    }

    // 3. Ищем ближайшее будущее бронирование (чтобы предупредить)
    const futureBookings = bookings
        .filter(b => {
            const start = parseDate(b.start_date)
            return start && start > to
        })
        .sort((a, b) => parseDate(a.start_date) - parseDate(b.start_date))

    const next = futureBookings[0]

    return {
        busy: false, kind: 'free',
        text: 'Свободна', shortText: 'Свободна',
        pillClass: 'u-pill--free',
        freeFrom: null,
        nextBookingDate: next ? parseDate(next.start_date) : null,
    }
}

/**
 * CSS-класс цветной точки для стороны (в кнопках и таблице)
 */
function sideStatusDotClass(item, sideCode) {
    const status = getSideStatus(item, sideCode, bookingRange.value.from, bookingRange.value.to)
    return {
        'bg-[#22C55E]': status.kind === 'free',
        'bg-[#EF4444]': status.kind === 'firm',
        'bg-[#F59E0B]': status.kind === 'hold',
    }
}

/**
 * Строка с периодом активного бронирования для стороны (для таблицы)
 */
function getSideBookingRange(item, sideCode) {
    const from = bookingRange.value.from || new Date()
    const to = bookingRange.value.to || from
    const booking = (item?.bookings || []).find(b => {
        if (b.side_code !== sideCode) return false
        const start = parseDate(b.start_date)
        const end = parseDate(b.end_date)
        return start && end && start <= to && end >= from
    })
    if (!booking) return null
    return `${formatDateRU(parseDate(booking.start_date))} – ${formatDateRU(parseDate(booking.end_date))}`
}

function getItemStatus(item, from, to) {
    const sideDetails = Array.isArray(item?.side_details) ? item.side_details : []
    const statuses = sideDetails.map(s => getSideStatus(item, s.code, from, to))

    if (!statuses.length) {
        return {busy: false, kind: 'free', text: 'Есть свободные стороны', pillClass: 'u-pill--free'}
    }
    if (!statuses.every(s => s.busy)) {
        return {busy: false, kind: 'free', text: 'Есть свободные стороны', pillClass: 'u-pill--free'}
    }
    if (statuses.some(s => s.kind === 'hold')) {
        return {busy: true, kind: 'hold', text: 'Занята (ожидание оплаты)', pillClass: 'u-pill--hold'}
    }
    return {busy: true, kind: 'firm', text: 'Занята', pillClass: 'u-pill--busy'}
}

// ─── Карта ────────────────────────────────────────────────────────────────────

function placemarkPreset(item) {
    const kind = getItemStatus(item, bookingRange.value.from, bookingRange.value.to).kind
    return PLACEMARK_PRESET[kind] ?? 'islands#greenCircleDotIcon'
}

function syncMapPlacemarks() {
    if (!map) return
    placemarks.forEach(p => map.geoObjects.remove(p))
    placemarks.clear()

    visibleObjects.value.forEach(item => {
        if (!item.location?.latitude) return
        const p = new window.ymaps.Placemark(
            [item.location.latitude, item.location.longitude],
            {hintContent: item.address || ''},
            {preset: placemarkPreset(item)},
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
    orderStatusMessage.value = ''
    if (map && item?.location) {
        map.setCenter([item.location.latitude, item.location.longitude], 15, {duration: 300})
    }
}

// ─── API ──────────────────────────────────────────────────────────────────────

async function loadFilters() {
    isLoadingFilters.value = true
    try {
        const res = await fetch(`${props.filtersUrl}${filters.productType ? '?productType=' + filters.productType : ''}`)
        const data = await res.json()
        productTypes.value = data.productTypes || []
        constrTypes.value = data.constrTypes || []
    } finally {
        isLoadingFilters.value = false
    }
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
    } finally {
        isLoadingObjects.value = false
    }
}

function applyCartPayload(data) {
    cartItems.value = Array.isArray(data?.items) ? data.items : []
}

async function loadCart() {
    try {
        const response = await fetch(props.cartUrl)
        if (!response.ok) throw new Error()
        applyCartPayload(await response.json())
    } catch {
        orderStatusMessage.value = 'Не удалось загрузить корзину.'
    }
}

// ─── Действия ─────────────────────────────────────────────────────────────────

function applyFilters() {
    activeObjectId.value = null
    loadAdvertisements()
}

function resetFilters() {
    Object.assign(filters, {productType: '', constrTypeId: '', bookingFrom: '', bookingTo: ''})
    activeDatePreset.value = ''
    searchQuery.value = ''
    applyFilters()
}

function selectSide(code) {
    activeSideCode.value = code
    isNightPhoto.value = false
    orderStatusMessage.value = ''
}

function closeCard() {
    activeObjectId.value = null
}

function goToCart() {
    window.location.href = '/cart'
}

async function addToCart() {
    if (!activeObject.value || !activeSide.value) return

    // Предупреждение если сторона занята
    const status = activeSideStatus.value
    if (status?.busy) {
        const confirmed = window.confirm(
            `Сторона ${activeSide.value.code} ${status.text.toLowerCase()}. ` +
            `Всё равно отправить заявку на бронирование?`
        )
        if (!confirmed) return
    }

    const startDate = filters.bookingFrom || toInputDate(new Date())
    const end = new Date();
    end.setDate(end.getDate() + 30)
    const endDate = filters.bookingTo || toInputDate(end)

    try {
        const response = await fetch(`${props.cartUrl}/items`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
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
        orderStatusMessage.value = `Сторона ${activeSide.value.code} добавлена в корзину (${startDate} – ${endDate}).`
    } catch {
        orderStatusMessage.value = 'Не удалось добавить позицию в корзину.'
    }
}

// ─── Карта: инициализация ─────────────────────────────────────────────────────

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
        mapError.value = 'Контейнер карты не найден.';
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
        }
    })
}

// ─── Watchers ─────────────────────────────────────────────────────────────────

watch(mobileView, async () => {
    await nextTick()
    const refit = () => {
        try {
            map?.container?.fitToViewport()
        } catch {
        }
    }
    refit();
    requestAnimationFrame(refit);
    setTimeout(refit, 120)
})

onBeforeUnmount(() => {
    map?.destroy();
    clearTimeout(applyTimer)
})

watch(() => filters.productType, () => {
    filters.constrTypeId = '';
    loadFilters()
})
watch(() => filters.constrTypeId, scheduleApplyFilters)
watch(() => [filters.bookingFrom, filters.bookingTo], ([from, to]) => {
    if (from && to && parseDate(to) < parseDate(from)) filters.bookingTo = from
    if (!from && !to) activeDatePreset.value = ''
    scheduleApplyFilters()
    syncMapPlacemarks()
})
watch(searchQuery, () => syncMapPlacemarks())
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.detail-enter-from,
.detail-leave-to {
    transform: translateX(110%);
    opacity: 0;
}

.detail-enter-active,
.detail-leave-active {
    transition: transform .42s cubic-bezier(.16, 1, .3, 1), opacity .3s;
}

@media (prefers-reduced-motion: reduce) {
    .detail-enter-active,
    .detail-leave-active {
        transition: none;
    }
}
</style>
