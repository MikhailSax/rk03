<template>
    <div class="flex min-h-0 flex-1 flex-col overflow-hidden bg-white text-gray-900 lg:flex-row">
        <div class="relative flex min-h-[220px] shrink-0 flex-col border-b border-gray-200 bg-black/5 lg:min-h-0 lg:w-[min(52%,560px)] lg:border-b-0 lg:border-r">
            <div class="relative flex min-h-[200px] flex-1 items-center justify-center bg-gray-900/5">
                <img
                    :src="currentSlide?.url || '/images/orig.png'"
                    :alt="currentSlide?.label || 'Фото'"
                    class="max-h-[min(48vh,420px)] w-full object-contain lg:max-h-[min(72vh,640px)]"
                />
                <button
                    v-if="slides.length > 1"
                    type="button"
                    class="site-header-font absolute left-2 top-1/2 z-10 -translate-y-1/2 border border-white/40 bg-black/40 px-2 py-3 text-[10px] font-semibold uppercase tracking-wider text-white backdrop-blur-sm hover:bg-black/60"
                    aria-label="Предыдущее фото"
                    @click="prevSlide"
                >
                    ‹
                </button>
                <button
                    v-if="slides.length > 1"
                    type="button"
                    class="site-header-font absolute right-2 top-1/2 z-10 -translate-y-1/2 border border-white/40 bg-black/40 px-2 py-3 text-[10px] font-semibold uppercase tracking-wider text-white backdrop-blur-sm hover:bg-black/60"
                    aria-label="Следующее фото"
                    @click="nextSlide"
                >
                    ›
                </button>
                <p
                    v-if="currentSlide"
                    class="site-header-font pointer-events-none absolute bottom-3 left-3 right-3 bg-black/55 px-3 py-1.5 text-center text-[10px] font-semibold uppercase tracking-wider text-white backdrop-blur-sm"
                >
                    {{ currentSlide.label }}
                </p>
            </div>
            <div
                v-if="slides.length > 1"
                class="flex gap-2 overflow-x-auto border-t border-gray-200 bg-white p-3"
            >
                <button
                    v-for="(slide, idx) in slides"
                    :key="idx"
                    type="button"
                    class="relative h-16 w-20 shrink-0 overflow-hidden border-2 transition"
                    :class="idx === galleryIndex ? 'border-[#e85d4c]' : 'border-gray-200 opacity-80 hover:opacity-100'"
                    @click="galleryIndex = idx"
                >
                    <img :src="slide.url" :alt="slide.label" class="h-full w-full object-cover" />
                </button>
            </div>
        </div>

        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto">
            <div class="border-b border-gray-100 p-4 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="advertisement-detail-title" class="text-lg font-bold leading-snug text-gray-900 sm:text-2xl">
                            {{ advertisement.address || 'Адрес не указан' }}
                        </h2>
                        <p class="site-header-font mt-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-400">
                            GID {{ advertisement.id }}
                            <span v-if="advertisement.code"> · код {{ advertisement.code }}</span>
                            <span v-if="advertisement.place_number"> · № площади {{ advertisement.place_number }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            v-if="constructionPageUrl"
                            :href="constructionPageUrl"
                            class="site-header-font border border-gray-300 bg-white px-3 py-2 text-[10px] font-semibold uppercase tracking-wider text-gray-700 hover:border-[#05299E] hover:text-[#05299E]"
                        >
                            Страница конструкции
                        </a>
                        <button
                            v-if="showClose"
                            type="button"
                            class="flex h-10 w-10 items-center justify-center border border-gray-200 text-xl text-gray-500 hover:bg-gray-50"
                            aria-label="Закрыть"
                            @click="$emit('close')"
                        >
                            ×
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        v-for="side in advertisement.side_details"
                        :key="side.code"
                        type="button"
                        class="site-header-font min-w-[44px] px-3 py-2 text-[11px] font-semibold uppercase tracking-wide transition"
                        :class="
                            activeSideCode === side.code
                                ? 'bg-[#05299E] text-white'
                                : sideStatus(side.code).busy
                                  ? 'bg-red-50 text-red-700'
                                  : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100'
                        "
                        @click="selectSide(side.code)"
                    >
                        {{ side.code }}
                    </button>
                </div>
            </div>

            <div class="space-y-6 p-4 sm:p-6">
                <section>
                    <h3 class="site-header-font mb-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500">
                        Параметры
                    </h3>
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-[1fr_auto]">
                        <dt class="text-gray-500">Категория продукции</dt>
                        <dd class="font-medium text-gray-900 sm:text-right">{{ advertisement.category || '—' }}</dd>
                        <dt class="text-gray-500">Тип конструкции</dt>
                        <dd class="font-medium text-gray-900 sm:text-right">{{ advertisement.type || '—' }}</dd>
                        <dt class="text-gray-500">Сторона (выбранная)</dt>
                        <dd class="font-medium text-gray-900 sm:text-right">{{ activeSide?.code || '—' }}</dd>
                        <dt class="text-gray-500">Описание стороны</dt>
                        <dd class="text-gray-800 sm:text-right">{{ activeSide?.description || '—' }}</dd>
                        <template v-if="advertisement.location && (lat != null || lng != null)">
                            <dt class="text-gray-500">Координаты</dt>
                            <dd class="font-mono text-xs text-gray-800 sm:text-right">
                                {{ lat != null && lng != null ? `${lat?.toFixed(5)}, ${lng?.toFixed(5)}` : '—' }}
                                <a
                                    v-if="lat != null && lng != null"
                                    :href="yandexMapUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="ml-2 text-[#05299E] underline-offset-2 hover:underline"
                                >На карте</a>
                            </dd>
                        </template>
                        <template v-if="azimuth != null">
                            <dt class="text-gray-500">Азимут</dt>
                            <dd class="font-medium text-gray-900 sm:text-right">{{ azimuth }}°</dd>
                        </template>
                    </dl>
                </section>

                <section v-if="activeSideStatus">
                    <p
                        class="site-header-font text-[11px] font-semibold uppercase tracking-wide"
                        :class="activeSideStatus.busy ? 'text-red-600' : 'text-emerald-700'"
                    >
                        {{ activeSideStatus.text }}
                    </p>
                </section>

                <section v-if="advertisement.bookings?.length">
                    <h3 class="site-header-font mb-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500">
                        Бронирования
                    </h3>
                    <ul class="max-h-40 space-y-2 overflow-y-auto text-xs text-gray-700">
                        <li
                            v-for="b in advertisement.bookings"
                            :key="b.id ?? `${b.side_code}-${b.start_date}`"
                            class="border border-gray-100 bg-gray-50 px-3 py-2"
                        >
                            <span class="font-semibold text-gray-900">{{ b.side_code }}</span>
                            · {{ formatBookingRange(b.start_date, b.end_date) }}
                            <span v-if="b.client_name" class="text-gray-500"> · {{ b.client_name }}</span>
                        </li>
                    </ul>
                </section>

                <section>
                    <h3 class="site-header-font mb-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-gray-500">
                        Все стороны
                    </h3>
                    <div class="overflow-x-auto border border-gray-200">
                        <table class="w-full min-w-[320px] text-left text-xs">
                            <thead class="bg-gray-100 text-[10px] font-semibold uppercase tracking-wider text-gray-600">
                                <tr>
                                    <th class="px-3 py-2">Сторона</th>
                                    <th class="px-3 py-2">Цена</th>
                                    <th class="px-3 py-2">Описание</th>
                                    <th class="px-3 py-2">Фото</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="s in advertisement.side_details" :key="s.code" class="border-t border-gray-100">
                                    <td class="px-3 py-2 font-semibold">{{ s.code }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ s.description || '—' }}</td>
                                    <td class="px-3 py-2">
                                        <span v-if="s.image_url || s.night_image_url" class="text-emerald-700">Есть</span>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="space-y-3 border-t border-gray-100 pt-4">
                    <p v-if="cartMessage" class="text-sm text-gray-700">{{ cartMessage }}</p>
                    <button
                        type="button"
                        class="site-header-font w-full bg-[#e85d4c] px-4 py-3 text-[12px] font-semibold uppercase tracking-wider text-white hover:bg-[#d64d3e] disabled:opacity-50"
                        :disabled="!activeSide || cartSubmitting"
                        @click="addToCart"
                    >
                        {{ cartSubmitting ? 'Добавление…' : 'В корзину' }}
                    </button>
                    <a
                        href="/cart"
                        class="site-header-font flex w-full items-center justify-center border border-[#05299E] px-4 py-3 text-[12px] font-semibold uppercase tracking-wider text-[#05299E] hover:bg-[#05299E]/5"
                    >
                        Перейти в корзину
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
    advertisement: { type: Object, required: true },
    cartUrl: { type: String, required: true },
    bookingFrom: { type: String, default: '' },
    bookingTo: { type: String, default: '' },
    constructionPageUrl: { type: String, default: '' },
    showClose: { type: Boolean, default: true },
})

