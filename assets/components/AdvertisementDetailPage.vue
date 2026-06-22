<template>
    <div class="detail-root">

        <!-- ── sticky header ── -->
        <header class="detail-header">
            <div class="detail-header__inner">
                <a href="/map/app" class="detail-back">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5">
                        <path d="M19 12H5M12 5l-7 7 7 7"/>
                    </svg>
                    Карта и каталог
                </a>
                <span v-if="item" class="detail-gid">GID {{ item.id }}</span>
            </div>
        </header>

        <!-- ── loading ── -->
        <div v-if="!item && !loadError" class="detail-loading">
            <div class="detail-loading__spinner"></div>
            <p>Загрузка карточки…</p>
        </div>

        <!-- ── error ── -->
        <div v-else-if="loadError" class="detail-error">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 8v4m0 4h.01"/>
            </svg>
            <h2>Не удалось загрузить</h2>
            <p>{{ loadError }}</p>
            <a href="/map/app" class="detail-btn detail-btn--blue">← Вернуться к каталогу</a>
        </div>

        <!-- ── main ── -->
        <main v-else-if="item" class="detail-main">
            <div class="detail-grid">

                <!-- ══════════════ LEFT: media ══════════════ -->
                <div class="detail-media">

                    <!-- hero photo -->
                    <div class="detail-hero">
                        <div class="detail-hero__img"
                             :style="heroImageStyle"
                             :class="{ 'detail-hero__img--loading': !heroLoaded }">
                            <img
                                :src="heroImageSrc"
                                alt=""
                                class="detail-hero__photo"
                                :class="{ 'loaded': heroLoaded }"
                                @load="heroLoaded = true"
                            />
                        </div>

                        <!-- gradient overlay -->
                        <div class="detail-hero__overlay"></div>

                        <!-- side buttons -->
                        <div class="detail-hero__sides">
                            <button
                                v-for="side in item.side_details"
                                :key="side.code"
                                class="detail-side-btn"
                                :class="{ 'detail-side-btn--active': activeSideCode === side.code }"
                                @click="selectSide(side.code)"
                            >
                                <span
                                    class="detail-side-btn__dot"
                                    :class="sideStatusDotClass(side.code)"
                                ></span>
                                Сторона {{ side.code }}
                            </button>
                        </div>

                        <!-- night toggle -->
                        <button
                            v-if="activeSide && activeSide.night_image_url"
                            class="detail-night-btn"
                            @click="isNight = !isNight"
                        >
                            <span>{{ isNight ? '☀' : '🌙' }}</span>
                            {{ isNight ? 'Дневное фото' : 'Ночное фото' }}
                        </button>

                        <!-- status badge top right -->
                        <div v-if="activeSideStatus" class="detail-hero__badge"
                             :class="`detail-hero__badge--${activeSideStatus.kind}`">
                            <span class="detail-hero__badge-dot"></span>
                            {{ activeSideStatus.shortText }}
                        </div>
                    </div>

                    <!-- ── sides grid ── -->
                    <section class="detail-section">
                        <h2 class="detail-section__title">Стороны конструкции</h2>
                        <div class="detail-sides-grid">
                            <button
                                v-for="side in item.side_details"
                                :key="`sg-${side.code}`"
                                class="detail-side-card"
                                :class="{ 'detail-side-card--active': activeSideCode === side.code }"
                                @click="selectSide(side.code)"
                            >
                                <!-- thumbnail -->
                                <div class="detail-side-card__thumb">
                                    <img
                                        v-if="resolveSideImage(side)"
                                        :src="resolveSideImage(side)"
                                        :alt="`Сторона ${side.code}`"
                                        class="detail-side-card__img"
                                    />
                                    <div v-else class="detail-side-card__no-img">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <path d="M21 15l-5-5L5 21"/>
                                        </svg>
                                    </div>
                                    <span class="detail-side-card__code">{{ side.code }}</span>
                                </div>

                                <!-- info -->
                                <div class="detail-side-card__info">
                                    <div class="detail-side-card__status">
                                        <span class="detail-side-card__dot"
                                              :class="sideStatusDotClass(side.code)">
                                        </span>
                                        <span class="detail-side-card__status-text">
                                            {{ getSideStatus(side.code).text }}
                                        </span>
                                    </div>
                                    <p v-if="getSideStatus(side.code).freeFrom" class="detail-side-card__date">
                                        Освободится {{ fmtDate(getSideStatus(side.code).freeFrom) }}
                                    </p>
                                    <p v-else-if="getSideStatus(side.code).nextBookingDate"
                                       class="detail-side-card__date">
                                        Занята с {{ fmtDate(getSideStatus(side.code).nextBookingDate) }}
                                    </p>
                                    <p v-if="getSideBookingRange(side.code)" class="detail-side-card__range">
                                        {{ getSideBookingRange(side.code) }}
                                    </p>
                                    <p class="detail-side-card__price">{{ fmtPrice(side.price) }}</p>
                                </div>

                                <!-- active indicator -->
                                <div v-if="activeSideCode === side.code" class="detail-side-card__check">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="3">
                                        <path d="M20 6L9 17l-5-5"/>
                                    </svg>
                                </div>
                            </button>
                        </div>
                    </section>

                    <!-- ── bookings timeline ── -->
                    <section v-if="activeSideBookingsFull.length" class="detail-section">
                        <h2 class="detail-section__title">Занятость стороны {{ activeSideCode }}</h2>
                        <div class="detail-timeline">
                            <div
                                v-for="b in activeSideBookingsFull"
                                :key="b.id"
                                class="detail-timeline__item"
                                :class="b.booking_kind === 'hold' ? 'detail-timeline__item--hold' : 'detail-timeline__item--busy'"
                            >
                                <div class="detail-timeline__dot"></div>
                                <div class="detail-timeline__body">
                                    <span class="detail-timeline__dates">
                                        {{
                                            fmtDateShort(parseDate(b.start_date))
                                        }} — {{ fmtDateShort(parseDate(b.end_date)) }}
                                    </span>
                                    <span class="detail-timeline__kind">
                                        {{ b.booking_kind === 'hold' ? 'Ожидание оплаты' : 'Забронировано' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ── description ── -->
                    <section v-if="activeSide && activeSide.description" class="detail-section">
                        <h2 class="detail-section__title">Описание</h2>
                        <p class="detail-description">{{ activeSide.description }}</p>
                    </section>

                    <!-- ── map ── -->
                    <section v-if="hasLocation" class="detail-section">
                        <h2 class="detail-section__title">Местоположение</h2>
                        <div class="detail-map-wrap" ref="mapEl">
                            <div v-show="!mapLoaded" class="detail-map-placeholder">
                                <span>Загрузка карты…</span>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- ══════════════ RIGHT: sticky panel ══════════════ -->
                <aside class="detail-panel">
                    <div class="detail-panel__inner">

                        <!-- eyebrow row: category + type chips -->
                        <div class="detail-panel__chips">
                            <span v-if="item.category" class="detail-chip detail-chip--cat">
                                {{ item.category }}
                            </span>
                            <span v-if="item.type" class="detail-chip detail-chip--type">
                                {{ item.type }}
                            </span>
                            <span v-if="item.place_number" class="detail-chip detail-chip--num">
                                № {{ item.place_number }}
                            </span>
                        </div>

                        <!-- THE SIGNATURE: address as billboard headline -->
                        <h1 class="detail-address">{{ item.address || 'Адрес не указан' }}</h1>

                        <!-- divider -->
                        <div class="detail-divider"></div>

                        <!-- active side status block -->
                        <div v-if="activeSideStatus" class="detail-status-block"
                             :class="`detail-status-block--${activeSideStatus.kind}`">
                            <div class="detail-status-block__row">
                                <span class="detail-status-block__dot"></span>
                                <span class="detail-status-block__text">{{ activeSideStatus.text }}</span>
                            </div>
                            <p v-if="activeSideStatus.freeFrom" class="detail-status-block__sub">
                                Освободится {{ fmtDate(activeSideStatus.freeFrom) }}
                            </p>
                            <p v-else-if="activeSideStatus.nextBookingDate" class="detail-status-block__sub">
                                Следующая бронь с {{ fmtDate(activeSideStatus.nextBookingDate) }}
                            </p>
                        </div>

                        <!-- price -->
                        <div class="detail-price-row">
                            <span class="detail-price-row__label">Прайс без НДС</span>
                            <span class="detail-price-row__value">{{ fmtPrice(activeSide?.price) }}</span>
                        </div>

                        <!-- period picker -->
                        <div class="detail-period">
                            <p class="detail-period__label">Период размещения</p>
                            <div class="detail-period__inputs">
                                <label class="detail-period__field">
                                    <span>С</span>
                                    <input v-model="bookFrom" type="date" class="detail-date-input"/>
                                </label>
                                <div class="detail-period__arrow">→</div>
                                <label class="detail-period__field">
                                    <span>По</span>
                                    <input v-model="bookTo" type="date" class="detail-date-input"/>
                                </label>
                            </div>

                            <!-- period presets -->
                            <div class="detail-presets">
                                <button
                                    v-for="p in presets"
                                    :key="p.key"
                                    class="detail-preset-btn"
                                    :class="{ 'detail-preset-btn--active': activePreset === p.key }"
                                    @click="applyPreset(p.key)"
                                >{{ p.label }}
                                </button>
                            </div>
                        </div>

                        <!-- conflict warning -->
                        <div v-if="activeSideStatus && activeSideStatus.busy"
                             class="detail-conflict">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2">
                                <path
                                    d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <span>
                                Сторона {{ activeSideCode }} занята в выбранный период.
                                <span v-if="activeSideStatus.freeFrom">
                                    Освобождается {{ fmtDate(activeSideStatus.freeFrom) }}.
                                </span>
                            </span>
                        </div>

                        <!-- CTA buttons -->
                        <div class="detail-actions">
                            <button
                                class="detail-btn detail-btn--blue"
                                :class="{ 'detail-btn--muted': activeSideStatus && activeSideStatus.busy }"
                                @click="addToCart"
                            >
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2">
                                    <circle cx="9" cy="21" r="1"/>
                                    <circle cx="20" cy="21" r="1"/>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                                </svg>
                                {{
                                    activeSideStatus && activeSideStatus.busy ? 'Забронировать (занята)' : `В корзину — ст. ${activeSideCode}`
                                }}
                            </button>

                            <a href="/cart" class="detail-btn detail-btn--soft">
                                Корзина ({{ cartCount }})
                                <span v-if="cartCount" class="detail-btn__badge">{{ cartCount }}</span>
                            </a>
                        </div>

                        <!-- status message -->
                        <p v-if="statusMsg" class="detail-status-msg"
                           :class="statusMsg.ok ? 'detail-status-msg--ok' : 'detail-status-msg--err'">
                            {{ statusMsg.text }}
                        </p>

                        <!-- meta row -->
                        <div class="detail-meta">
                            <div v-if="hasLocation" class="detail-meta__row">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2">
                                    <path d="M12 22s8-4.5 8-11.8A8 8 0 0 0 4 10.2C4 17.5 12 22 12 22z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                                <span>{{ item.location.latitude?.toFixed(4) }}, {{
                                        item.location.longitude?.toFixed(4)
                                    }}</span>
                            </div>
                            <div v-if="item.id" class="detail-meta__row">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2">
                                    <path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                                </svg>
                                <span>GID {{ item.id }}</span>
                            </div>
                        </div>
                    </div>
                </aside>

            </div>
        </main>
    </div>
