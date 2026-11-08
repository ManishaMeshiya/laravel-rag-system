<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm, usePoll } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';
import { destroy, store } from '@/routes/documents';
import { store as storeQuestion } from '@/routes/questions';

const props = defineProps({
    documents: {
        type: Array,
        required: true,
    },
    messages: {
        type: Array,
        required: true,
    },
});

const fileInput = ref(null);
const deletingDocumentId = ref(null);
const documentError = ref('');
const questionSubmitError = ref('');

const form = useForm({
    title: '',
    file: null,
});

const questionForm = useForm({
    question: '',
});

const hasPendingDocuments = computed(() => props.documents.some((document) => document.status === 'pending'));
const { start, stop } = usePoll(2000, {
    only: ['documents'],
}, {
    autoStart: false,
});

watch(hasPendingDocuments, (hasPending) => {
    if (hasPending) {
        start();

        return;
    }

    stop();
}, { immediate: true });

const statusClasses = (status) => ({
    pending: 'bg-amber-100 text-amber-800',
    ready: 'bg-emerald-100 text-emerald-800',
    failed: 'bg-red-100 text-red-800',
}[status] ?? 'bg-stone-100 text-stone-700');

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

const deleteDocument = (document) => {
    if (! window.confirm(`Delete "${document.title}" and all of its indexed chunks?`)) {
        return;
    }

    deletingDocumentId.value = document.id;
    documentError.value = '';

    router.delete(destroy.url(document.id), {
        preserveScroll: true,
        onError: () => {
            documentError.value = 'The document could not be deleted. Please try again.';
        },
        onFinish: () => {
            deletingDocumentId.value = null;
        },
    });
};

const submitQuestion = () => {
    questionSubmitError.value = '';

    questionForm.submit(storeQuestion(), {
        preserveScroll: true,
        onSuccess: () => {
            questionForm.reset();
        },
        onError: (errors) => {
            if (! errors.question) {
                questionSubmitError.value = 'The answer could not be generated. Please try again.';
            }
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
                        class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Uploading...' : 'Upload document' }}
                    </button>
                </form>

                <p v-if="documentError" class="text-sm text-red-600" role="alert">{{ documentError }}</p>

                <p v-if="documents.length === 0" class="text-sm text-stone-500">No documents yet.</p>

                <ul v-else class="flex flex-col gap-3">
                    <li
                        v-for="document in documents"
                        :key="document.id"
                        class="rounded-xl border border-stone-200 p-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="font-medium">{{ document.title }}</h2>
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium capitalize"
                                :class="statusClasses(document.status)"
                            >
                                {{ document.status }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-stone-600">{{ document.original_filename }}</p>
                        <p v-if="document.status === 'pending'" class="mt-2 text-xs text-amber-700">
                            Preparing this document for search…
                        </p>
                        <p v-if="document.status === 'failed'" class="mt-2 text-xs text-red-700">
                            Ingestion failed. Check the queue worker and application logs.
                        </p>
                        <button
                            type="button"
                            class="mt-3 text-xs font-medium text-red-700 disabled:opacity-50"
                            :disabled="deletingDocumentId === document.id"
                            @click="deleteDocument(document)"
                        >
                            {{ deletingDocumentId === document.id ? 'Deleting...' : 'Delete' }}
                        </button>
                    </li>
                </ul>
            </section>

            <section class="flex min-h-[32rem] flex-col rounded-2xl bg-white shadow-sm">
                <header class="border-b border-stone-200 px-5 py-4">
                    <h1 class="text-lg font-semibold">Ask the knowledge base</h1>
                </header>

                <div class="flex flex-1 flex-col gap-6 overflow-y-auto px-5 py-5">
                    <p v-if="messages.length === 0" class="text-sm text-stone-500">
                        Ask a question after a document is ready.
                    </p>

                    <article
                        v-for="message in messages"
                        :key="message.id"
                        class="flex flex-col gap-3 border-b border-stone-100 pb-6 last:border-b-0 last:pb-0"
                    >
                        <div class="self-end rounded-2xl rounded-br-sm bg-stone-900 px-4 py-3 text-sm text-white">
                            {{ message.question }}
                        </div>
                        <div class="flex flex-col gap-3">
                            <p class="text-sm leading-6">{{ message.answer }}</p>
                            <div v-if="message.sources.length > 0" class="flex flex-col gap-2">
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-stone-500">Sources</h3>
                                <details
                                    v-for="(source, index) in message.sources"
                                    :key="`${message.id}-${source.document_id}-${index}`"
                                    class="rounded-lg border border-stone-200 bg-stone-50 px-3 py-2"
                                >
                                    <summary class="cursor-pointer text-sm font-medium">
                                        {{ source.document_title }}
                                    </summary>
                                    <p class="mt-2 text-xs leading-5 text-stone-600">{{ source.excerpt }}</p>
                                </details>
                            </div>
                        </div>
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
                            :disabled="questionForm.processing"
                        >
                        <button
                            type="submit"
                            class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                            :disabled="questionForm.processing"
                        >
                            {{ questionForm.processing ? 'Thinking...' : 'Ask' }}
                        </button>
                    </div>
                    <p v-if="questionForm.errors.question" class="text-sm text-red-500">
                        {{ questionForm.errors.question }}
                    </p>
                    <p v-if="questionSubmitError" class="text-sm text-red-500" role="alert">
                        {{ questionSubmitError }}
                    </p>
                </form>
            </section>
        </div>
    </main>
</template>
