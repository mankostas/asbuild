<template>
  <div>
    <div v-for="comment in comments" :key="comment.id" class="mb-4">
      <div class="font-semibold">{{ comment.user?.name }}</div>
      <div class="whitespace-pre-line">{{ comment.body }}</div>
      <ul v-if="comment.files?.length" class="mt-2 list-disc list-inside">
        <li v-for="file in comment.files" :key="file.id">
          <a :href="`/api/files/${file.id}`" target="_blank">{{ file.filename }}</a>
        </li>
      </ul>
    </div>
    <div class="mt-4 flex flex-col gap-2">
      <Textarea v-model="body" rows="3" placeholder="Add a comment" />
      <InputText v-model="fileIds" placeholder="File IDs comma separated" />
      <Button label="Comment" @click="submit" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import api from '@/services/api';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

const props = defineProps<{ appointmentId: number | string }>();

const comments = ref<any[]>([]);
const body = ref('');
const fileIds = ref('');

async function load() {
  const { data } = await api.get(`/appointments/${props.appointmentId}/comments`);
  comments.value = data;
}

async function submit() {
  const ids = fileIds.value
    .split(',')
    .map((s) => parseInt(s.trim()))
    .filter((n) => !isNaN(n));
  const { data } = await api.post(`/appointments/${props.appointmentId}/comments`, {
    body: body.value,
    files: ids,
  });
  comments.value.push(data);
  body.value = '';
  fileIds.value = '';
}

onMounted(load);
watch(() => props.appointmentId, load);
</script>