const emit = defineEmits(['close', 'cart-updated'])

const galleryIndex = ref(0)
const activeSideCode = ref('')
const cartMessage = ref('')
const cartSubmitting = ref(false)

function parseDate(value) {
    if (!value) return null
    const date = new Date(`${value}T00:00:00`)
    return isNaN(date.getTime()) ? null : date
}

function toInputDate(date) {
    return date.toISOString().slice(0, 10)
}

const slides = computed(() => {
    const out = []
    for (const side of props.advertisement.side_details || []) {
        if (side.image_url) {
            out.push({
                url: side.image_url,
                label: `${side.code} · день`,
                code: side.code,
            })
        }
        if (side.night_image_url) {
            out.push({
                url: side.night_image_url,
                label: `${side.code} · ночь`,
                code: side.code,
            })
        }
    }
    if (out.length === 0) {
        out.push({ url: '/images/orig.png', label: 'Фото не загружено', code: null })
    }
    return out
})

const currentSlide = computed(() => slides.value[galleryIndex.value] || slides.value[0] || null)

function prevSlide() {
    const n = slides.value.length
    if (n < 2) return
    galleryIndex.value = (galleryIndex.value - 1 + n) % n
}

function nextSlide() {
    const n = slides.value.length
    if (n < 2) return
    galleryIndex.value = (galleryIndex.value + 1) % n
}

