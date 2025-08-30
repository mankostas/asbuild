<template>
  <Drawer :open="open" @close="close">
    <div class="w-full max-w-md p-4 space-y-6">
      <h2 class="text-lg font-bold">{{ t('templates.title') }}</h2>
      <div>
        <Select
          id="export-select"
          v-model="exportId"
          :label="t('templates.export')"
          :options="selectOptions"
          :placeholder="t('templates.selectType')"
        />
        <Button
          class="mt-2"
          :text="t('actions.export')"
          :is-disabled="!exportId"
          @click="doExport"
        />
      </div>
      <div>
        <p class="mb-2">{{ t('templates.import') }}</p>
        <Fileinput
          name="import-input"
          :placeholder="t('templates.import')"
          @input="onFile"
        />
      </div>
      <Button
        btn-class="btn-outline-secondary"
        :text="t('actions.close')"
        @click="close"
      />
    </div>
  </Drawer>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import Drawer from '@/components/ui/Drawer/index.vue';
import Select from '@/components/ui/Select/index.vue';
import Button from '@/components/ui/Button/index.vue';
import Fileinput from '@/components/ui/Fileinput/index.vue';
import { useTaskTypesStore } from '@/stores/taskTypes';

interface Props {
  open: boolean;
  types: any[];
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'imported']);
const exportId = ref<number>();
const typesStore = useTaskTypesStore();
const { t } = useI18n();

const selectOptions = computed(() =>
  props.types.map((t: any) => ({ value: t.id, label: t.name }))
);

function close() {
  emit('close');
}

async function doExport() {
  if (!exportId.value) return;
  const data = await typesStore.export(exportId.value);
  const blob = new Blob([JSON.stringify(data, null, 2)], {
    type: 'application/json',
  });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `task-type-${exportId.value}.json`;
  a.click();
  URL.revokeObjectURL(url);
}

async function onFile(file: File) {
  if (!file) return;
  const text = await file.text();
  const json = JSON.parse(text);
  await typesStore.import(json);
  emit('imported');
}
</script>
