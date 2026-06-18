<template>
    <section class="bg-[var(--color-bg)] py-8 sm:py-10">
        <div class="u-wrap">
            <div class="u-card u-card--raised mb-6 p-6 sm:p-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-[var(--color-blue)]">
                            Размещение</p>
                        <h1 class="home-display mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Корзина</h1>
                        <p class="mt-2 text-xs text-[var(--color-muted)]">Проверьте позиции, заполните контакты
                            и отправьте заявку на бронь.</p>
                    </div>
                    <div
                        class="rounded-[var(--radius-soft)] border border-[var(--color-tint-blue)] bg-[var(--color-tint-blue)] px-4 py-3 text-right">
                        <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-[var(--color-blue)]">
                            Позиции</p>
                        <p class="mt-1 font-[var(--font-display)] text-xl font-bold text-[var(--color-blue)]">
                            {{ cartItems.length }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
                <article class="u-card p-4 sm:p-6">
                    <div
                        class="mb-4 flex items-center justify-between gap-3 border-b border-[rgba(13,19,32,.08)] pb-3">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-[var(--color-muted)]">
                            Всего: <span class="text-[var(--color-ink)]">{{ cartItems.length }}</span>
                        </p>
                        <button type="button"
                                class="u-btn u-btn--soft !px-3 !py-2 text-[11px] disabled:opacity-50"
                                :disabled="!cartItems.length || isSubmitting" @click="clearCart">Очистить
                        </button>
                    </div>

                    <div v-if="!cartItems.length"
                         class="rounded-[var(--radius-soft)] border border-dashed border-[rgba(13,19,32,.18)] bg-[var(--color-bg)] p-8 text-center text-sm text-[var(--color-muted)]">
                        Корзина пуста.
                        <a href="/map/app" class="ml-2 font-semibold text-[var(--color-blue)] hover:underline">Карта</a>
                    </div>

                    <div v-else class="space-y-3">
                        <article
                            v-for="(item, index) in cartItems"
                            :key="`${item.advertisementId}-${item.side}-${item.startDate}-${item.endDate}`"
                            class="rounded-[var(--radius-soft)] border border-[rgba(13,19,32,.08)] bg-white p-4 transition hover:border-[rgba(42,75,247,.25)] hover:shadow-[var(--shadow-soft)]"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.12em] text-[var(--color-muted)]">
                                        Позиция {{ index + 1 }}</p>
                                    <p class="truncate text-sm font-bold">{{ item.address }}</p>
                                    <p class="mt-1 truncate text-xs text-[var(--color-muted)]">{{ item.side }} •
                                        {{ item.startDate }}—{{ item.endDate }}</p>
                                </div>
                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    <p class="font-[var(--font-display)] text-sm font-bold text-[var(--color-blue)]">
                                        {{ formatPrice(item.price) }}</p>
                                    <button type="button"
                                            class="u-btn u-btn--soft !px-2.5 !py-1 text-[10px] disabled:opacity-50"
                                            :disabled="isSubmitting" @click="removeCartItem(index)">Удалить
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </article>

                <aside class="u-card p-5 sm:p-6 lg:sticky lg:top-28 lg:h-fit">
                    <h2 class="text-sm font-bold uppercase tracking-[0.16em] text-[var(--color-blue)]">
                        Оформление заказа</h2>

                    <div class="mt-4 border-b border-[rgba(13,19,32,.08)] pb-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-[var(--color-muted)]">Итого</span>
                            <span class="font-[var(--font-display)] text-base font-bold">{{
                                    formatPrice(cartTotal)
                                }}</span>
                        </div>
                    </div>

                    <form class="mt-4 space-y-3" @submit.prevent="submitOrder">
                        <label class="block text-sm">
                            <span class="u-field-label">Имя</span>
                            <input v-model.trim="orderForm.name" :readonly="isAuthenticated" required
                                   class="u-input"/>
                        </label>
                        <label class="block text-sm">
                            <span class="u-field-label">Телефон</span>
                            <input v-model.trim="orderForm.phone" :readonly="isAuthenticated" required
                                   class="u-input"/>
                        </label>
                        <label class="block text-sm">
                            <span class="u-field-label">Комментарий</span>
                            <textarea v-model.trim="orderForm.comment" rows="3" class="u-textarea"></textarea>
                        </label>
                        <input v-model="orderForm.website" type="text" autocomplete="off" class="hidden"
                               tabindex="-1"/>

                        <p v-if="statusMessage"
                           class="rounded-[var(--radius-xs)] border border-[rgba(13,19,32,.08)] bg-[var(--color-bg)] px-3 py-2 text-sm text-[var(--color-muted)]">
                            {{ statusMessage }}</p>

                        <button type="submit" :disabled="!cartItems.length || isSubmitting"
                                class="u-btn u-btn--blue w-full justify-center disabled:opacity-50">
                            {{ isSubmitting ? 'Отправка…' : 'Отправить заказ' }}
                        </button>
                    </form>
                </aside>
            </div>
        </div>
    </section>
