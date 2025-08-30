<template>
  <div class="border rounded">
    <header class="flex items-center justify-between bg-gray-50 px-2 py-1">
      <span class="handle cursor-move" aria-label="Drag section">≡</span>
      <input
        v-model="section.label"
        class="flex-1 mx-2 border rounded px-2 py-1 text-sm"
        :aria-label="t('Section label')"
      />
      <button type="button" @click="$emit('remove')" aria-label="Remove section" class="text-red-600">✕</button>
    </header>
    <draggable v-model="section.fields" item-key="id" handle=".field-handle" class="p-2 space-y-2">
      <template #item="{ element }">
        <div
          class="p-2 border rounded flex items-center gap-2 cursor-pointer"
          @click="$emit('select', element)"
          tabindex="0"
        >
          <span class="field-handle cursor-move" aria-label="Drag field">≡</span>
          <span>{{ element.label }}</span>
        </div>
      </template>
    </draggable>
  </div>
</template>

<script setup lang="ts">
import draggable from 'vuedraggable';
import { useI18n } from 'vue-i18n';

const props = defineProps<{ section: any }>();
const emit = defineEmits<{ (e: 'remove'): void; (e: 'select', field: any): void }>();
const { t } = useI18n();
</script>