</template>

<script setup>
import {ref, computed, onMounted, watch, nextTick} from 'vue'
import {normalizeAdvertisementRecord} from '../js/advertisementNormalize.js'

const props = defineProps({
    advertisementApiUrl: {type: String, required: true},
    cartUrl: {type: String, required: true},
})

// ─── State ────────────────────────────────────────────────────────────────────

const item = ref(null)
const loadError = ref('')
const activeSideCode = ref('')
const isNight = ref(false)
const heroLoaded = ref(false)
const bookFrom = ref('')
const bookTo = ref('')
const activePreset = ref('')
const cartCount = ref(0)
const statusMsg = ref(null)
const mapEl = ref(null)
const mapLoaded = ref(false)

const presets = [
    {key: 'week', label: '7 дней'},
    {key: 'month', label: '30 дней'},
    {key: 'q', label: '3 месяца'},
]

// ─── Computed ─────────────────────────────────────────────────────────────────

const activeSide = computed(() => {
    if (!item.value) return null
    return item.value.side_details.find(s => s.code === activeSideCode.value)
        || item.value.side_details[0]
        || null
})

const bookingFrom = computed(() => parseDate(bookFrom.value))
const bookingTo = computed(() => parseDate(bookTo.value))

const activeSideStatus = computed(() => {
    if (!item.value || !activeSide.value) return null
    return getSideStatus(activeSide.value.code)
})

