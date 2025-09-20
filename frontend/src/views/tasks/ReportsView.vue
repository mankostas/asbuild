<template>
  <div class="mx-auto max-w-7xl space-y-8 p-6">
    <div class="mb-4 flex justify-end">
      <div class="flex flex-wrap items-end gap-2">
        <Select
          v-model="typeId"
          label="Type"
          class="w-40"
          aria-label="Type"
        >
          <option
            v-for="t in types"
            :key="String(t.public_id ?? t.id)"
            :value="String(t.public_id ?? t.id)"
          >
            {{ t.name }}
          </option>
        </Select>
        <Select
          v-model="range"
          label="Range"
          class="w-40"
          aria-label="Range"
        >
          <option value="7">Last 7 days</option>
          <option value="30">Last 30 days</option>
          <option value="90">Last 90 days</option>
        </Select>
        <Button @click="fetchData">Apply</Button>
      </div>
    </div>
    <div class="grid gap-6 md:grid-cols-3">
      <div class="h-64">
        <Line :data="throughputData" :options="chartOptions" aria-label="Throughput chart" />
      </div>
      <div class="h-64">
        <Bar :data="cycleData" :options="chartOptions" aria-label="Cycle time chart" />
      </div>
      <div class="h-64">
        <Bar :data="slaData" :options="chartOptions" aria-label="SLA attainment chart" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import {
  Chart,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Tooltip,
  Legend,
} from 'chart.js';
import { Line, Bar } from 'vue-chartjs';
import api from '@/services/api';
import Button from '@/components/ui/Button/index.vue';
import Select from '@/components/ui/Select/index.vue';

Chart.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Tooltip,
  Legend,
);

interface Point {
  x: string;
  y: number;
}

const types = ref<any[]>([]);
const typeId = ref<string | null>(null);
const range = ref('7');
const throughput = ref<Point[]>([]);
const cycle = ref<Point[]>([]);
const sla = ref<Point[]>([]);

const RANGE_KEY = 'taskReportsRange';
const TYPE_KEY = 'taskReportsType';

onMounted(async () => {
  const savedRange = localStorage.getItem(RANGE_KEY);
  if (savedRange) range.value = savedRange;
  const { data } = await api.get('/task-types');
  types.value = data.data || data;
  const savedType = localStorage.getItem(TYPE_KEY);
  if (
    savedType &&
    types.value.find(
      (t: any) => String(t.public_id ?? t.id) === savedType,
    )
  ) {
    typeId.value = savedType;
  } else if (types.value.length) {
    typeId.value = String(types.value[0].public_id ?? types.value[0].id);
  }
  await fetchData();
});

watch(range, (val) => localStorage.setItem(RANGE_KEY, val));
watch(typeId, (val) => val && localStorage.setItem(TYPE_KEY, String(val)));

async function fetchData() {
  if (!typeId.value) return;
  const { data } = await api.get('/reports/tasks/overview', {
    params: { type_id: typeId.value, range: range.value },
  });
  throughput.value = data.throughput;
  cycle.value = data.cycle_time;
  sla.value = data.sla_attainment;
}

const chartOptions = { responsive: true, maintainAspectRatio: false };

const throughputData = computed(() => ({
  labels: throughput.value.map((d) => d.x),
  datasets: [{ label: 'Throughput', data: throughput.value.map((d) => d.y) }],
}));

const cycleData = computed(() => ({
  labels: cycle.value.map((d) => d.x),
  datasets: [{ label: 'Cycle time (min)', data: cycle.value.map((d) => d.y) }],
}));

const slaData = computed(() => ({
  labels: sla.value.map((d) => d.x),
  datasets: [{ label: 'SLA %', data: sla.value.map((d) => d.y) }],
}));
</script>

