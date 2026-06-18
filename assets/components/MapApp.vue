<template>
    <div class="flex min-h-0 flex-1 flex-col bg-[var(--color-bg)] text-[var(--color-ink)] lg:overflow-hidden">

        <!-- toolbar -->
        <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <div class="flex items-center gap-3">
                <h1 class="home-display text-lg font-bold">Карта и каталог</h1>
                <span class="u-pill" style="background:var(--color-tint-blue);color:var(--color-blue)">{{ objects.length }} конструкций</span>
            </div>
            <a href="/cart" class="u-btn u-btn--blue !px-4 !py-2 text-sm">Корзина ({{ cartItems.length }}) <span class="arr">→</span></a>
        </div>

        <!-- mobile tabs -->
        <div class="flex gap-2 px-4 pb-2 lg:hidden">
            <button
                type="button"
                class="flex-1 rounded-full py-2.5 text-[.82rem] font-bold transition"
                :class="mobileView === 'list' ? 'bg-[var(--color-ink)] text-white' : 'bg-white text-[var(--color-muted)] shadow-[var(--shadow-soft)]'"
                @click="mobileView = 'list'"
            >Фильтры</button>
            <button
                type="button"
                class="flex-1 rounded-full py-2.5 text-[.82rem] font-bold transition"
                :class="mobileView === 'map' ? 'bg-[var(--color-ink)] text-white' : 'bg-white text-[var(--color-muted)] shadow-[var(--shadow-soft)]'"
                @click="mobileView = 'map'"
            >Карта</button>
        </div>

        <div class="flex min-h-0 flex-1 flex-col gap-4 p-4 pt-0 lg:flex-row lg:overflow-hidden">

            <!-- sidebar -->
            <aside
                class="u-card flex w-full flex-col overflow-hidden lg:w-[372px] lg:shrink-0"
                :class="mobileView === 'map' ? 'hidden lg:flex' : 'flex'"
            >
                <div class="flex items-start justify-between border-b border-[rgba(13,19,32,.08)] px-5 py-4">
                    <div>
                        <h2 class="text-lg font-extrabold tracking-tight">Инвентарь</h2>
                        <p class="mt-0.5 text-xs text-[var(--color-muted)]">Подбор поверхностей</p>
                    </div>
                    <span v-if="hasActiveFilters" class="u-pill" style="background:var(--color-tint-blue);color:var(--color-blue)">Фильтр</span>
                </div>

                <div class="flex max-h-[44vh] flex-col gap-4 overflow-auto border-b border-[rgba(13,19,32,.08)] p-5 lg:max-h-none">
                    <div>
                        <label class="u-field-label">Категория конструкции</label>
                        <select v-model="filters.productType" class="u-select">
                            <option value="">Все категории</option>
                            <option v-for="item in productTypes" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="u-field-label">Тип конструкции</label>
                        <select v-model="filters.constrTypeId" :disabled="isLoadingFilters" class="u-select disabled:cursor-not-allowed disabled:opacity-60">
                            <option value="">Все типы</option>
                            <option v-for="item in constrTypes" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="u-field-label">Период</label>
                        <div class="flex gap-2">
                            <button
                                v-for="preset in datePresets"
                                :key="preset.key"
                                type="button"
                                class="flex-1 rounded-full border py-2.5 text-[.78rem] font-bold transition"
                                :class="activeDatePreset === preset.key
                                    ? 'border-[var(--color-ink)] bg-[var(--color-ink)] text-white'
                                    : 'border-[rgba(13,19,32,.12)] bg-white text-[var(--color-muted)] hover:border-[var(--color-blue)] hover:text-[var(--color-blue)]'"
                                @click="applyDatePreset(preset.key)"
                            >{{ preset.label }}</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div><label class="u-field-label">Свободно с</label><input v-model="filters.bookingFrom" type="date" class="u-input"></div>
                        <div><label class="u-field-label">Свободно до</label><input v-model="filters.bookingTo" type="date" class="u-input"></div>
                    </div>
                    <div class="flex gap-2.5">
                        <button type="button" class="u-btn u-btn--soft flex-1 justify-center !py-3 text-[.82rem]" @click="resetFilters">Сбросить</button>
                        <button type="button" class="u-btn u-btn--blue flex-1 justify-center !py-3 text-[.82rem]" @click="applyFilters">Подобрать</button>
                    </div>
                </div>

                <div class="flex items-center justify-between border-b border-[rgba(13,19,32,.08)] px-5 py-3 text-[.74rem] font-bold text-[var(--color-muted)]">
                    <span>Найдено конструкций</span>
                    <span class="rounded-full bg-[var(--color-tint-blue)] px-2.5 py-0.5 font-[var(--font-display)] text-[.88rem] text-[var(--color-blue)]">{{ objects.length }}</span>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-3.5">
                    <div v-if="isLoadingObjects" class="p-4 text-sm text-[var(--color-muted)]">Загрузка объектов…</div>
                    <div v-else-if="objects.length === 0" class="rounded-[var(--radius-soft)] border border-dashed border-[rgba(13,19,32,.18)] bg-white p-4 text-sm text-[var(--color-muted)]">
                        По выбранным фильтрам ничего не найдено.
                    </div>

                    <button
                        v-for="item in objects"
                        :key="item.id"
                        type="button"
                        class="mb-3 w-full rounded-[var(--radius-soft)] border bg-white p-4 text-left transition hover:-translate-y-0.5 hover:shadow-[var(--shadow-soft)]"
                        :class="activeObjectId === item.id ? 'border-[var(--color-blue)] shadow-[0_0_0_3px_rgba(42,75,247,.15)]' : 'border-[rgba(13,19,32,.08)] hover:border-[rgba(42,75,247,.35)]'"
                        @click="focusObject(item.id)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="line-clamp-2 text-[.96rem] font-bold leading-snug">{{ item.address || 'Адрес не указан' }}</h3>
                            <span class="shrink-0 rounded-full bg-[var(--color-bg-2)] px-2 py-0.5 font-[var(--font-display)] text-[.66rem] font-bold text-[var(--color-muted)]">#{{ item.id }}</span>
                        </div>
                        <p class="mt-1.5 text-[.82rem] text-[var(--color-muted)]">{{ item.category || '—' }} • {{ item.type || '—' }}</p>
                        <p class="mt-0.5 text-[.78rem] text-[var(--color-muted)]">Стороны: {{ formatSides(item.sides) }}</p>
                        <span class="u-pill mt-2.5" :class="getItemStatus(item, bookingRange.from, bookingRange.to).pillClass">
                            {{ getItemStatus(item, bookingRange.from, bookingRange.to).text }}
                        </span>
                    </button>
                </div>
            </aside>

            <!-- map -->
            <section
                class="u-card relative flex min-h-0 flex-1 overflow-hidden"
                :class="mobileView === 'list' ? 'hidden lg:flex' : 'flex'"
            >
                <div v-if="mapError" class="flex min-h-[280px] flex-1 items-center justify-center p-6 text-center text-sm text-[var(--color-busy)]">
                    {{ mapError }}
                </div>

                <div v-else class="relative min-h-[320px] w-full flex-1 max-lg:min-h-[52vh] lg:min-h-0">
                    <div ref="mapContainer" class="absolute inset-0 bg-[#EEF1FA]"></div>
                    <div v-show="!isMapLoaded" class="absolute inset-0 z-10 flex items-center justify-center bg-[#EEF1FA] text-xs font-bold uppercase tracking-[0.18em] text-[var(--color-muted)]">
                        Загрузка карты…
                    </div>
                    <span class="pointer-events-none absolute left-4 top-4 z-[5] rounded-full bg-white/92 px-3 py-1.5 text-[.64rem] font-bold tracking-[0.08em] text-[var(--color-blue)] shadow-[var(--shadow-soft)]">● Карта · live</span>
                </div>

                <!-- detail card -->
                <transition name="detail">
                    <article
                        v-if="activeObject && activeSide"
                        class="absolute inset-0 z-30 flex flex-col overflow-hidden bg-white shadow-[var(--shadow-card)] sm:inset-auto sm:right-3.5 sm:top-3.5 sm:bottom-3.5 sm:w-[430px] sm:max-w-[calc(100%-28px)] sm:rounded-[var(--radius-card)]"
                    >
                        <div class="h-[5px] shrink-0" style="background:var(--grad)"></div>

                        <div class="relative h-[200px] shrink-0 bg-cover bg-center" :style="{ backgroundImage: `url(${getMainSideImage(activeSide)})` }">
                            <div class="absolute left-3 top-3 z-[3] flex gap-1.5">
                                <button
                                    v-for="side in activeObject.side_details"
                                    :key="side.code"
                                    type="button"
                                    class="rounded-full px-3 py-1.5 font-[var(--font-display)] text-[.78rem] font-bold shadow-[var(--shadow-soft)] transition"
                                    :class="activeSideCode === side.code ? 'bg-[var(--color-blue)] text-white' : 'bg-white/92 text-[var(--color-ink)]'"
                                    @click="selectSide(side.code)"
                                >{{ side.code }}</button>
                            </div>
                            <button type="button" class="absolute right-3 top-3 z-[3] grid h-9 w-9 place-items-center rounded-full bg-white/92 text-xl shadow-[var(--shadow-soft)]" @click="closeCard">×</button>
                            <button
                                v-if="activeSide.night_image_url"
                                type="button"
                                class="absolute bottom-3 right-3 z-[3] rounded-full bg-[rgba(13,19,32,.72)] px-3 py-1.5 text-[.64rem] font-bold tracking-[0.06em] text-white"
                                @click="isNightPhoto = !isNightPhoto"
                            >{{ isNightPhoto ? 'Днём' : 'Ночью' }}</button>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5">
                            <h3 class="home-display text-[1.4rem] font-bold leading-tight">{{ activeObject.address }}</h3>
                            <p class="mt-1.5 text-[.68rem] font-bold uppercase tracking-[0.04em] text-[var(--color-muted)]">GID {{ activeObject.id }}</p>

                            <dl class="mt-4 border-t border-[rgba(13,19,32,.08)]">
                                <div class="flex justify-between gap-4 border-b border-[rgba(13,19,32,.08)] py-2.5 text-[.92rem]"><dt class="text-[var(--color-muted)]">Формат</dt><dd class="text-right font-semibold">{{ activeObject.type || '—' }}</dd></div>
                                <div class="flex justify-between gap-4 border-b border-[rgba(13,19,32,.08)] py-2.5 text-[.92rem]"><dt class="text-[var(--color-muted)]">Сторона</dt><dd class="text-right font-semibold">{{ activeSide.code }}</dd></div>
                                <div class="flex justify-between gap-4 border-b border-[rgba(13,19,32,.08)] py-2.5 text-[.92rem]"><dt class="text-[var(--color-muted)]">Описание</dt><dd class="text-right text-[var(--color-ink)]">{{ activeSide.description || '—' }}</dd></div>
                            </dl>

                            <div class="flex items-center justify-between py-4">
                                <span class="text-[.92rem] text-[var(--color-muted)]">Прайс без НДС</span>
                                <b class="home-display text-[1.8rem] font-bold">{{ formatPrice(activeSide.price) }}</b>
                            </div>

                            <p v-if="activeSideStatus" class="u-pill" :class="activeSideStatus.pillClass">{{ activeSideStatus.text }}</p>

                            <section v-if="activeObject.side_details?.length" class="mt-5">
                                <p class="mb-2 text-[.7rem] font-bold uppercase tracking-[0.14em] text-[var(--color-muted)]">Фотографии сторон</p>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        v-for="side in activeObject.side_details"
                                        :key="`photo-${side.code}`"
                                        type="button"
                                        class="group relative aspect-[4/3] overflow-hidden rounded-xl border transition"
                                        :class="activeSideCode === side.code ? 'border-[var(--color-blue)] ring-2 ring-[rgba(42,75,247,.25)]' : 'border-[rgba(13,19,32,.08)] hover:border-[rgba(42,75,247,.4)]'"
                                        @click="selectSide(side.code)"
                                    >
                                        <img :src="getPreviewSideImage(side)" :alt="`Сторона ${side.code}`" class="h-full w-full object-cover">
                                        <span class="absolute left-1.5 top-1.5 rounded-full bg-white/92 px-1.5 py-0.5 text-[.62rem] font-bold text-[var(--color-ink)]">{{ side.code }}</span>
                                    </button>
                                </div>
                            </section>
                        </div>

                        <div class="shrink-0 border-t border-[rgba(13,19,32,.08)] bg-[#fbfcfe] px-5 py-4">
                            <p v-if="orderStatusMessage" class="mb-2.5 text-[.85rem] font-semibold text-[var(--color-free)]">{{ orderStatusMessage }}</p>
                            <button type="button" class="u-btn u-btn--blue w-full justify-center" @click="addToCart">В корзину</button>
                            <button type="button" class="u-btn u-btn--soft mt-2.5 w-full justify-center" @click="goToCart">Перейти в корзину ({{ cartItems.length }})</button>
                            <a :href="`/catalog/construction/${activeObject.id}`" class="u-btn u-btn--soft mt-2.5 w-full justify-center">Детальная карточка</a>
                        </div>
                    </article>
                </transition>
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
    authUser: { type: Object, required: true },
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