</template>

<script setup>
import {computed, onMounted, reactive, ref} from 'vue'

const props = defineProps({
    cartUrl: {type: String, required: true},
    ordersUrl: {type: String, required: true},
    authUser: {type: Object, required: true},
})

const cartItems = ref([])
const isSubmitting = ref(false)
const statusMessage = ref('')
const orderForm = reactive({name: '', phone: '', comment: '', website: '', startedAt: Date.now()})

const normalizedOrdersUrl = computed(() => String(props.ordersUrl || '').replace(/\/+$/, ''))
const isAuthenticated = computed(() => Boolean(props.authUser?.isAuthenticated))
const cartTotal = computed(() => cartItems.value.reduce((sum, item) => sum + (Number(item.price) || 0), 0))

function formatPrice(price) {
    if (!price) return 'По запросу'
    return `${new Intl.NumberFormat('ru-RU').format(price)} ₽`
}

function applyCartPayload(data) {
    cartItems.value = Array.isArray(data?.items) ? data.items : []
}

async function loadCart() {
    try {
        const response = await fetch(props.cartUrl)
        const data = await response.json()
        if (!response.ok) {
            throw new Error(data?.message || 'Ошибка загрузки корзины.')
        }
        applyCartPayload(data)
    } catch (error) {
        statusMessage.value = error instanceof Error ? error.message : 'Не удалось загрузить корзину.'
    }
}

async function removeCartItem(index) {
    statusMessage.value = ''
    try {
        const response = await fetch(`${props.cartUrl}/items/${index}`, {method: 'DELETE'})
        const data = await response.json()
        if (!response.ok) {
            throw new Error(data?.message || 'Не удалось удалить позицию.')
        }
        applyCartPayload(data)
        statusMessage.value = data?.message || 'Позиция удалена.'
    } catch (error) {
        statusMessage.value = error instanceof Error ? error.message : 'Не удалось удалить позицию.'
    }
}

async function clearCart() {
    statusMessage.value = ''
    try {
        const response = await fetch(props.cartUrl, {method: 'DELETE'})
        const data = await response.json()
        if (!response.ok) {
            throw new Error(data?.message || 'Не удалось очистить корзину.')
        }
        applyCartPayload(data)
        statusMessage.value = data?.message || 'Корзина очищена.'
    } catch (error) {
        statusMessage.value = error instanceof Error ? error.message : 'Не удалось очистить корзину.'
    }
}

async function submitOrder() {
    if (!cartItems.value.length) {
        statusMessage.value = 'Добавьте позиции в корзину.'
        return
    }

    isSubmitting.value = true
    statusMessage.value = ''

    if (isAuthenticated.value) {
        orderForm.name = props.authUser?.name || orderForm.name
        orderForm.phone = props.authUser?.phone || orderForm.phone
    }

    try {
        const response = await fetch(normalizedOrdersUrl.value || '/api/orders', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
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
            }),
        })
        const data = await response.json()
        if (!response.ok) {
            throw new Error(data?.message || 'Ошибка при отправке заказа.')
        }
        statusMessage.value = data?.message || 'Заказ отправлен.'
        orderForm.startedAt = Date.now()
        await loadCart()
    } catch (error) {
        statusMessage.value = error instanceof Error ? error.message : 'Ошибка при отправке заказа.'
    } finally {
        isSubmitting.value = false
    }
}

onMounted(async () => {
    const initialSpinner = document.getElementById('initial-cart-spinner')
    if (initialSpinner) {
        initialSpinner.remove()
    }

    if (isAuthenticated.value) {
        orderForm.name = props.authUser?.name || ''
        orderForm.phone = props.authUser?.phone || ''
    }

    await loadCart()
})
</script>
