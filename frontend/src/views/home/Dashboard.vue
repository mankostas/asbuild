<template>
  <div class="mx-auto max-w-7xl space-y-8 p-6">
    <h2 class="text-3xl font-bold tracking-tight">Dashboard</h2>

    <!-- Loading state -->
    <div v-if="loading" class="space-y-6">
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <Card v-for="n in 4" :key="n" class="flex flex-col gap-2 p-6">
          <Skeleton class="h-4 w-1/2" />
          <Skeleton class="h-8 w-20" />
        </Card>
      </div>
      <Card class="h-64">
        <Skeleton class="h-full w-full" />
      </Card>
    </div>

    <!-- Error state -->
    <div
      v-else-if="error"
      class="flex flex-col items-center justify-center gap-4 py-10"
    >
      <p class="text-center text-sm text-foreground/70">
        Failed to load dashboard data.
      </p>
      <Button @click="fetchData">Retry</Button>
    </div>

    <!-- Loaded state -->
    <template v-else>
      <div v-if="kpis.length" class="space-y-6">
        <KpiCards :kpis="kpis" />
        <div v-if="chartSeries.length" class="mt-6">
          <ChartCard :title="chartTitle" :type="chartType" :series="chartSeries" />
        </div>
        <Card v-else class="mt-6 flex h-64 items-center justify-center">
          <p class="text-sm text-foreground/70">No chart data</p>
        </Card>
      </div>
      <div v-else class="flex h-64 items-center justify-center">
        <p class="text-sm text-foreground/70">No dashboard data</p>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import KpiCards from '@/components/reports/KpiCards.vue';
import ChartCard from '@/components/reports/ChartCard.vue';
import Card from '@/components/ui/Card.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import Button from '@/components/ui/Button.vue';

interface Kpi {
  label: string;
  value: string | number;
}
interface Datum {
  x: string | number | Date;
  y: number;
}
interface Series {
  label: string;
  data: Datum[];
}

const kpis = ref<Kpi[]>([]);
const chartSeries = ref<Series[]>([]);
const chartType = ref<'line' | 'bar'>('line');
const chartTitle = ref('Trend');
const loading = ref(false);
const error = ref(false);

async function fetchData() {
  loading.value = true;
  error.value = false;
  try {
    const { data } = await api.get('/reports/overview');
    kpis.value = data.kpis || [];
    chartSeries.value = data.chart?.series || [];
    chartType.value = data.chart?.type || 'line';
    chartTitle.value = data.chart?.title || 'Trend';
  } catch (e) {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

onMounted(fetchData);
</script>