const activeSideBookingsFull = computed(() => {
    if (!item.value || !activeSide.value) return []
    return (item.value.bookings || []).filter(b => b.side_code === activeSide.value.code)
})

const hasLocation = computed(() =>
    item.value?.location?.latitude && item.value?.location?.longitude
)

const heroImageSrc = computed(() => {
    if (!activeSide.value) return ''
    return resolveImageUrl(
        isNight.value
            ? (activeSide.value.night_image_url || activeSide.value.night_image || activeSide.value.image_url || activeSide.value.image)
            : (activeSide.value.image_url || activeSide.value.image)
    ) || '/images/orig.png'
})

const heroImageStyle = computed(() => ({
    backgroundImage: heroLoaded.value ? 'none' : `url(${heroImageSrc.value})`,
    backgroundSize: 'cover',
    backgroundPosition: 'center',
}))

// ─── Helpers ──────────────────────────────────────────────────────────────────

function parseDate(value) {
    if (!value) return null
    const d = new Date(`${value}T00:00:00`)
    return isNaN(d.getTime()) ? null : d
}

function fmtDate(date) {
    if (!date) return ''
    return date.toLocaleDateString('ru-RU', {day: 'numeric', month: 'long', year: 'numeric'})
}

function fmtDateShort(date) {
    if (!date) return ''
    return date.toLocaleDateString('ru-RU', {day: 'numeric', month: 'short'})
}

