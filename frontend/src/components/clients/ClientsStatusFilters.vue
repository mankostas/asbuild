<template>
  <div class="flex flex-wrap items-center gap-4 pt-2">
    <Checkbox
      v-model="includeArchivedModel"
      :label="includeArchivedLabel"
      :aria-label="includeArchivedLabel"
    />
    <Checkbox
      v-model="archivedOnlyModel"
      :label="archivedOnlyLabel"
      :aria-label="archivedOnlyLabel"
      :disabled="archivedOnlyDisabled"
    />
    <Checkbox
      v-model="includeTrashedModel"
      :label="includeTrashedLabel"
      :aria-label="includeTrashedLabel"
    />
    <Checkbox
      v-model="trashedOnlyModel"
      :label="trashedOnlyLabel"
      :aria-label="trashedOnlyLabel"
      :disabled="trashedOnlyDisabled"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Checkbox from '@/components/ui/Checkbox/index.vue';

const props = withDefaults(
  defineProps<{
    includeArchived?: boolean;
    archivedOnly?: boolean;
    includeTrashed?: boolean;
    trashedOnly?: boolean;
    includeArchivedLabel?: string;
    archivedOnlyLabel?: string;
    includeTrashedLabel?: string;
    trashedOnlyLabel?: string;
  }>(),
  {
    includeArchived: false,
    archivedOnly: false,
    includeTrashed: false,
    trashedOnly: false,
    includeArchivedLabel: '',
    archivedOnlyLabel: '',
    includeTrashedLabel: '',
    trashedOnlyLabel: '',
  },
);

const emit = defineEmits<{
  (e: 'update:include-archived', value: boolean): void;
  (e: 'update:archived-only', value: boolean): void;
  (e: 'update:include-trashed', value: boolean): void;
  (e: 'update:trashed-only', value: boolean): void;
}>();

const { t } = useI18n();

const includeArchivedModel = computed({
  get: () => props.includeArchived,
  set: (value: boolean) => emit('update:include-archived', value),
});

const archivedOnlyModel = computed({
  get: () => props.archivedOnly,
  set: (value: boolean) => emit('update:archived-only', value),
});

const includeTrashedModel = computed({
  get: () => props.includeTrashed,
  set: (value: boolean) => emit('update:include-trashed', value),
});

const trashedOnlyModel = computed({
  get: () => props.trashedOnly,
  set: (value: boolean) => emit('update:trashed-only', value),
});

const includeArchivedLabel = computed(
  () => props.includeArchivedLabel || t('clients.filters.includeArchived'),
);
const archivedOnlyLabel = computed(
  () => props.archivedOnlyLabel || t('clients.filters.archivedOnly'),
);
const includeTrashedLabel = computed(
  () => props.includeTrashedLabel || t('clients.filters.includeTrashed'),
);
const trashedOnlyLabel = computed(
  () => props.trashedOnlyLabel || t('clients.filters.trashedOnly'),
);

const archivedOnlyDisabled = computed(() => !includeArchivedModel.value);
const trashedOnlyDisabled = computed(() => !includeTrashedModel.value);
</script>
