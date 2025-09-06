<template>
  <div class="flex items-center gap-2">
    <button
      v-for="chip in chips"
      :key="chip.key"
      type="button"
      :aria-pressed="local[chip.key]"
      @click="toggle(chip.key)"
      class="focus:outline-none"
    >
      <Badge
        :label="t(chip.label)"
        :badgeClass="
          local[chip.key]
            ? 'bg-primary-500 text-white pill'
            : 'bg-primary-500 text-primary-500 bg-opacity-[0.12] pill'
        "
      />
    </button>
    <Button
      btnClass="btn-outline btn-sm"
      :aria-label="t('board.clear')"
      @click="clear"
      @keyup.enter="clear"
      @keyup.space.prevent="clear"
    >
      {{ t('board.clear') }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Badge from '@dc/components/Badge';
import Button from '@dc/components/Button';

interface Filters {
  mine: boolean;
  dueToday: boolean;
  breachedOnly: boolean;
}

const props = defineProps<{ modelValue: Filters }>();
const emit = defineEmits<{ (e: 'update:modelValue', v: Filters): void }>();
const { t } = useI18n();

const local = ref<Filters>({ mine: false, dueToday: false, breachedOnly: false });
const chips = [
  { key: 'mine' as const, label: 'board.myTasks' },
  { key: 'dueToday' as const, label: 'board.dueToday' },
  { key: 'breachedOnly' as const, label: 'board.breachedOnly' },
];

watch(
  () => props.modelValue,
  (val) => Object.assign(local.value, val),
  { deep: true, immediate: true },
);

watch(
  local,
  (val) => emit('update:modelValue', { ...val }),
  { deep: true },
);

function toggle(key: keyof Filters) {
  local.value[key] = !local.value[key];
}
function clear() {
  local.value.mine = false;
  local.value.dueToday = false;
  local.value.breachedOnly = false;
}
</script>
