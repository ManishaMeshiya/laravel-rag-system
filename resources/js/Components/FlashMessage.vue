<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(null);

const dismissAfterMs = 4000;
let dismissTimer = null;

function clearDismissTimer() {
    if (dismissTimer !== null) {
        clearTimeout(dismissTimer);
        dismissTimer = null;
    }
}

function show(message, type) {
    clearDismissTimer();

    if (message === null || message === '') {
        visible.value = null;

        return;
    }

    visible.value = { message, type };

    dismissTimer = setTimeout(() => {
        visible.value = null;
        dismissTimer = null;
    }, dismissAfterMs);
}

function applyFlash() {
    const success = page.props.flash?.success;
    const error = page.props.flash?.error;

    if (success) {
        show(success, 'success');

        return;
    }

    if (error) {
        show(error, 'error');
    }
}

applyFlash();

const stopListening = router.on('success', applyFlash);

onBeforeUnmount(() => {
    stopListening();
    clearDismissTimer();
});

const appearanceClasses = computed(() => {
    if (visible.value?.type === 'error') {
        return 'bg-red-100 text-red-800';
    }

    return 'bg-green-100 text-green-800';
});
</script>

<template>
    <div
        v-if="visible"
        class="rounded-lg px-4 py-3 text-sm"
        :class="appearanceClasses"
        role="status"
    >
        {{ visible.message }}
    </div>
</template>
