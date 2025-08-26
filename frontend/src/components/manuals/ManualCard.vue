<template>
  <div
    class="bg-white dark:bg-slate-800 rounded-md shadow p-4 flex flex-col h-full"
  >
    <div class="flex justify-between items-start">
      <div>
        <h3 class="font-medium text-base">{{ manual.file?.filename }}</h3>
        <div class="text-xs text-gray-500">
          Updated {{ new Date(manual.updated_at).toLocaleDateString() }}
        </div>
        <div v-if="manual.category" class="text-xs mt-1">
          {{ manual.category }}
        </div>
      </div>
      <button
        class="text-yellow-500 text-xl"
        :aria-pressed="favorite"
        @click.stop="$emit('toggle-favorite', manual.id)"
      >
        <Icon
          :icon="favorite ? 'heroicons-solid:star' : 'heroicons-outline:star'"
        />
      </button>
    </div>

    <div
      v-if="manual.tags && manual.tags.length"
      class="mt-3 flex flex-wrap gap-1"
    >
      <span
        v-for="tag in manual.tags"
        :key="tag"
        class="text-xs bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-200 px-2 py-0.5 rounded-full"
      >
        {{ tag }}
      </span>
    </div>

    <div class="mt-auto pt-4 flex gap-2">
      <button class="btn btn-outline-primary" @click="$emit('open', manual.id)">
        Open
      </button>
      <button class="btn btn-outline-secondary" @click="$emit('offline', manual)">
        {{ offline ? 'Remove Offline' : 'Keep Offline' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import Icon from '@/components/ui/Icon/index.vue';

defineProps<{ manual: any; favorite: boolean; offline: boolean }>();
defineEmits(['open', 'toggle-favorite', 'offline']);
</script>
