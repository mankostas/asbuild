<template>
  <div>
    <h3 class="font-bold mb-2">{{ title }}</h3>
    <div v-if="data.length === 0">No data</div>
    <div v-else class="flex items-end gap-2 h-40">
      <div v-for="d in data" :key="d.label" class="flex-1 text-center">
        <div class="bg-blue-500 w-full" :style="{ height: (d.value / max * 100) + '%' }"></div>
        <div class="mt-1 text-sm">{{ d.label }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Datum {
  label: string;
  value: number;
}

const props = defineProps<{ title: string; data: Datum[] }>();
const max = computed(() => Math.max(...props.data.map(d => d.value), 1));
</script>
