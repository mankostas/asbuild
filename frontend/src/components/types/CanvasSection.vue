<template>
  <!-- eslint-disable vue/no-mutating-props -->
  <Card bodyClass="p-0">
    <header class="flex items-center justify-between bg-gray-50 px-2 py-1">
      <Button
        type="button"
        btnClass="btn-light p-1 handle cursor-move"
        aria-label="Drag section"
        @keydown.enter.prevent="noop"
        @keydown.space.prevent="noop"
      >
        <Icon icon="heroicons-outline:bars-3" />
      </Button>
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
        aria-describedby="remove-section-desc"
        @click="$emit('remove')"
      >
        ✕
      </Button>
    </header>
    <span id="remove-section-desc" class="sr-only">{{ t('actions.delete') }}</span>
    <draggable v-model="section.fields" item-key="id" handle=".field-handle" class="p-2 space-y-2">
      <template #item="{ element }">
        <Card
          bodyClass="p-2 flex items-center gap-2 cursor-pointer"
          tabindex="0"
          @click="$emit('select', element)"
          @keydown.enter.space.prevent="$emit('select', element)"
        >
          <Button
            type="button"
            btnClass="btn-light p-1 field-handle cursor-move"
            aria-label="Drag field"
            @keydown.enter.prevent="noop"
            @keydown.space.prevent="noop"
          >
            <Icon icon="heroicons-outline:bars-3" />
          </Button>
          <span>{{ resolveI18n(element.label) }}</span>
        </Card>
      </template>
    </draggable>
  </Card>
</template>

<script setup lang="ts">
import draggable from 'vuedraggable';
import { useI18n } from 'vue-i18n';
import { resolveI18n as resolveI18nUtil } from '@/utils/i18n';
import Icon from '@/components/ui/Icon/index.vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import Button from '@/components/ui/Button/index.vue';
import Card from '@/components/ui/Card/index.vue';

defineProps<{ section: any }>();
defineEmits<{ (e: 'remove'): void; (e: 'select', field: any): void }>();
const { t, locale } = useI18n();

const noop = () => {};

function resolveI18n(val: any) {
  return resolveI18nUtil(val, locale.value);
}
</script>
