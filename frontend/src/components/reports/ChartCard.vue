<template>
  <Card class="flex flex-col gap-4 p-6">
    <h3 class="text-lg font-semibold tracking-tight">{{ title }}</h3>
    <div v-if="!hasData" class="flex h-56 items-center justify-center">
      <Skeleton class="h-full w-full" />
    </div>
    <apexchart
      v-else
      :type="type"
      height="256"
      :options="chartOptions"
      :series="apexSeries"
      class="h-64"
    />
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';
import Card from '@/components/ui/Card/index.vue';
import Skeleton from '@/components/ui/Skeleton.vue';

const apexchart = VueApexCharts;

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
  type: 'bar' | 'line' | 'pie';
  series: Series[];
  yLabel?: string;
}>();

const hasData = computed(() => props.series.some((s) => s.data.length));

const apexSeries = computed(() =>
  props.series.map((s) => ({
    name: s.label,
    data: s.data.map((d) => ({ x: d.x, y: d.y })),
  })),
);

const chartOptions = computed(() => ({
  chart: { toolbar: { show: false } },
  xaxis: { type: inferTime(props.series) ? 'datetime' : 'category' },
  yaxis: props.yLabel ? { title: { text: props.yLabel } } : {},
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth' },
}));

function inferTime(s: Series[]): boolean {
  return s.some((ser) => ser.data.some((d) => typeof d.x !== 'number'));
}
</script>
