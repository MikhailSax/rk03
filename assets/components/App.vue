<template>
    <div class="flex min-h-0 flex-1 flex-col">
        <Spinner v-if="isLoading" message="Загрузка интерфейса..."/>
        <MapSection
            v-else
            :filters-url="runtimeConfig.filtersUrl"
            :advertisements-url="runtimeConfig.advertisementsUrl"
            :orders-url="runtimeConfig.ordersUrl"
            :cart-url="runtimeConfig.cartUrl"
            :auth-user="runtimeConfig.authUser"
        />
    </div>
</template>

<script setup>
import {onMounted, ref} from 'vue'
import Spinner from './Spinner.vue'
import MapSection from './MapApp.vue'

const props = defineProps({
    filtersUrl: {
        type: String,
        required: true,
    },
    advertisementsUrl: {
        type: String,
        required: true,
    },
    ordersUrl: {
        type: String,
        required: true,
    },
    cartUrl: {
        type: String,
        required: true,
    },
    authUser: {
        type: Object,
        required: true,
    },
})

const isLoading = ref(true)
const runtimeConfig = ref({
    filtersUrl: props.filtersUrl,
    advertisementsUrl: props.advertisementsUrl,
    ordersUrl: props.ordersUrl,
    cartUrl: props.cartUrl,
    authUser: props.authUser,
})

onMounted(() => {
    const initialSpinner = document.getElementById('initial-spinner')
    if (initialSpinner) {
        initialSpinner.remove()
    }

    isLoading.value = false
})
</script>
