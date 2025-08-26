<template>
  <div>
    <div
      v-for="c in comments"
      :key="c.id"
      class="mb-4 border-b pb-2 last:border-b-0"
    >
      <div class="text-sm text-gray-600 mb-1">
        {{ c.user?.name || 'Unknown' }} ·
        <span>{{ formatDate(c.created_at) }}</span>
      </div>
      <div class="whitespace-pre-line">{{ c.body }}</div>
      <div
        v-if="c.files?.length"
        class="mt-2 flex flex-wrap gap-2"
      >
        <template v-for="file in c.files" :key="file.id">
          <img
            v-if="hasThumb(file)"
            :src="file.variants.thumb"
            class="w-16 h-16 object-cover rounded"
          />
          <div
            v-else
            class="flex items-center gap-2 px-2 py-1 border rounded text-xs text-gray-600"
          >
            <span>{{ file.filename }}</span>
            <button disabled class="text-gray-400 cursor-not-allowed">Open</button>
          </div>
          <!-- TODO: needs signed URL endpoint -->
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ comments: any[] }>();

function formatDate(d?: string) {
  return d ? new Date(d).toLocaleString() : '';
}

function hasThumb(file: any) {
  if (file?.variants?.thumb) return true;
  console.warn('TODO: needs signed URL endpoint');
  return false;
}
</script>
