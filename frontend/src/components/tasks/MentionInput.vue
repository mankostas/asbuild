<template>
  <VueSelect class="w-full">
    <template #default>
      <!-- eslint-disable vue/v-on-event-hyphenation -->
      <vSelect
        id="task-mention-input"
        label="name"
        multiple
        :options="options"
        :modelValue="modelValue"
        :placeholder="t('tasks.comments.mentionUsers')"
        :aria-label="t('tasks.comments.mentions')"
        @update:modelValue="update"
        @search="search"
      >
        <template #selected-option="{ option, deselect }">
          <span class="inline-flex items-center gap-1 bg-gray-200 rounded px-2 py-1">
            {{ option.name }}
            <button
              type="button"
              class="focus:outline-none"
              :aria-label="t('actions.delete')"
              @click.stop="deselect(option)"
              @keydown.enter.prevent="deselect(option)"
            >
              ×
            </button>
          </span>
        </template>
      </vSelect>
    </template>
  </VueSelect>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import vSelect from 'vue-select';

const props = defineProps<{ modelValue: any[] }>();
const emit = defineEmits<{ (e: 'update:modelValue', value: any[]): void }>();

const options = ref<any[]>([]);
const { t } = useI18n();

async function search(query: string) {
  const { data } = await api.get('/users', {
    params: { query, ability: 'tasks.view' },
  });
  options.value = data;
}

function update(val: any[]) {
  emit('update:modelValue', val);
}
</script>
