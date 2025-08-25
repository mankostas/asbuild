<template>
  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
    <Card
      v-for="k in kpis"
      :key="k.label"
      class="flex flex-col gap-1 bg-gradient-to-br from-primary/20 via-primary/10 to-transparent"
    >
      <span class="text-sm font-medium text-foreground/70">{{ k.label }}</span>
      <span class="text-3xl font-bold tracking-tight">{{ k.value }}</span>
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