function fmtPrice(price) {
    if (!price) return 'По запросу'
    return `${new Intl.NumberFormat('ru-RU').format(price)} ₽`
}

function resolveImageUrl(url) {
    if (!url) return null
    const s = String(url).trim()
    const m = s.match(/^https?:\/\/cloud\.mail\.ru\/public\/([^/]+)\/([^/?#]+)/i)
    if (m) return `https://thumb.cloud.mail.ru/weblink/thumb/xw0/${m[1]}/${m[2]}?wm=true`
    return s || null
}

function resolveSideImage(side) {
    return resolveImageUrl(side.image_url || side.image) || null
}

function toInputDate(d) {
    return d.toISOString().slice(0, 10)
}

// ─── Status logic ─────────────────────────────────────────────────────────────

function getSideStatus(sideCode) {
    if (!item.value) return {
        busy: false,
        kind: 'free',
        text: 'Свободна',
        shortText: 'Свободна',
        pillClass: 'u-pill--free',
        freeFrom: null,
        nextBookingDate: null
    }

    const from = bookingFrom.value || new Date()
    const to = bookingTo.value || from

    const bookings = (item.value.bookings || []).filter(b => b.side_code === sideCode)

    // 1. Check site bookings for date overlap
    const overlap = bookings.find(b => {
        const s = parseDate(b.start_date)
        const e = parseDate(b.end_date)
        return s && e && s <= to && e >= from
    })

    if (overlap) {
        const freeDate = parseDate(overlap.end_date)
        freeDate.setDate(freeDate.getDate() + 1)
        const kind = overlap.booking_kind ?? overlap.bookingKind ?? 'firm'
        if (kind === 'hold') {
            return {
                busy: true,
                kind,
                text: `Занята (ожидание оплаты) до ${fmtDateShort(parseDate(overlap.end_date))}`,
                shortText: `до ${fmtDateShort(parseDate(overlap.end_date))}`,
                pillClass: 'u-pill--hold',
                freeFrom: freeDate,
                nextBookingDate: null
            }
        }
        return {
            busy: true,
            kind,
            text: `Занята до ${fmtDateShort(parseDate(overlap.end_date))}`,
            shortText: `до ${fmtDateShort(parseDate(overlap.end_date))}`,
            pillClass: 'u-pill--busy',
            freeFrom: freeDate,
            nextBookingDate: null
        }
    }

    // 2. Check occupancy from 1C
    const sd = (item.value.side_details || []).find(s => s.code === sideCode)
    if (sd?.occupancy_status === 'busy') return {
        busy: true,
        kind: 'firm',
        text: 'Занята',
        shortText: 'Занята',
        pillClass: 'u-pill--busy',
        freeFrom: null,
        nextBookingDate: null
    }
    if (sd?.occupancy_status === 'reserved') return {
        busy: true,
        kind: 'hold',
        text: 'Бронь',
        shortText: 'Бронь',
        pillClass: 'u-pill--hold',
        freeFrom: null,
        nextBookingDate: null
    }

    // 3. Check future bookings
    const future = bookings
        .filter(b => {
            const s = parseDate(b.start_date);
            return s && s > to
        })
        .sort((a, b) => parseDate(a.start_date) - parseDate(b.start_date))
    const next = future[0]

    return {
        busy: false,
        kind: 'free',
        text: 'Свободна',
        shortText: 'Свободна',
        pillClass: 'u-pill--free',
        freeFrom: null,
        nextBookingDate: next ? parseDate(next.start_date) : null
    }
}

function sideStatusDotClass(sideCode) {
    const s = getSideStatus(sideCode)
    if (s.kind === 'firm') return 'dot--busy'
    if (s.kind === 'hold') return 'dot--hold'
    return 'dot--free'
}

function getSideBookingRange(sideCode) {
    const from = bookingFrom.value || new Date()
    const to = bookingTo.value || from
    const b = (item.value?.bookings || []).find(b => {
        if (b.side_code !== sideCode) return false
        const s = parseDate(b.start_date), e = parseDate(b.end_date)
        return s && e && s <= to && e >= from
    })
    if (!b) return null
    return `${fmtDateShort(parseDate(b.start_date))} – ${fmtDateShort(parseDate(b.end_date))}`
}

// ─── Actions ──────────────────────────────────────────────────────────────────

function selectSide(code) {
    activeSideCode.value = code
    heroLoaded.value = false
    statusMsg.value = null
}

function applyPreset(key) {
    activePreset.value = key
    const now = new Date()
    const from = new Date(now)
    const to = new Date(now)
    if (key === 'week') to.setDate(now.getDate() + 7)
    if (key === 'month') to.setMonth(now.getMonth() + 1)
    if (key === 'q') to.setMonth(now.getMonth() + 3)
    bookFrom.value = toInputDate(from)
    bookTo.value = toInputDate(to)
}

async function addToCart() {
    if (!item.value || !activeSide.value) return

    const status = activeSideStatus.value
    if (status?.busy) {
        const ok = window.confirm(
            `Сторона ${activeSideCode.value} ${status.text.toLowerCase()}.\nВсё равно отправить заявку?`
        )
        if (!ok) return
    }

    const startDate = bookFrom.value || toInputDate(new Date())
    const end = new Date();
    end.setMonth(end.getMonth() + 1)
    const endDate = bookTo.value || toInputDate(end)

    try {
        const r = await fetch(`${props.cartUrl}/items`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                advertisementId: item.value.id,
                side: activeSideCode.value,
                startDate,
                endDate,
            }),
        })
        const data = await r.json()
        if (!r.ok) {
            statusMsg.value = {ok: false, text: data?.message || 'Ошибка при добавлении.'}
            return
        }
        cartCount.value = data?.count ?? (cartCount.value + 1)
        statusMsg.value = {
            ok: true,
            text: `Сторона ${activeSideCode.value} добавлена в корзину (${startDate} – ${endDate}).`,
        }
        setTimeout(() => {
            statusMsg.value = null
        }, 5000)
    } catch {
        statusMsg.value = {ok: false, text: 'Не удалось добавить. Проверьте соединение.'}
    }
}