const activeSide = computed(() => {
    const sides = props.advertisement.side_details || []
    return sides.find((s) => s.code === activeSideCode.value) || sides[0] || null
})

const lat = computed(() => props.advertisement.location?.latitude ?? null)
const lng = computed(() => props.advertisement.location?.longitude ?? null)
const azimuth = computed(() => props.advertisement.location?.azimuth ?? null)

const yandexMapUrl = computed(() => {
    if (lat.value == null || lng.value == null) return '#'
    return `https://yandex.ru/maps/?pt=${lng.value},${lat.value}&z=16&l=map`
})

function selectSide(code) {
    activeSideCode.value = code
    const idx = slides.value.findIndex((s) => s.code === code)
    if (idx >= 0) {
        galleryIndex.value = idx
    }
}

function sideStatus(sideCode) {
    const bookings = (props.advertisement.bookings || []).filter((b) => b.side_code === sideCode)
    const fromDate = parseDate(props.bookingFrom)
    const toDate = parseDate(props.bookingTo)
    const from = fromDate || new Date()
    const to = toDate || from

    const overlap = bookings.find((b) => {
        const start = parseDate(b.start_date)
        const end = parseDate(b.end_date)
        return start && end && start <= to && end >= from
    })

    if (overlap) {
        const d = parseDate(overlap.end_date)
        if (d) {
            d.setDate(d.getDate() + 1)
            return { busy: true, text: `Занята до ${d.toLocaleDateString('ru-RU')}` }
        }
        return { busy: true, text: 'Занята' }
    }
    return { busy: false, text: 'Свободна' }
}

const activeSideStatus = computed(() => {
    if (!activeSide.value) return null
    return sideStatus(activeSide.value.code)
})

function formatPrice(price) {
    if (!price) return 'По запросу'
    return `${new Intl.NumberFormat('ru-RU').format(price)} ₽`
}

function formatBookingRange(from, to) {
    if (!from || !to) return '—'
    return `${from} — ${to}`
}

watch(
    () => props.advertisement?.id,
    () => {
        const first = props.advertisement.side_details?.[0]?.code || ''
        activeSideCode.value = first
        galleryIndex.value = 0
        cartMessage.value = ''
    },
    { immediate: true },
)

watch(slides, (s) => {
    if (galleryIndex.value >= s.length) {
        galleryIndex.value = 0
    }
})

async function addToCart() {
    if (!activeSide.value) return
    const startDate = props.bookingFrom || toInputDate(new Date())
    const end = new Date()
    end.setDate(end.getDate() + 30)
    const endDate = props.bookingTo || toInputDate(end)

    cartSubmitting.value = true
    cartMessage.value = ''
    try {
        const response = await fetch(`${props.cartUrl}/items`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                advertisementId: props.advertisement.id,
                side: activeSide.value.code,
                startDate,
                endDate,
            }),
        })
        const data = await response.json()
        if (!response.ok) {
            cartMessage.value = data?.message || 'Не удалось добавить в корзину.'
            return
        }
        cartMessage.value = data?.message || 'Позиция добавлена в корзину.'
        emit('cart-updated', data)
    } catch {
        cartMessage.value = 'Не удалось добавить в корзину.'
    } finally {
        cartSubmitting.value = false
    }
}
</script>
