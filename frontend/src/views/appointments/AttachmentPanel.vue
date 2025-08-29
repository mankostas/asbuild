<template>
  <Card>
    <div class="mb-4">
      <label :for="ids.file" class="block mb-1">Attachment</label>
      <input :id="ids.file" type="file" @change="onFileChange" />
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

const props = defineProps<{ appointmentId: number }>();
const attachments = ref<any[]>([]);
const ids = { file: 'attachment-file' };

async function load() {
  const { data } = await api.get(`/appointments/${props.appointmentId}`);
  attachments.value = data.photos?.filter((p: any) => p.type === 'attachment') || [];
}

async function onFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (!file) return;
  const uploaded = await uploadFile(file);
  await api.post(`/appointments/${props.appointmentId}/files`, { file_id: uploaded.file_id });
  await load();
}

onMounted(load);
</script>