// ─── Init ─────────────────────────────────────────────────────────────────────

onMounted(async () => {
    // default period: today + 30 days
    const now = new Date()
    const end = new Date();
    end.setDate(now.getDate() + 30)
    bookFrom.value = toInputDate(now)
    bookTo.value = toInputDate(end)
    activePreset.value = 'month'

    try {
        const res = await fetch(props.advertisementApiUrl)
        let data = null
        try {
            data = await res.json()
        } catch {
            data = null
        }
        if (!res.ok) {
            loadError.value = data?.message || 'Конструкция не найдена.';
            return
        }
        item.value = normalizeAdvertisementRecord(data)
        if (!item.value) {
            loadError.value = 'Некорректные данные конструкции.';
            return
        }
        activeSideCode.value = item.value.side_details?.[0]?.code || ''

        // load cart count
        try {
            const cartRes = await fetch(props.cartUrl)
            const cartData = await cartRes.json()
            cartCount.value = cartData?.count ?? 0
        } catch { /* ignore */
        }

        // init map after next tick
        if (item.value.location?.latitude) {
            await nextTick()
            initMap()
        }
    } catch {
        loadError.value = 'Не удалось загрузить данные. Проверьте соединение и обновите страницу.'
    }
})

function initMap() {
    if (!mapEl.value || !item.value) return
    const loadYmaps = () => {
        window.ymaps.ready(() => {
            const m = new window.ymaps.Map(mapEl.value, {
                center: [item.value.location.latitude, item.value.location.longitude],
                zoom: 15,
                controls: ['zoomControl'],
            })
            m.geoObjects.add(new window.ymaps.Placemark(
                [item.value.location.latitude, item.value.location.longitude],
                {hintContent: item.value.address || ''},
                {preset: 'islands#blueCircleDotIcon'},
            ))
            mapLoaded.value = true
            try {
                m.container.fitToViewport()
            } catch {
            }
        })
    }
    if (!window.ymaps) {
        const s = document.createElement('script')
        s.src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU'
        s.onload = loadYmaps
        document.head.appendChild(s)
    } else {
        loadYmaps()
    }
}

watch(activeSideCode, () => {
    heroLoaded.value = false
})
</script>

