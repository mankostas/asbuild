<template>
  <div v-if="members.length" v-tippy="tooltip" class="flex items-center" :title="tooltip">
    <div
      v-for="(m, idx) in displayed"
      :key="idx"
      class="w-6 h-6 rounded-full bg-slate-200 text-[10px] font-medium text-slate-600 flex items-center justify-center overflow-hidden border-2 border-white"
      :class="idx !== 0 ? '-ml-2' : ''"
    >
      <img
        v-if="m.avatar"
        :src="m.avatar"
        alt="avatar"
        class="w-full h-full object-cover"
      />
      <span v-else>{{ initials(m.name) }}</span>
    </div>
    <div
      v-if="remainder > 0"
      class="w-6 h-6 rounded-full bg-slate-200 text-[10px] font-medium text-slate-600 flex items-center justify-center border-2 border-white -ml-2"
    >
      +{{ remainder }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Member {
  name: string;
  avatar?: string | null;
}

const props = defineProps<{ members: Member[]; max?: number }>();

const max = computed(() => props.max ?? 3);
const displayed = computed(() => props.members.slice(0, max.value));
const remainder = computed(() => Math.max(props.members.length - displayed.value.length, 0));
const tooltip = computed(() => props.members.map((m) => m.name).join(', '));

function initials(name: string) {
  return name
    .split(' ')
    .filter(Boolean)
    .map((n) => n[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();
}
</script>

<style scoped>
</style>
