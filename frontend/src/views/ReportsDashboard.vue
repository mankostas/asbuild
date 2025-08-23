<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Reports</h2>
    <div class="mb-4 flex flex-wrap items-end gap-2">
      <select v-model="range" class="border rounded p-1">
        <option value="today">Today</option>
        <option value="7">Last 7 days</option>
        <option value="30">Last 30 days</option>
        <option value="custom">Custom</option>
      </select>
      <input v-if="range === 'custom'" type="date" v-model="from" class="border rounded p-1" />
      <input v-if="range === 'custom'" type="date" v-model="to" class="border rounded p-1" />
      <Button @click="fetchData">Apply</Button>
      <Button @click="exportCsv">Export CSV</Button>
    </div>
    <KpiCards :kpis="kpis" class="mb-6" />
    <SimpleChart title="Materials" :data="materials" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import Button from '@/components/ui/Button.vue';
import KpiCards from '@/components/reports/KpiCards.vue';
import SimpleChart from '@/components/reports/SimpleChart.vue';

const range = ref('today');
const from = ref('');
const to = ref('');
const kpis = ref([] as any);
const materials = ref([] as any);

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
  materials.value = mat.data.map((m: any) => ({ label: m.category || 'Uncategorized', value: m.count }));
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
