<template>
  <div>
    <h2 class="text-lg font-semibold mb-2">{{ t('types.workflow.transitions') }}</h2>
    <Card :bodyClass="'p-4'">
      <div class="flex items-center justify-between mb-2">
        <Button
          v-if="editable"
          type="button"
          btnClass="btn-primary text-xs px-3 py-1"
          :aria-label="t('types.workflow.addTransition')"
          @click="openTransitionForm"
        >
          {{ t('types.workflow.addTransition') }}
        </Button>
      </div>
      <div v-if="showTransitionForm" class="flex flex-wrap items-center gap-2 mb-2">
        <VueSelect
          id="transition-from"
          v-model="transitionForm.from"
          :options="transitionOptions"
          :label="t('types.workflow.from')"
          class="w-40"
          classLabel="sr-only"
          :reduce="(o: any) => o.value"
          :placeholder="t('actions.select')"
        />
        <span aria-hidden="true">→</span>
        <VueSelect
          id="transition-to"
          v-model="transitionForm.to"
          :options="transitionOptions"
          :label="t('types.workflow.to')"
          class="w-40"
          classLabel="sr-only"
          :reduce="(o: any) => o.value"
          :placeholder="t('actions.select')"
        />
        <Button
          type="button"
          btnClass="btn-primary text-xs px-3 py-1"
          :disabled="!transitionForm.from || !transitionForm.to || transitionForm.from === transitionForm.to"
          :aria-label="t('actions.save')"
          @click="saveTransition"
        >
          {{ t('actions.save') }}
        </Button>
        <Button
          type="button"
          btnClass="btn-outline-secondary text-xs px-3 py-1"
          :aria-label="t('actions.cancel')"
          @click="cancelTransition"
        >
          {{ t('actions.cancel') }}
        </Button>
      </div>
      <div class="max-h-64 overflow-auto">
        <table
          class="min-w-full divide-y divide-slate-100 dark:divide-slate-700"
          :aria-label="t('types.workflow.transitions')"
        >
          <thead class="sticky top-0 z-10 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
            <tr>
              <th scope="col" class="table-th">{{ t('types.workflow.from') }}</th>
              <th scope="col" class="table-th">{{ t('types.workflow.to') }}</th>
              <th scope="col" class="table-th sr-only">{{ t('actions.actions') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
            <tr
              v-for="(edge, idx) in edges"
              :key="`${edge[0]}-${edge[1]}-${idx}`"
              class="hover:bg-slate-50 dark:hover:bg-slate-700"
            >
              <td class="table-td">{{ displayName(edge[0]) }}</td>
              <td class="table-td">{{ displayName(edge[1]) }}</td>
              <td class="table-td text-right">
                <Dropdown v-if="editable">
                  <template #default>
                    <Button
                      type="button"
                      btnClass="btn-outline-secondary text-xs px-2 py-1"
                      :aria-label="t('actions.actions')"
                    >
                      ⋯
                    </Button>
                  </template>
                  <template #menus>
                    <MenuItem #default="{ active }">
                      <button
                        type="button"
                        :class="menuItemClass(active)"
                        @click="editTransition(idx)"
                      >
                        {{ t('actions.edit') }}
                      </button>
                    </MenuItem>
                    <MenuItem #default="{ active }">
                      <button
                        type="button"
                        :class="menuItemClass(active)"
                        @click="removeTransition(idx)"
                      >
                        {{ t('actions.delete') }}
                      </button>
                    </MenuItem>
                  </template>
                </Dropdown>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import Card from '@/components/ui/Card/index.vue';
import Button from '@/components/ui/Button/index.vue';
import Dropdown from '@/components/ui/Dropdown/index.vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import { MenuItem } from '@headlessui/vue';
import { useAuthStore, can } from '@/stores/auth';

interface StatusOption {
  slug: string;
  name: string;
}

const props = defineProps<{ statuses: string[]; modelValue: string[][]; tenantId?: number | '' }>();
const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();

const auth = useAuthStore();
const editable = computed(() => auth.isSuperAdmin || can('task_types.manage'));

const edges = ref<[string, string][]>(props.modelValue ? props.modelValue.map((e) => [e[0], e[1]]) : []);
watch(
  () => props.modelValue,
  (v) => (edges.value = v ? v.map((e) => [e[0], e[1]]) : [])
);

const allStatuses = ref<StatusOption[]>([]);
async function fetchStatuses(id: number | string) {
  const { data } = await api.get('/task-statuses', {
    params: { scope: 'tenant', tenant_id: id, per_page: 100 },
  });
  allStatuses.value = data.data ?? data;
}

watch(
  () => props.tenantId,
  async (id: number | '' | undefined) => {
    if (id) {
      await fetchStatuses(id);
    } else {
      allStatuses.value = [];
    }
  },
  { immediate: true },
);

watch(
  () => props.statuses,
  (newStatuses) => {
    edges.value = edges.value.filter(([f, t]) => newStatuses.includes(f) && newStatuses.includes(t));
    emitEdges();
  }
);

const transitionOptions = computed(() =>
  props.statuses.map((slug) => ({
    value: slug,
    label: allStatuses.value.find((s) => s.slug === slug)?.name || slug,
  }))
);

function displayName(slug: string) {
  return allStatuses.value.find((s) => s.slug === slug)?.name || slug;
}

const showTransitionForm = ref(false);
const transitionForm = ref<{ from: string; to: string }>({ from: '', to: '' });
const editingIndex = ref<number | null>(null);

function openTransitionForm() {
  showTransitionForm.value = true;
  editingIndex.value = null;
  transitionForm.value = { from: '', to: '' };
}
function cancelTransition() {
  showTransitionForm.value = false;
  transitionForm.value = { from: '', to: '' };
  editingIndex.value = null;
}
function saveTransition() {
  if (!transitionForm.value.from || !transitionForm.value.to) return;
  const edge: [string, string] = [transitionForm.value.from, transitionForm.value.to];
  if (
    editingIndex.value !== null &&
    editingIndex.value >= 0 &&
    editingIndex.value < edges.value.length
  ) {
    edges.value[editingIndex.value] = edge;
  } else {
    edges.value.push(edge);
  }
  emitEdges();
  cancelTransition();
}
function editTransition(idx: number) {
  editingIndex.value = idx;
  transitionForm.value = { from: edges.value[idx][0], to: edges.value[idx][1] };
  showTransitionForm.value = true;
}
function removeTransition(idx: number) {
  edges.value.splice(idx, 1);
  emitEdges();
}
function emitEdges() {
  emit('update:modelValue', edges.value);
}

function commitPending() {
  if (
    showTransitionForm.value &&
    transitionForm.value.from &&
    transitionForm.value.to
  ) {
    saveTransition();
  }
}

defineExpose({ commitPending });

function menuItemClass(active: boolean) {
  return (
    (active
      ? 'bg-slate-100 dark:bg-slate-600 dark:bg-opacity-50'
      : 'text-slate-600 dark:text-slate-300') +
    ' block w-full text-left px-4 py-2'
  );
}
</script>
