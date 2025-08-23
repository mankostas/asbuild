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
    <div class="mt-4">
      <textarea v-model="body" class="w-full border p-2" rows="3" placeholder="Add a comment"></textarea>
      <input type="text" v-model="fileIds" placeholder="File IDs comma separated" class="w-full border p-2 mt-2" />
      <button class="bg-blue-600 text-white px-4 py-2 mt-2" @click="submit">Comment</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import api from '@/services/api';

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
