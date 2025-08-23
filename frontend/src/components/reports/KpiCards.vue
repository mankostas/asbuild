<template>
  <div class="grid gap-4 md:grid-cols-4">
    <Card v-for="k in kpis" :key="k.label">
      <div class="text-sm text-gray-500">{{ k.label }}</div>
      <div class="text-2xl font-bold">{{ k.value }}</div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import Card from '@/components/ui/Card.vue';

interface Kpi {
  label: string;
  value: string | number;
}

defineProps<{ kpis: Kpi[] }>();
</script>
