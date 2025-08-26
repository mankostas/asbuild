<template>
  <div class="fixed top-4 right-4 z-50 space-y-2">
    <div
      v-for="t in toasts"
      :key="t.id"
      class="px-4 py-2 rounded shadow text-white"
      :class="t.type === 'error' ? 'bg-red-600' : 'bg-gray-800'"
    >
      {{ t.message }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useToastStore } from '@/store/toast';

const toastStore = useToastStore();
const { toasts } = storeToRefs(toastStore);
</script>
