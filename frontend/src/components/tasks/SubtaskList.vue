<template>
  <div>
    <h3 class="font-semibold mb-2">{{ t('tasks.subtasks.title') }}</h3>
    <draggable
      v-model="items"
      item-key="id"
      class="flex flex-col gap-2"
      handle=".handle"
      @end="onReorder"
    >
      <template #item="{ element }">
        <div class="flex items-center gap-2">
          <button
            class="handle cursor-move"
            aria-label="Drag to reorder"
          >⋮⋮</button>
          <input
            type="checkbox"
            v-model="element.is_completed"
            @change="save(element)"
            :aria-label="t('tasks.subtasks.title')"
          />
          <input
            v-model="element.title"
            @keyup.enter="save(element)"
            @blur="save(element)"
            :aria-label="t('tasks.subtasks.title')"
            class="flex-1 border rounded p-1"
          />
          <button
            @click="remove(element)"
            @keyup.enter="remove(element)"
            class="text-red-600"
            aria-label="Delete subtask"
          >✕</button>
        </div>
      </template>
    </draggable>
    <div class="mt-2 flex items-center gap-2">
      <input
        v-model="newTitle"
        @keyup.enter="add"
        :aria-label="t('tasks.subtasks.add')"
        class="flex-1 border rounded p-1"
        :placeholder="t('tasks.subtasks.add')"
      />
      <button
        @click="add"
        @keyup.enter="add"
        class="btn btn-primary"
        :aria-label="t('tasks.subtasks.add')"
      >{{ t('tasks.subtasks.add') }}</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import draggable from 'vuedraggable';

const props = defineProps<{ taskId: number }>();
const { t } = useI18n();
const items = ref<any[]>([]);
const newTitle = ref('');

async function load() {
  const { data } = await api.get(`/tasks/${props.taskId}`);
  items.value = data.subtasks || [];
}

onMounted(load);

async function add() {
  if (!newTitle.value) return;
  const { data } = await api.post(`/tasks/${props.taskId}/subtasks`, {
    title: newTitle.value,
  });
  items.value.push(data);
  newTitle.value = '';
}

async function save(item: any) {
  await api.patch(`/tasks/${props.taskId}/subtasks/${item.id}`, {
    title: item.title,
    is_completed: item.is_completed,
  });
}

async function remove(item: any) {
  await api.delete(`/tasks/${props.taskId}/subtasks/${item.id}`);
  items.value = items.value.filter((s) => s.id !== item.id);
}

async function onReorder() {
  await api.patch(`/tasks/${props.taskId}/subtasks/reorder`, {
    order: items.value.map((s) => s.id),
  });
}
</script>
