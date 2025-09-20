<template>
  <Card>
    <div class="mb-4">
      <span id="attachment-file-label" class="block mb-1">Attachment</span>
      <input
        id="attachment-file"
        type="file"
        aria-labelledby="attachment-file-label"
        @change="onFileChange"
      />
    </div>
    <ul class="text-sm">
      <li v-for="att in attachments" :key="att.id">
        {{ att.file?.filename }}
      </li>
    </ul>
  </Card>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import { uploadFile } from '@/services/uploader';
import Card from '@/components/ui/Card/index.vue';

const props = defineProps<{ taskId: string }>();
const attachments = ref<any[]>([]);

async function load() {
  const { data } = await api.get(`/tasks/${props.taskId}`);
  attachments.value = data.photos?.filter((p: any) => p.type === 'attachment') || [];
}

async function onFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (!file) return;
  const uploaded = await uploadFile(file);
  await api.post(`/tasks/${props.taskId}/files`, { file_id: uploaded.file_id });
  await load();
}

onMounted(load);
</script>
