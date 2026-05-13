<!-- components/Spinner.vue -->
<template>
    <div class="spinner-container">
        <div class="spinner"></div>
        <p v-if="message" class="message">{{ message }}</p>
    </div>
</template>

<script setup>
defineProps({
    message: {
        type: String,
        default: ''
    }
})
</script>

<style scoped>
.spinner-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #4b5563; /* text-gray-600 */
}

.spinner {
    width: 48px;
    height: 48px;
    border: 5px solid #e5e7eb; /* border-gray-200 */
    border-top-color: #dc2626; /* border-red-600 */
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 0.75rem;
}

.message {
    font-size: 0.875rem;
    color: #6b7280; /* text-gray-500 */
    text-align: center;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
