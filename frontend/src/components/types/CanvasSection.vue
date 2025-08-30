<template>
  <div class="border rounded">
    <header class="flex items-center justify-between bg-gray-50 px-2 py-1">
      <Icon
        icon="heroicons-outline:bars-3"
        class="handle cursor-move"
        aria-label="Drag section"
      />
      <Textinput
        v-model="section.label[locale]"
        :label="t('Section label')"
        class="flex-1 mx-2"
        classInput="text-sm"
      />
      <Button
        type="button"
        btnClass="btn-outline-danger text-xs px-2 py-1"
        :aria-label="t('actions.delete')"
        @click="$emit('remove')"
      >
        ✕
      </Button>
    </header>
    <draggable v-model="section.fields" item-key="id" handle=".field-handle" class="p-2 space-y-2">
      <template #item="{ element }">
        <div
          class="p-2 border rounded flex items-center gap-2 cursor-pointer"
          @click="$emit('select', element)"
          tabindex="0"
        >
          <Icon
            icon="heroicons-outline:bars-3"
            class="field-handle cursor-move"
            aria-label="Drag field"
          />
          <span>{{ resolveI18n(element.label) }}</span>
        </div>
      </template>
    </draggable>
  </div>
</template>

<script setup lang="ts">
import draggable from 'vuedraggable';
import { useI18n } from 'vue-i18n';
import { resolveI18n as resolveI18nUtil } from '@/utils/i18n';
import Icon from '@/components/ui/Icon/index.vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import Button from '@/components/ui/Button/index.vue';

const props = defineProps<{ section: any }>();
const emit = defineEmits<{ (e: 'remove'): void; (e: 'select', field: any): void }>();
const { t, locale } = useI18n();

function resolveI18n(val: any) {
  return resolveI18nUtil(val, locale.value);
}
</script>
