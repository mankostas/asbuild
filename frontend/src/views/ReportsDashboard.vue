<template>
  <div class="mx-auto max-w-7xl space-y-8 p-6">
    <div class="flex justify-end mb-4">
      <div class="flex flex-wrap items-end gap-2">
        <Select v-model="range" label="Range" class="w-40" aria-label="Range">
          <option value="today">Today</option>
          <option value="7">Last 7 days</option>
          <option value="30">Last 30 days</option>
          <option value="custom">Custom</option>
        </Select>
        <Textinput
          v-if="range === 'custom'"
          v-model="from"
          type="date"
          label="From"
          class="w-40"
          aria-label="From"
        />
        <Textinput
          v-if="range === 'custom'"
          v-model="to"
          type="date"
          label="To"
          class="w-40"
          aria-label="To"
        />
        <Button @click="fetchData">Apply</Button>
        <Button variant="secondary" @click="exportCsv">Export CSV</Button>
      </div>
    </div>
    <KpiCards :kpis="kpis" />
    <div class="grid gap-6 md:grid-cols-2">
      <ChartCard title="Materials" type="bar" :series="materialSeries" />
      <ChartCard title="Materials Trend" type="line" :series="materialSeries" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '@/services/api';
import Button from '@/components/ui/Button/index.vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import Select from '@/components/ui/Select/index.vue';
import KpiCards from '@/components/reports/KpiCards.vue';
import ChartCard from '@/components/reports/ChartCard.vue';

const range = ref('today');
const from = ref('');
const to = ref('');
const kpis = ref([] as any);
const materials = ref([] as any);
const materialSeries = computed(() => [
  {
    label: 'Materials',
    data: materials.value.map((m: any) => ({ x: m.label, y: m.value })),
  },
]);

function params() {
  if (range.value === 'custom') {
    return { from: from.value, to: to.value };
  }
  return { range: range.value };
}

async function fetchData() {
  const { data } = await api.get('/reports/kpis', { params: params() });
  kpis.value = [
    { label: 'Completed', value: data.completed },
    { label: 'On-time %', value: data.on_time_percentage.toFixed(2) + '%' },
    { label: 'Avg duration (m)', value: data.avg_duration_minutes.toFixed(2) },
    { label: 'Failed uploads', value: data.failed_uploads },
  ];
  const mat = await api.get('/reports/materials', { params: params() });
  materials.value = mat.data.map((m: any) => ({
    label: m.category || 'Uncategorized',
    value: m.count,
  }));
}

async function exportCsv() {
  const response = await api.get('/reports/export', {
    params: params(),
    responseType: 'blob',
  });
  const url = window.URL.createObjectURL(new Blob([response.data]));
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', 'report.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

onMounted(fetchData);
</script>
