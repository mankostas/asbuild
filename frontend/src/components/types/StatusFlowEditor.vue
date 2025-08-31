<template>
  <Card :bodyClass="'p-4'">
    <div class="flex flex-wrap items-center gap-2 mb-4" role="list">
      <p id="statusReorderHint" class="sr-only">
        {{ t('a11y.reorderInstructions') }}
      </p>
      <draggable
        v-model="localStatuses"
        item-key="value"
        handle=".handle"
        tag="ul"
        class="flex flex-wrap items-center gap-2"
        aria-describedby="statusReorderHint"
        @end="emitStatuses"
      >
        <template #item="{ element, index }">
          <li :key="element" class="flex items-center gap-2">
            <button
              type="button"
              class="handle cursor-move text-slate-600 dark:text-slate-300"
              :aria-label="t('a11y.dragToReorder')"
              :aria-describedby="'statusReorderHint'"
              :aria-grabbed="grabbedIndex === index"
              @keydown="onHandleKeydown($event, index)"
            >
              ≡
            </button>
            <span
              class="flex items-center gap-1 rounded-2xl bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 px-3 py-1 shadow-sm"
            >
              {{ displayName(element) }}
              <Button
                v-if="editable"
                type="button"
                btnClass="btn-outline-danger text-xs px-1 py-0"
                :aria-label="t('actions.delete')"
                @click="removeStatus(element)"
              >
                ×
              </Button>
            </span>
          </li>
        </template>
      </draggable>
      <Button
        v-if="editable"
        type="button"
        btnClass="btn-primary text-xs px-3 py-1"
        :aria-label="t('types.workflow.addStatus')"
        @click="openAddStatusModal"
      >
        {{ t('types.workflow.addStatus') }}
      </Button>
      <Dropdown v-if="editable">
        <template #default>
          <Button
            type="button"
            btnClass="btn-outline-secondary text-xs px-3 py-1 flex items-center gap-1"
            :aria-label="t('types.workflow.quickPresets')"
          >
            {{ t('types.workflow.quickPresets') }}
            <Icon icon="heroicons-outline:chevron-down" />
          </Button>
        </template>
        <template #menus>
          <MenuItem
            v-for="preset in presets"
            :key="preset.key"
            #default="{ active }"
          >
            <button
              type="button"
              :class="menuItemClass(active)"
              @click="applyPreset(preset)"
            >
              {{ preset.label }}
            </button>
          </MenuItem>
        </template>
      </Dropdown>
    </div>
    <span class="sr-only" aria-live="assertive">{{ liveMessage }}</span>
    <div>
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-medium">{{ t('types.workflow.transitions') }}</h3>
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
          <thead
            class="sticky top-0 z-10 bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300"
          >
            <tr>
              <th scope="col" class="table-th">{{ t('types.workflow.from') }}</th>
              <th scope="col" class="table-th">{{ t('types.workflow.to') }}</th>
              <th scope="col" class="table-th">{{ t('types.workflow.condition') }}</th>
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
              <td class="table-td">{{ t('types.workflow.noCondition') }}</td>
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
    </div>
    <Modal
      :open="showAddStatusModal"
      :title="t('types.workflow.addStatus')"
      :label="t('types.workflow.addStatus')"
      @close="closeAddStatusModal"
    >
      <template #header>{{ t('types.workflow.addStatus') }}</template>
      <template #body>
        <VueSelect
          id="status-select"
          v-model="newStatus"
          :options="addStatusOptions"
          :label="t('types.workflow.addStatus')"
          classLabel="sr-only"
          :placeholder="t('actions.select')"
        />
      </template>
      <template #footer>
        <Button
          type="button"
          btnClass="btn-primary text-xs px-3 py-1"
          :disabled="!newStatus"
          :aria-label="t('actions.add')"
          @click="confirmAddStatus"
        >
          {{ t('actions.add') }}
        </Button>
        <Button
          type="button"
          btnClass="btn-outline-secondary text-xs px-3 py-1"
          :aria-label="t('actions.cancel')"
          @click="closeAddStatusModal"
        >
          {{ t('actions.cancel') }}
        </Button>
      </template>
    </Modal>
  </Card>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable';
