<template>
  <div>
    <h2 class="text-lg font-semibold mb-2">{{ t('types.workflow.statuses') }}</h2>
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
      </div>
      <div v-if="editable" class="flex flex-wrap items-center gap-4">
        <div
          v-for="status in allStatuses"
          :key="status.slug"
          class="flex items-center gap-1"
        >
          <input
            :id="`status-${status.slug}`"
            v-model="localStatuses"
            type="checkbox"
            :value="status.slug"
            :aria-label="status.name"
            @change="emitStatuses"
          />
          <span>{{ status.name }}</span>
        </div>
      </div>
      <span class="sr-only" aria-live="assertive">{{ liveMessage }}</span>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable';
import api from '@/services/api';
import Card from '@/components/ui/Card/index.vue';
import Button from '@/components/ui/Button/index.vue';
import { useAuthStore, can } from '@/stores/auth';

interface StatusOption {
  slug: string;
  name: string;
}

const props = defineProps<{ modelValue: string[]; tenantId?: number | '' }>();
const emit = defineEmits(['update:modelValue']);
const { t } = useI18n();

const auth = useAuthStore();
const editable = computed(() => auth.isSuperAdmin || can('task_types.manage'));

const localStatuses = ref<string[]>([...props.modelValue]);
watch(
  () => props.modelValue,
  (v) => (localStatuses.value = [...v])
);

const allStatuses = ref<StatusOption[]>([]);
const grabbedIndex = ref<number | null>(null);
const liveMessage = ref('');

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

function displayName(slug: string) {
  return allStatuses.value.find((s) => s.slug === slug)?.name || slug;
}
function removeStatus(slug: string) {
  localStatuses.value = localStatuses.value.filter((s) => s !== slug);
  emitStatuses();
}
function emitStatuses() {
  emit('update:modelValue', localStatuses.value);
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
</script>
