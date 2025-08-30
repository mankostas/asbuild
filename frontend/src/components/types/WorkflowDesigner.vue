<template>
  <div class="space-y-4">
    <div>
      <span class="block text-sm font-medium">{{ t('types.workflow.addStatus') }}</span>
      <div class="mt-1 flex gap-2">
        <select
          id="status-select"
          v-model="newStatus"
          class="border rounded p-1 flex-1"
          :aria-label="t('types.workflow.addStatus')"
        >
          <option value="" disabled>{{ t('actions.select') }}</option>
          <option
            v-for="s in remainingStatuses"
            :key="s.slug"
            :value="s.slug"
          >{{ s.name }}</option>
        </select>
        <button
          type="button"
          class="px-2 py-1 border rounded"
          :disabled="!newStatus"
          :aria-label="t('actions.add')"
          @click="addStatus"
        >{{ t('actions.add') }}</button>
      </div>
    </div>
    <draggable v-model="localStatuses" item-key="value" handle=".handle" class="space-y-2" @end="emitStatuses">
      <template #item="{ element }">
        <div class="flex items-center gap-2 border p-1 rounded">
          <span class="handle cursor-move" tabindex="0" aria-label="drag">≡</span>
          <span>{{ displayName(element) }}</span>
          <button
            type="button"
            class="ml-auto text-red-600"
            :aria-label="t('actions.delete')"
            @click="removeStatus(element)"
          >{{ t('actions.delete') }}</button>
        </div>
      </template>
    </draggable>
    <div>
      <span class="block text-sm font-medium">{{ t('types.workflow.transitions') }}</span>
      <table class="border-collapse">
        <thead>
          <tr>
            <th></th>
            <th v-for="to in localStatuses" :key="to" class="px-2 py-1 border">{{ displayName(to) }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="from in localStatuses" :key="from">
            <th scope="row" class="px-2 py-1 border text-left">{{ displayName(from) }}</th>
            <td
              v-for="to in localStatuses"
              :key="to"
              class="px-2 py-1 border text-center"
            >
              <input
                :checked="hasEdge(from, to)"
                :disabled="from === to"
                :aria-label="`${displayName(from)} → ${displayName(to)}`"
                type="checkbox"
                @change="toggleEdge(from, to)"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable';
import api from '@/services/api';

interface StatusOption {
  slug: string;
  name: string;
}

const props = defineProps<{
  statuses: string[];
  modelValue: string[][];
}>();
const emit = defineEmits(['update:statuses', 'update:modelValue']);
const { t } = useI18n();

const localStatuses = ref<string[]>([...props.statuses]);
watch(
  () => props.statuses,
  (v) => (localStatuses.value = [...v])
);

const edges = ref<[string, string][]>(props.modelValue ? props.modelValue.map((e) => [e[0], e[1]]) : []);
watch(
  () => props.modelValue,
  (v) => (edges.value = v ? v.map((e) => [e[0], e[1]]) : [])
);

const allStatuses = ref<StatusOption[]>([]);
const newStatus = ref('');

onMounted(async () => {
  const res = await api.get('/task-statuses');
  allStatuses.value = res.data;
});

const remainingStatuses = computed(() =>
  allStatuses.value.filter((s) => !localStatuses.value.includes(s.slug))
);

function displayName(slug: string) {
  return allStatuses.value.find((s) => s.slug === slug)?.name || slug;
}

function addStatus() {
  if (newStatus.value && !localStatuses.value.includes(newStatus.value)) {
    localStatuses.value.push(newStatus.value);
    newStatus.value = '';
    emitStatuses();
  }
}

function removeStatus(slug: string) {
  localStatuses.value = localStatuses.value.filter((s) => s !== slug);
  edges.value = edges.value.filter(([f, t]) => f !== slug && t !== slug);
  emitStatuses();
  emitEdges();
}

function emitStatuses() {
  emit('update:statuses', localStatuses.value);
}
function emitEdges() {
  emit('update:modelValue', edges.value);
}

function hasEdge(from: string, to: string) {
  return edges.value.some(([f, t]) => f === from && t === to);
}
function toggleEdge(from: string, to: string) {
  const idx = edges.value.findIndex(([f, t]) => f === from && t === to);
  if (idx >= 0) {
    edges.value.splice(idx, 1);
  } else {
    edges.value.push([from, to]);
  }
  emitEdges();
}
</script>