import api from '@/services/api';
import Card from '@/components/ui/Card/index.vue';
import Button from '@/components/ui/Button/index.vue';
import Dropdown from '@/components/ui/Dropdown/index.vue';
import Modal from '@/components/ui/Modal/Modal.vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import Icon from '@/components/ui/Icon/index.vue';
import { MenuItem } from '@headlessui/vue';
import { useAuthStore, can } from '@/stores/auth';

interface StatusOption {
  slug: string;
  name: string;
}

const props = defineProps<{
  statuses: string[];
  modelValue: string[][];
  tenantId?: number | '';
}>();
const emit = defineEmits(['update:statuses', 'update:modelValue']);
const { t } = useI18n();

const auth = useAuthStore();
const editable = computed(() => auth.isSuperAdmin || can('task_type_versions.manage'));

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
const showAddStatusModal = ref(false);
const grabbedIndex = ref<number | null>(null);
const liveMessage = ref('');
const presets = [
  {
    key: 'basic',
    label: t('types.workflow.presetBasic'),
    statuses: ['todo', 'in_progress', 'completed'],
  },
];

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
      localStatuses.value = [];
      edges.value = [];
      emitStatuses();
      emitEdges();
    } else {
      allStatuses.value = [];
      localStatuses.value = [];
      edges.value = [];
      emitStatuses();
      emitEdges();
    }
  },
  { immediate: true },
);

const remainingStatuses = computed(() =>
  allStatuses.value.filter((s) => !localStatuses.value.includes(s.slug))
);

const addStatusOptions = computed(() =>
  remainingStatuses.value.map((s) => ({ value: s.slug, label: s.name }))
);

const transitionOptions = computed(() =>
  localStatuses.value.map((slug) => ({
    value: slug,
    label: allStatuses.value.find((s) => s.slug === slug)?.name || slug,
  }))
);

function displayName(slug: string) {
  return allStatuses.value.find((s) => s.slug === slug)?.name || slug;
}

function openAddStatusModal() {
  showAddStatusModal.value = true;
}
function closeAddStatusModal() {
  showAddStatusModal.value = false;
  newStatus.value = '';
}
function confirmAddStatus() {
  if (newStatus.value && !localStatuses.value.includes(newStatus.value)) {
    localStatuses.value.push(newStatus.value);
    emitStatuses();
  }
  closeAddStatusModal();
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

function applyPreset(preset: { statuses: string[] }) {
  preset.statuses.forEach((slug, idx) => {
    if (!localStatuses.value.includes(slug)) {
      localStatuses.value.push(slug);
    }
    if (idx < preset.statuses.length - 1) {
      const from = preset.statuses[idx];
      const to = preset.statuses[idx + 1];
      if (!edges.value.some((e) => e[0] === from && e[1] === to)) {
        edges.value.push([from, to]);
      }
    }
  });
  emitStatuses();
  emitEdges();
}

function onHandleKeydown(e: KeyboardEvent, index: number) {
  if (grabbedIndex.value === null) {
    if (e.key === 'Enter') {
      grabbedIndex.value = index;
    }
  } else {
    if (e.key === 'Escape') {
      grabbedIndex.value = null;
    } else if (e.key === 'ArrowUp' && grabbedIndex.value > 0) {
      const item = localStatuses.value.splice(grabbedIndex.value, 1)[0];
      const newIndex = grabbedIndex.value - 1;
      localStatuses.value.splice(newIndex, 0, item);
      grabbedIndex.value = newIndex;
      emitStatuses();
      announcePosition(newIndex);
    } else if (
      e.key === 'ArrowDown' &&
      grabbedIndex.value < localStatuses.value.length - 1
    ) {
      const item = localStatuses.value.splice(grabbedIndex.value, 1)[0];
      const newIndex = grabbedIndex.value + 1;
      localStatuses.value.splice(newIndex, 0, item);
      grabbedIndex.value = newIndex;
      emitStatuses();
      announcePosition(newIndex);
    }
  }
}
function announcePosition(pos: number) {
  liveMessage.value = t('a11y.movedToPosition', { pos: pos + 1 });
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
  const edge: [string, string] = [
    transitionForm.value.from,
    transitionForm.value.to,
  ];
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

function menuItemClass(active: boolean) {
  return (
    (active
      ? 'bg-slate-100 dark:bg-slate-600 dark:bg-opacity-50'
      : 'text-slate-600 dark:text-slate-300') +
    ' block w-full text-left px-4 py-2'
  );
}
</script>