<style scoped>
/* ── Root ────────────────────────────────────────────────────────────────── */
.detail-root {
    min-height: calc(100dvh - 4rem);
    background: var(--color-bg, #F7F8FC);
    color: var(--color-ink, #0D1320);
}

/* ── Header ──────────────────────────────────────────────────────────────── */
.detail-header {
    position: sticky;
    top: 0;
    z-index: 20;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(13, 19, 32, .08);
}

.detail-header__inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: .75rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.detail-back {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .8125rem;
    font-weight: 700;
    color: var(--color-blue, #2A4BF7);
    text-decoration: none;
    transition: opacity .15s;
}

.detail-back:hover {
    opacity: .7;
}

.detail-gid {
    font-size: .75rem;
    font-family: monospace;
    color: var(--color-muted, #9CA3B0);
    letter-spacing: .04em;
}

/* ── Loading / Error ─────────────────────────────────────────────────────── */
.detail-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    min-height: 40vh;
    color: var(--color-muted);
    font-size: .875rem;
}

.detail-loading__spinner {
    width: 32px;
    height: 32px;
    border: 2.5px solid rgba(42, 75, 247, .15);
    border-top-color: var(--color-blue, #2A4BF7);
    border-radius: 50%;
    animation: spin .7s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.detail-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: .75rem;
    min-height: 50vh;
    text-align: center;
    padding: 2rem;
    color: var(--color-muted);
}

.detail-error h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--color-ink);
}

.detail-error p {
    font-size: .875rem;
    max-width: 380px;
}

/* ── Main layout ─────────────────────────────────────────────────────────── */
.detail-main {
    max-width: 1280px;
    margin: 0 auto;
    padding: 1.5rem 1.25rem 4rem;
}

.detail-grid {
    display: grid;
    gap: 2.5rem;
}

@media (min-width: 1024px) {
    .detail-grid {
        grid-template-columns: 1fr 400px;
        align-items: start;
    }
}

/* ── Hero ────────────────────────────────────────────────────────────────── */
.detail-hero {
    position: relative;
    border-radius: 1.25rem;
    overflow: hidden;
    background: var(--color-bg-2, #EAECF4);
    aspect-ratio: 16/9;
}

.detail-hero__img {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
}

.detail-hero__photo {
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity .4s ease;
}

.detail-hero__photo.loaded {
    opacity: 1;
}

.detail-hero__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(13, 19, 32, .65) 0%, transparent 55%);
    pointer-events: none;
}

.detail-hero__sides {
    position: absolute;
    bottom: 1rem;
    left: 1rem;
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    z-index: 2;
}

.detail-side-btn {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .4rem .875rem;
    border-radius: 999px;
    font-size: .78125rem;
    font-weight: 700;
    cursor: pointer;
    border: none;
    background: rgba(255, 255, 255, .2);
    color: #fff;
    backdrop-filter: blur(8px);
    transition: background .15s, transform .1s;
}

.detail-side-btn:hover {
    background: rgba(255, 255, 255, .3);
    transform: translateY(-1px);
}

.detail-side-btn--active {
    background: var(--color-blue, #2A4BF7);
}

.detail-side-btn__dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}

.detail-night-btn {
    position: absolute;
    top: .875rem;
    right: .875rem;
    z-index: 2;
    display: inline-flex;
    align-items: center;
    gap: .375rem;
    padding: .375rem .875rem;
    border-radius: 999px;
    background: rgba(13, 19, 32, .55);
    color: #fff;
    font-size: .6875rem;
    font-weight: 700;
    letter-spacing: .04em;
    cursor: pointer;
    border: none;
    backdrop-filter: blur(8px);
    transition: background .15s;
}

.detail-night-btn:hover {
    background: rgba(13, 19, 32, .75);
}

.detail-hero__badge {
    position: absolute;
    top: .875rem;
    left: .875rem;
    z-index: 2;
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .35rem .875rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 700;
}

.detail-hero__badge--free {
    background: rgba(34, 200, 94, .2);
    color: #0f5e30;
}

.detail-hero__badge--firm {
    background: rgba(239, 68, 68, .2);
    color: #7f1d1d;
}

.detail-hero__badge--hold {
    background: rgba(245, 158, 11, .2);
    color: #78350f;
}

.detail-hero__badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

/* ── Status dots ─────────────────────────────────────────────────────────── */
.dot--free {
    background: #22C55E;
}

.dot--busy {
    background: #EF4444;
}

.dot--hold {
    background: #F59E0B;
}

/* ── Sections ────────────────────────────────────────────────────────────── */
.detail-section {
    margin-top: 2rem;
}

.detail-section__title {
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--color-muted, #9CA3B0);
    margin-bottom: .875rem;
}

/* ── Sides grid ──────────────────────────────────────────────────────────── */
.detail-sides-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: .75rem;
}

.detail-side-card {
    display: flex;
    align-items: stretch;
    border: 1.5px solid rgba(13, 19, 32, .09);
    border-radius: .875rem;
    overflow: hidden;
    background: #fff;
    cursor: pointer;
    text-align: left;
    transition: border-color .15s, box-shadow .15s, transform .15s;
    position: relative;
}

.detail-side-card:hover {
    border-color: rgba(42, 75, 247, .35);
    box-shadow: 0 4px 20px rgba(42, 75, 247, .1);
    transform: translateY(-2px);
}

.detail-side-card--active {
    border-color: var(--color-blue, #2A4BF7);
    box-shadow: 0 0 0 3px rgba(42, 75, 247, .12);
}

.detail-side-card__thumb {
    position: relative;
    width: 80px;
    min-width: 80px;
    background: var(--color-bg-2, #EAECF4);
    overflow: hidden;
}

.detail-side-card__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.detail-side-card__no-img {
    width: 100%;
    height: 100%;
    min-height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-muted);
}

.detail-side-card__code {
    position: absolute;
    left: .3rem;
    top: .3rem;
    background: rgba(255, 255, 255, .9);
    font-size: .65rem;
    font-weight: 700;
    padding: .15rem .35rem;
    border-radius: .25rem;
    color: var(--color-ink);
}

.detail-side-card__info {
    padding: .625rem .75rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: .2rem;
}

.detail-side-card__status {
    display: flex;
    align-items: center;
    gap: .4rem;
}

.detail-side-card__dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}

