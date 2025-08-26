<template>
  <Card class="flex flex-col gap-4 p-6">
    <h3 class="text-lg font-semibold tracking-tight">{{ title }}</h3>
    <div v-if="!hasData" class="flex h-56 items-center justify-center">
      <Skeleton class="h-full w-full" />
    </div>
    <component
      v-else
      :is="chartComponent"
      :data="chartData"
      :options="chartOptions"
      class="h-64"
    />
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  PointElement,
  LineElement,
  TimeScale,
  Tooltip,
  Legend,
} from 'chart.js';
import Card from '@/components/ui/Card/index.vue';
import Skeleton from '@/components/ui/Skeleton.vue';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  PointElement,
  LineElement,
  TimeScale,
  Tooltip,
  Legend,
);

interface Datum {
  x: string | number | Date;
  y: number;
}
interface Series {
  label: string;
  data: Datum[];
}

const props = defineProps<{
  title: string;
  type: 'bar' | 'line';
  series: Series[];
  yLabel?: string;
}>();

const hasData = computed(() => props.series.some((s) => s.data.length));

const chartComponent = computed(() => (props.type === 'bar' ? Bar : Line));

const chartData = computed(() => ({
  datasets: props.series.map((s) => ({
    label: s.label,
    data: s.data,
    parsing: { xAxisKey: 'x', yAxisKey: 'y' },
    borderWidth: 2,
  })),
}));

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: true } },
  scales: {
    x: { type: inferTime(props.series) ? 'time' : 'category' },
    y: {
      title: props.yLabel ? { display: true, text: props.yLabel } : undefined,
    },
  },
}));

function inferTime(s: Series[]): boolean {
  return s.some((ser) => ser.data.some((d) => typeof d.x !== 'number'));
}
</script>
