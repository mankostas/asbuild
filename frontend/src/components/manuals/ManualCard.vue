<template>
  <div class="border p-4 rounded shadow">
    <div class="flex justify-between items-start">
      <div>
        <h3 class="font-bold">{{ manual.file?.filename }}</h3>
        <div class="text-sm text-gray-500">
          Updated {{ new Date(manual.updated_at).toLocaleDateString() }}
        </div>
        <div v-if="manual.category" class="text-sm">{{ manual.category }}</div>
      </div>
      <button
        class="text-yellow-500 text-xl"
        :aria-pressed="favorite"
        @click.stop="$emit('toggle-favorite', manual.id)"
      >
        {{ favorite ? '★' : '☆' }}
      </button>
    </div>
    <div class="mt-3 flex gap-2">
      <button class="text-blue-600" @click="$emit('open', manual.id)">Open</button>
      <button class="text-blue-600" @click="$emit('offline', manual)">
        {{ offline ? 'Remove Offline' : 'Keep Offline' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ manual: any; favorite: boolean; offline: boolean }>();
defineEmits(['open', 'toggle-favorite', 'offline']);
</script>