// pillClass — классы под новую систему (.u-pill--free/busy/hold)
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
            return { busy: true, kind, text: `Занята (ожидание оплаты) до ${dateText}`, pillClass: 'u-pill--hold' }
        }
        return { busy: true, kind, text: `Занята до ${dateText}`, pillClass: 'u-pill--busy' }
    }

    return { busy: false, kind: 'free', text: 'Свободна', pillClass: 'u-pill--free' }
}

function getItemStatus(item, from, to) {
    const sideDetails = Array.isArray(item?.side_details) ? item.side_details : []
    const statuses = sideDetails.map(s => getSideStatus(item, s.code, from, to))
    if (!statuses.length) {
        return { busy: false, kind: 'free', text: 'Есть свободные стороны', pillClass: 'u-pill--free' }
    }
    const busyAll = statuses.every(s => s.busy)
    if (!busyAll) {
        return { busy: false, kind: 'free', text: 'Есть свободные стороны', pillClass: 'u-pill--free' }
    }

    const anyHold = statuses.some(s => s.kind === 'hold')
    if (anyHold) {
        return { busy: true, kind: 'hold', text: 'Занята (ожидание оплаты)', pillClass: 'u-pill--hold' }
    }

    return { busy: true, kind: 'firm', text: 'Занята', pillClass: 'u-pill--busy' }
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
function placemarkPreset(item) {
    const kind = getItemStatus(item, bookingRange.value.from, bookingRange.value.to).kind
    if (kind === 'hold') return 'islands#orangeCircleDotIcon'
    if (kind === 'firm') return 'islands#redCircleDotIcon'
    return 'islands#greenCircleDotIcon'
}

function syncMapPlacemarks() {
    if (!map) return
    placemarks.forEach(p => map.geoObjects.remove(p))
    placemarks.clear()

    objects.value.forEach(item => {
        if (!item.location?.latitude) return
        const p = new window.ymaps.Placemark(
            [item.location.latitude, item.location.longitude],
            {},
            { preset: placemarkPreset(item) }
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
    syncMapPlacemarks()
})
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
    .detail-leave-active { transition: none; }
}
</style>