.detail-side-card__status-text {
    font-size: .8125rem;
    font-weight: 700;
    color: var(--color-ink);
}

.detail-side-card__date {
    font-size: .71875rem;
    color: var(--color-muted);
}

.detail-side-card__range {
    font-size: .6875rem;
    color: var(--color-muted);
    font-style: italic;
}

.detail-side-card__price {
    font-size: .75rem;
    font-weight: 600;
    color: var(--color-blue, #2A4BF7);
    margin-top: auto;
}

.detail-side-card__check {
    position: absolute;
    top: .5rem;
    right: .5rem;
    width: 18px;
    height: 18px;
    background: var(--color-blue, #2A4BF7);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ── Timeline ────────────────────────────────────────────────────────────── */
.detail-timeline {
    display: flex;
    flex-direction: column;
    gap: .5rem;
}

.detail-timeline__item {
    display: flex;
    align-items: center;
    gap: .875rem;
    padding: .625rem .875rem;
    border-radius: .75rem;
    border: 1px solid transparent;
}

.detail-timeline__item--busy {
    background: #FEE8E8;
    border-color: rgba(239, 68, 68, .2);
}

.detail-timeline__item--hold {
    background: #FFF4DC;
    border-color: rgba(245, 158, 11, .25);
}

.detail-timeline__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.detail-timeline__item--busy .detail-timeline__dot {
    background: #EF4444;
}

.detail-timeline__item--hold .detail-timeline__dot {
    background: #F59E0B;
}

.detail-timeline__body {
    display: flex;
    flex-direction: column;
    gap: .1rem;
}

.detail-timeline__dates {
    font-size: .8125rem;
    font-weight: 700;
    color: var(--color-ink);
}

.detail-timeline__kind {
    font-size: .71875rem;
    color: var(--color-muted);
}

/* ── Description ─────────────────────────────────────────────────────────── */
.detail-description {
    font-size: .875rem;
    line-height: 1.7;
    color: var(--color-ink);
    background: #fff;
    border: 1px solid rgba(13, 19, 32, .08);
    border-radius: .875rem;
    padding: 1rem 1.25rem;
}

/* ── Map ─────────────────────────────────────────────────────────────────── */
.detail-map-wrap {
    border-radius: 1rem;
    overflow: hidden;
    height: 280px;
    background: #DDE3F0;
    position: relative;
}

.detail-map-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8125rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--color-muted);
}

/* ── Right panel ─────────────────────────────────────────────────────────── */
.detail-panel {
    position: relative;
}

@media (min-width: 1024px) {
    .detail-panel {
        position: sticky;
        top: 5.5rem;
    }
}

.detail-panel__inner {
    background: #fff;
    border: 1px solid rgba(13, 19, 32, .09);
    border-radius: 1.375rem;
    padding: 1.75rem;
    display: flex;
    flex-direction: column;
    gap: 1.125rem;
    box-shadow: 0 8px 40px rgba(13, 19, 32, .07);
}

/* chips */
.detail-panel__chips {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
}

.detail-chip {
    display: inline-flex;
    align-items: center;
    padding: .25rem .625rem;
    border-radius: 999px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .02em;
}

.detail-chip--cat {
    background: var(--color-tint-blue, #E8EBF7);
    color: var(--color-blue, #2A4BF7);
}

.detail-chip--type {
    background: rgba(13, 19, 32, .07);
    color: var(--color-ink);
}

.detail-chip--num {
    background: rgba(13, 19, 32, .05);
    color: var(--color-muted);
    font-family: monospace;
}

/* THE SIGNATURE: billboard address */
.detail-address {
    font-size: clamp(1.5rem, 3.5vw, 2.125rem);
    font-weight: 900;
    line-height: 1.1;
    letter-spacing: -0.03em;
    color: var(--color-ink);
    font-style: normal;
    word-break: break-word;
}

.detail-divider {
    height: 1px;
    background: rgba(13, 19, 32, .08);
}

/* status block */
.detail-status-block {
    border-radius: .875rem;
    padding: .875rem 1rem;
}

.detail-status-block--free {
    background: #E5F8ED;
}

.detail-status-block--firm {
    background: #FEE8E8;
}

.detail-status-block--hold {
    background: #FFF4DC;
}

.detail-status-block__row {
    display: flex;
    align-items: center;
    gap: .5rem;
}

.detail-status-block__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
    background: currentColor;
}

.detail-status-block--free .detail-status-block__dot {
    background: #22A855;
}

.detail-status-block--firm .detail-status-block__dot {
    background: #EF4444;
}

.detail-status-block--hold .detail-status-block__dot {
    background: #F59E0B;
}

.detail-status-block__text {
    font-size: .875rem;
    font-weight: 700;
}

.detail-status-block--free .detail-status-block__text {
    color: #1A7A40;
}

.detail-status-block--firm .detail-status-block__text {
    color: #A12222;
}

.detail-status-block--hold .detail-status-block__text {
    color: #875800;
}

.detail-status-block__sub {
    font-size: .75rem;
    margin-top: .25rem;
    color: var(--color-muted);
}

/* price row */
.detail-price-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
}

.detail-price-row__label {
    font-size: .8125rem;
    color: var(--color-muted);
}

.detail-price-row__value {
    font-size: 1.75rem;
    font-weight: 900;
    letter-spacing: -0.03em;
    color: var(--color-ink);
}

/* period picker */
.detail-period {
}

.detail-period__label {
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--color-muted);
    margin-bottom: .625rem;
}

