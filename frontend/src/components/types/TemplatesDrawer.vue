<template>
  <!-- eslint-disable-next-line vuejs-accessibility/no-static-element-interactions -->
  <div
    v-if="open"
    class="fixed inset-0 bg-black/50 flex justify-end"
    role="dialog"
    aria-modal="true"
    tabindex="0"
    @keydown.escape.prevent="close"
  >
    <div class="bg-white w-80 p-4 overflow-y-auto">
      <h2 class="text-lg font-bold mb-4">Templates</h2>
      <div class="mb-6">
        <label for="export-select" class="block mb-2">
          Export JSON
          <select
            id="export-select"
            v-model="exportId"
            class="border rounded w-full mt-2"
          >
            <option :value="undefined" disabled>Select type</option>
            <option v-for="t in types" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
        </label>
        <button
          class="bg-blue-600 text-white px-2 py-1 rounded"
          :disabled="!exportId"
          @click="doExport"
        >
          Export
        </button>
      </div>
      <div class="mb-6">
        <label for="import-input" class="block mb-2">
          Import JSON
          <input
            id="import-input"
            type="file"
            accept="application/json"
            class="mt-2"
            @change="onFile"
          />
        </label>
      </div>
      <button
        class="text-sm text-gray-700 underline"
        @click="close"
      >
        Close
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useTaskTypesStore } from '@/stores/taskTypes';

interface Props {
  open: boolean;
  types: any[];
}

defineProps<Props>();
const emit = defineEmits(['close', 'imported']);
const exportId = ref<number>();
const typesStore = useTaskTypesStore();

function close() {
  emit('close');
}

async function doExport() {
  if (!exportId.value) return;
  const data = await typesStore.export(exportId.value);
  const blob = new Blob([JSON.stringify(data, null, 2)], {
    type: 'application/json',
  });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `task-type-${exportId.value}.json`;
  a.click();
  URL.revokeObjectURL(url);
}

async function onFile(e: Event) {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  const text = await file.text();
  const json = JSON.parse(text);
  await typesStore.import(json);
  emit('imported');
}
</script>
