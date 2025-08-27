<template>
  <div v-if="manual">
      <div class="flex justify-end items-center mb-4">
        <button
          class="text-yellow-500 text-xl"
          :aria-pressed="isFavorite"
          @click="store.toggleFavorite(manual.id)"
        >
          {{ isFavorite ? '★' : '☆' }}
        </button>
      </div>
    <div class="text-sm text-gray-500 mb-4">
      Last updated: {{ new Date(manual.updated_at).toLocaleString() }}
    </div>
    <div class="mb-4">
      <button class="text-blue-600" @click="toggleOffline">
        {{ offline ? 'Remove Offline' : 'Keep Offline' }}
      </button>
    </div>
    <div ref="container"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useManualsStore } from '@/stores/manuals';
import * as pdfjsLib from 'pdfjs-dist';
import pdfjsWorker from 'pdfjs-dist/build/pdf.worker?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsWorker;

const route = useRoute();
const store = useManualsStore();
const manual = ref<any>(null);
const container = ref<HTMLDivElement | null>(null);
const offline = ref(false);

const isFavorite = computed(() => manual.value && store.favorites.includes(manual.value.id));

onMounted(async () => {
  const id = route.params.id as string;
  manual.value = await store.get(id);
  if (manual.value) {
    store.addRecent(manual.value.id);
    offline.value = await store.isOffline(manual.value.id);
    await renderPdf();
  }
});

async function getBlob() {
  if (offline.value) {
    const data = await store.loadOffline(manual.value.id);
    if (data?.blob) return data.blob;
  }
  return await store.downloadPdf(manual.value.id);
}

async function renderPdf() {
  const blob = await getBlob();
  const url = URL.createObjectURL(blob);
  const pdf = await pdfjsLib.getDocument(url).promise;
  for (let i = 1; i <= pdf.numPages; i++) {
    const page = await pdf.getPage(i);
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d')!;
    const viewport = page.getViewport({ scale: 1.2 });
    canvas.width = viewport.width;
    canvas.height = viewport.height;
    await page.render({ canvasContext: context, viewport }).promise;
    container.value?.appendChild(canvas);
  }
  URL.revokeObjectURL(url);
}

async function toggleOffline() {
  if (!manual.value) return;
  if (offline.value) {
    await store.removeOffline(manual.value.id);
    offline.value = false;
  } else {
    await store.keepOffline(manual.value);
    offline.value = true;
  }
}
</script>
