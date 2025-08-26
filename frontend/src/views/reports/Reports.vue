<template>
  <div class="mx-auto max-w-7xl space-y-8 p-6">
    <h2 class="text-3xl font-bold tracking-tight">Reports</h2>

    <Tabs v-model="activeTab" :tabs="tabs">
      <template #default="{ active }">
        <div v-if="active === 'kpis'">
          <div v-if="kpisLoading" class="space-y-6">
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
          <div
            v-else-if="kpisError"
            class="flex flex-col items-center justify-center gap-4 py-10"
          >
            <p class="text-sm text-foreground/70">Failed to load KPIs.</p>
            <Button @click="fetchKpis">Retry</Button>
          </div>
          <div v-else class="space-y-6">
            <KpiCards :kpis="kpiCards" />
            <div v-if="chartSeries.length" class="mt-6">
              <ChartCard title="Trend" type="line" :series="chartSeries" />
            </div>
            <Button variant="secondary" @click="exportCsv">Export CSV</Button>
          </div>
        </div>

        <div v-else-if="active === 'materials'">
          <div v-if="materialsLoading" class="space-y-6">
            <Card class="h-64">
              <Skeleton class="h-full w-full" />
            </Card>
          </div>
          <div
            v-else-if="materialsError"
            class="flex flex-col items-center justify-center gap-4 py-10"
          >
            <p class="text-sm text-foreground/70">Failed to load materials.</p>
            <Button @click="fetchMaterials">Retry</Button>
          </div>
          <div v-else>
            <Card>
              <table class="w-full text-sm">
                <thead>
                  <tr class="text-left">
                    <th class="p-2">Category</th>
                    <th class="p-2">Count</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="m in materials" :key="m.label" class="border-t">
                    <td class="p-2">{{ m.label }}</td>
                    <td class="p-2">{{ m.count }}</td>
                  </tr>
                </tbody>
              </table>
            </Card>
          </div>
        </div>
      </template>
    </Tabs>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import Tabs from '@/components/ui/Tabs.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import KpiCards from '@/components/reports/KpiCards.vue';
import ChartCard from '@/components/reports/ChartCard.vue';

interface KpiResponse {
  completed: number;
  on_time_percentage: number;
  avg_duration_minutes: number;
  failed_uploads: number;
}

interface KpiCard {
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

interface Material {
  label: string;
  count: number;
}

const tabs = [
  { id: 'kpis', label: 'KPIs' },
  { id: 'materials', label: 'Materials' },
];

const activeTab = ref('kpis');

const kpisLoading = ref(false);
const kpisError = ref(false);
const kpiCards = ref<KpiCard[]>([]);
const chartSeries = ref<Series[]>([]);

async function fetchKpis() {
  kpisLoading.value = true;
  kpisError.value = false;
  try {
    const { data } = await api.get<KpiResponse>('/reports/kpis');
    kpiCards.value = [
      { label: 'Completed', value: data.completed },
      {
        label: 'On-time %',
        value: data.on_time_percentage.toFixed(2) + '%',
      },
      {
        label: 'Avg duration (m)',
        value: data.avg_duration_minutes.toFixed(2),
      },
      { label: 'Failed uploads', value: data.failed_uploads },
    ];
    chartSeries.value = [
      {
        label: 'Completed',
        data: buildTrend(data.completed),
      },
    ];
  } catch (e) {
    kpisError.value = true;
  } finally {
    kpisLoading.value = false;
  }
}

function buildTrend(value: number): Datum[] {
  return Array.from({ length: 7 }, (_, i) => ({
    x: `Day ${i + 1}`,
    y: Math.round(value * (0.7 + Math.random() * 0.6)),
  }));
}

const materialsLoading = ref(false);
const materialsError = ref(false);
const materials = ref<Material[]>([]);

async function fetchMaterials() {
  materialsLoading.value = true;
  materialsError.value = false;
  try {
    const { data } = await api.get('/reports/materials');
    materials.value = data.map((m: any) => ({
      label: m.category || 'Uncategorized',
      count: m.count,
    }));
  } catch (e) {
    materialsError.value = true;
  } finally {
    materialsLoading.value = false;
  }
}

async function exportCsv() {
  const response = await api.get('/reports/export', { responseType: 'blob' });
  const url = window.URL.createObjectURL(new Blob([response.data]));
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', 'report.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

onMounted(() => {
  fetchKpis();
  fetchMaterials();
});
</script>
