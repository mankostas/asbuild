<template>
  <div class="space-y-4">
    <div>
      <div class="mt-1 flex gap-2">
        <Select
          id="status-select"
          v-model="newStatus"
          :options="statusOptions"
          :placeholder="t('actions.select')"
          :label="t('types.workflow.addStatus')"
          class="flex-1"
        />
        <Button
          type="button"
          :disabled="!newStatus"
          :aria-label="t('actions.add')"
          btnClass="btn-outline-primary px-2 py-1"
          @click="addStatus"
        >
          {{ t('actions.add') }}
        </Button>
      </div>
    </div>
    <draggable v-model="localStatuses" item-key="value" handle=".handle" class="space-y-2" @end="emitStatuses">
      <template #item="{ element }">
        <div class="flex items-center gap-2 border p-1 rounded">
          <span
            class="handle cursor-move"
            tabindex="0"
            role="button"
            aria-label="drag"
            @keydown.enter.prevent="noop"
            @keydown.space.prevent="noop"
            >≡</span
          >
          <span>{{ displayName(element) }}</span>
          <Button
            type="button"
            class="ml-auto"
            btnClass="btn-outline-danger text-xs"
            :aria-label="t('actions.delete')"
            @click="removeStatus(element)"
          >
            {{ t('actions.delete') }}
          </Button>
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
              <Switch
                :model-value="hasEdge(from, to)"
                :disabled="from === to"
                :aria-label="`${displayName(from)} → ${displayName(to)}`"
                @update:model-value="() => toggleEdge(from, to)"
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
import Select from '@/components/ui/Select/index.vue';
import Button from '@/components/ui/Button/index.vue';
import Switch from '@/components/ui/Switch/index.vue';

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

const statusOptions = computed(() =>
  remainingStatuses.value.map((s) => ({ value: s.slug, label: s.name }))
);

function displayName(slug: string) {
  return allStatuses.value.find((s) => s.slug === slug)?.name || slug;
}

const noop = () => {};

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