.detail-period__inputs {
    display: flex;
    align-items: center;
    gap: .5rem;
}

.detail-period__field {
    display: flex;
    flex-direction: column;
    gap: .25rem;
    flex: 1;
    font-size: .6875rem;
    font-weight: 600;
    color: var(--color-muted);
    letter-spacing: .04em;
    text-transform: uppercase;
}

.detail-period__arrow {
    font-size: .875rem;
    color: var(--color-muted);
    flex-shrink: 0;
    padding-top: 1.25rem;
}

.detail-date-input {
    width: 100%;
    padding: .5rem .625rem;
    border: 1.5px solid rgba(13, 19, 32, .12);
    border-radius: .625rem;
    font-size: .8125rem;
    font-weight: 500;
    color: var(--color-ink);
    background: var(--color-bg, #F7F8FC);
    outline: none;
    transition: border-color .15s;
    appearance: none;
}

.detail-date-input:focus {
    border-color: var(--color-blue, #2A4BF7);
}

.detail-presets {
    display: flex;
    gap: .4rem;
    margin-top: .625rem;
    flex-wrap: wrap;
}

.detail-preset-btn {
    flex: 1;
    padding: .4rem .5rem;
    border: 1.5px solid rgba(13, 19, 32, .12);
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 700;
    color: var(--color-muted);
    background: #fff;
    cursor: pointer;
    transition: border-color .15s, color .15s, background .15s;
    white-space: nowrap;
}

.detail-preset-btn:hover {
    border-color: var(--color-blue);
    color: var(--color-blue);
}

.detail-preset-btn--active {
    border-color: var(--color-ink);
    background: var(--color-ink);
    color: #fff;
}

/* conflict warning */
.detail-conflict {
    display: flex;
    align-items: flex-start;
    gap: .5rem;
    padding: .75rem .875rem;
    border-radius: .75rem;
    background: #FEF3C7;
    border: 1px solid rgba(245, 158, 11, .3);
    font-size: .8rem;
    line-height: 1.5;
    color: #78350F;
}

.detail-conflict svg {
    flex-shrink: 0;
    margin-top: 1px;
}

/* CTA */
.detail-actions {
    display: flex;
    flex-direction: column;
    gap: .5rem;
}

.detail-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    padding: .8125rem 1.25rem;
    border-radius: .875rem;
    font-size: .875rem;
    font-weight: 700;
    cursor: pointer;
    border: none;
    text-decoration: none;
    transition: opacity .15s, transform .1s, box-shadow .15s;
}

.detail-btn:hover {
    transform: translateY(-1px);
}

.detail-btn:active {
    transform: translateY(0);
}

.detail-btn--blue {
    background: var(--color-blue, #2A4BF7);
    color: #fff;
    box-shadow: 0 4px 16px rgba(42, 75, 247, .3);
}

.detail-btn--blue:hover {
    box-shadow: 0 6px 24px rgba(42, 75, 247, .4);
}

.detail-btn--soft {
    background: rgba(13, 19, 32, .06);
    color: var(--color-ink);
}

.detail-btn--soft:hover {
    background: rgba(13, 19, 32, .1);
}

.detail-btn--muted {
    background: rgba(13, 19, 32, .35) !important;
    box-shadow: none !important;
}

.detail-btn__badge {
    background: rgba(255, 255, 255, .25);
    border-radius: 999px;
    padding: .1rem .45rem;
    font-size: .7rem;
}

/* status message */
.detail-status-msg {
    font-size: .8125rem;
    padding: .625rem .875rem;
    border-radius: .625rem;
    font-weight: 600;
}

.detail-status-msg--ok {
    background: #E5F8ED;
    color: #1A7A40;
}

.detail-status-msg--err {
    background: #FEE8E8;
    color: #A12222;
}

/* meta */
.detail-meta {
    display: flex;
    flex-direction: column;
    gap: .35rem;
}

.detail-meta__row {
    display: flex;
    align-items: center;
    gap: .4rem;
    font-size: .71875rem;
    color: var(--color-muted);
}

.detail-meta__row svg {
    flex-shrink: 0;
}
</style>
