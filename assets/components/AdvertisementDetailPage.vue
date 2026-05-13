<template>
    <div class="min-h-[calc(100dvh-4rem)] text-gray-900">
        <header class="border-b border-gray-200 bg-white px-4 py-4 sm:px-6">
            <a
                href="/map/app"
                class="site-header-font text-[11px] font-semibold uppercase tracking-wider text-[#05299E] transition hover:text-[#e85d4c]"
            >
                ← К карте и каталогу
            </a>
        </header>

        <div v-if="loadError" class="mx-auto max-w-xl px-4 py-12 text-center text-red-700">
            {{ loadError }}
        </div>
        <div v-else-if="!item" class="flex min-h-[40vh] items-center justify-center text-sm text-gray-500">
            Загрузка карточки…
        </div>
        <div v-else class="mx-auto max-w-[min(1280px,100%)] p-2 sm:p-4">
            <div class="overflow-hidden border border-gray-200 bg-white shadow-lg">
                <AdvertisementDetailBody
                    :advertisement="item"
                    :cart-url="advertisementCartUrl"
                    :booking-from="defaultFrom"
                    :booking-to="defaultTo"
                    :show-close="false"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import AdvertisementDetailBody from './AdvertisementDetailBody.vue'
import { normalizeAdvertisementRecord } from '../js/advertisementNormalize.js'

const props = defineProps({
    advertisementApiUrl: { type: String, required: true },
    cartUrl: { type: String, required: true },
})

const item = ref(null)
const loadError = ref('')
const defaultFrom = ref('')
const defaultTo = ref('')

const advertisementCartUrl = props.cartUrl.replace(/\/$/, '')

onMounted(async () => {
    const now = new Date()
    const end = new Date()
    end.setDate(end.getDate() + 30)
    defaultFrom.value = now.toISOString().slice(0, 10)
    defaultTo.value = end.toISOString().slice(0, 10)

    try {
        const res = await fetch(props.advertisementApiUrl)
        let data = null
        try {
            data = await res.json()
        } catch {
            data = null
        }
        if (!res.ok) {
            loadError.value =
                (data && typeof data === 'object' && data.message) || 'Конструкция не найдена.'
            return
        }
        item.value = normalizeAdvertisementRecord(data)
        if (!item.value) {
            loadError.value = 'Некорректные данные конструкции.'
        }
    } catch {
        loadError.value = 'Не удалось загрузить данные. Проверьте соединение и обновите страницу.'
    }
})
</script>
