<template>
  <!-- eslint-disable vue/no-mutating-props -->
  <Card bodyClass="p-0">
    <header class="flex items-center justify-between bg-gray-50 px-2 py-1">
      <Button
        type="button"
        btnClass="btn-light p-1 handle cursor-move"
        aria-label="Drag section"
        aria-describedby="reorderHint reorderHintMobile"
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
    <p id="fieldReorderHint" class="sr-only">{{ t('fields.reorderHint') }}</p>
    <draggable
      v-model="section.fields"
      item-key="id"
      handle=".field-handle"
      class="p-2 space-y-2"
      aria-describedby="fieldReorderHint"
    >
      <template #item="{ element }">
        <Card
          bodyClass="p-2 flex items-center gap-2 cursor-pointer"
          tabindex="0"
          role="button"
          @click="$emit('select', element)"
          @keydown.enter.prevent="$emit('select', element)"
          @keydown.space.prevent="$emit('select', element)"
        >
          <Button
            type="button"
            btnClass="btn-light p-1 field-handle cursor-move"
            aria-label="Drag field"
            aria-describedby="fieldReorderHint"
            @keydown.enter.prevent="noop"
            @keydown.space.prevent="noop"
          >
            <Icon icon="heroicons-outline:bars-3" />
          </Button>
          <span class="flex-1">{{ resolveI18n(element.label) }}</span>
          <Button
            type="button"
            btnClass="btn-outline-danger text-xs px-1 py-1"
            :aria-label="t('actions.delete')"
            @click.stop="$emit('remove-field', element)"
          >
            ✕
          </Button>
        </Card>
      </template>
    </draggable>
    <div class="p-2">
      <Dropdown>
        <template #default>
          <Button
            type="button"
            btnClass="btn-primary text-xs px-2 py-1 flex items-center gap-1"
            :aria-label="t('actions.add')"
          >
            {{ t('actions.add') }}
            <Icon icon="heroicons-outline:chevron-down" />
          </Button>
        </template>
        <template #menus>
          <MenuItem #default="{ active }">
            <button type="button" :class="menuItemClass(active)" @click="$emit('add-field')">
              {{ t('actions.addField') }}
            </button>
          </MenuItem>
          <MenuItem #default="{ active }">
            <button type="button" :class="menuItemClass(active)" @click="$emit('add-section')">
              {{ t('actions.addSection') }}
            </button>
          </MenuItem>
        </template>
      </Dropdown>
    </div>
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
import Dropdown from '@/components/ui/Dropdown/index.vue';
import { MenuItem } from '@headlessui/vue';

defineProps<{ section: any }>();
defineEmits<{
  (e: 'remove'): void;
  (e: 'select', field: any): void;
  (e: 'add-field'): void;
  (e: 'add-section'): void;
  (e: 'remove-field', field: any): void;
}>();
const { t, locale } = useI18n();

const noop = () => {};

function resolveI18n(val: any) {
  return resolveI18nUtil(val, locale.value);
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
