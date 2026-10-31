<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';
import { store } from '@/routes/documents';
import { store as storeQuestion } from '@/routes/questions';

defineProps({
    documents: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const fileInput = ref(null);

const form = useForm({
    title: '',
    file: null,
});

const questionForm = useForm({
    question: '',
});

const answer = computed(() => page.props.flash?.answer ?? null);

const submitDocument = () => {
    form.submit(store(), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
        preserveScroll: true,
    });
};

const submitQuestion = () => {
    questionForm.submit(storeQuestion(), {
        preserveScroll: true,
        onSuccess: () => {
            questionForm.reset();
        },
    });
};
</script>

<template>
    <main class="min-h-screen bg-stone-100 text-stone-900">
        <div class="mx-auto grid min-h-screen max-w-6xl gap-6 p-4 lg:grid-cols-[22rem_1fr] lg:p-8">
            <div class="lg:col-span-2">
                <FlashMessage />
            </div>

            <section class="flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-sm">
                <header>
                    <h1 class="text-lg font-semibold">Documents</h1>
                    <p class="mt-1 text-sm text-stone-500">Upload a note the chat can cite later.</p>
                </header>

                <form class="flex flex-col gap-3" @submit.prevent="submitDocument">
                    <label class="flex flex-col gap-1 text-sm font-medium">
                        Title
                        <input
                            v-model="form.title"
                            type="text"
                            placeholder="Defaults to the filename"
                            class="rounded-lg border border-stone-300 px-3 py-2 font-normal"
                        >
                        <p v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</p>
                    </label>
                    <label class="flex flex-col gap-1 text-sm font-medium">
                        File
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".txt,.md,.pdf,text/plain,text/markdown,application/pdf"
                            class="rounded-lg border border-stone-300 px-3 py-2 font-normal"
                            @change="form.file = $event.target.files[0]"
                        >
                        <p v-if="form.errors.file" class="text-sm text-red-500">{{ form.errors.file }}</p>
                    </label>
                    <button
                        type="submit"
                        class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white"
                    >
                        Upload document
                    </button>
                </form>

                <p v-if="documents.length === 0" class="text-sm text-stone-500">No documents yet.</p>

                <ul v-else class="flex flex-col gap-3">
                    <li
                        v-for="document in documents"
                        :key="document.id"
                        class="rounded-xl border border-stone-200 p-3"
                    >
                        <h2 class="font-medium">{{ document.title }}</h2>
                        <p class="mt-1 text-sm text-stone-600">{{ document.original_filename }}</p>
                    </li>
                </ul>
            </section>

            <section class="flex min-h-[32rem] flex-col rounded-2xl bg-white shadow-sm">
                <header class="border-b border-stone-200 px-5 py-4">
                    <h1 class="text-lg font-semibold">Ask the knowledge base</h1>
                </header>

                <div class="flex flex-1 flex-col gap-6 overflow-y-auto px-5 py-5">
                    <p v-if="answer === null" class="text-sm text-stone-500">No questions yet.</p>

                    <article v-else class="flex flex-col gap-4">
                        <h2 class="font-medium">{{ answer.question }}</h2>
                        <p class="text-sm leading-6">{{ answer.text }}</p>
                    </article>
                </div>

                <form class="flex flex-col gap-2 border-t border-stone-200 p-4" @submit.prevent="submitQuestion">
                    <div class="flex gap-2">
                        <label class="sr-only" for="question">Question</label>
                        <input
                            id="question"
                            v-model="questionForm.question"
                            type="text"
                            class="min-w-0 flex-1 rounded-lg border border-stone-300 px-3 py-2 text-sm"
                        >
                        <button
                            type="submit"
                            class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                            :disabled="questionForm.processing"
                        >
                            Ask
                        </button>
                    </div>
                    <p v-if="questionForm.errors.question" class="text-sm text-red-500">{{ questionForm.errors.question }}</p>
                </form>
            </section>
        </div>
    </main>
</template>
