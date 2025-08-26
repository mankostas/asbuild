<template>
  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
    <Card
      v-for="k in kpis"
      :key="k.label"
      bodyClass="p-6 flex flex-col gap-1"
    >
      <span class="text-sm font-medium text-slate-600 dark:text-slate-300"
        >{{ k.label }}</span
      >
      <span
        class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white"
        >{{ k.value }}</span
      >
    </Card>
  </div>
</template>

<script setup lang="ts">
import Card from '@/components/ui/Card/index.vue';

interface Kpi {
  label: string;
  value: string | number;
}

defineProps<{ kpis: Kpi[] }>();
</script>
