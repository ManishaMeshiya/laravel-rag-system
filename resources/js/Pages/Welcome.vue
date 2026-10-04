<script setup>
defineProps({
    appName: String,
});
</script>

<template>
    <main class="min-h-screen p-8">
        <h1 class="text-2xl font-semibold">{{ appName }}</h1>
    </main>
</template>