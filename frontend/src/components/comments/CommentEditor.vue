<template>
  <div class="flex flex-col gap-2">
    <Textarea v-model="body" rows="3" placeholder="Add a comment" />
    <MultiSelect
      v-model="selectedMentions"
      :options="employees"
      option-label="name"
      option-value="id"
      placeholder="Mention users"
      display="chip"
      filter
      class="w-full"
    />
    <InputText
      v-if="allowFiles"
      v-model="fileIds"
      placeholder="File IDs comma separated"
    />
    <Button label="Comment" @click="submit" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import Textarea from 'primevue/textarea';
import MultiSelect from 'primevue/multiselect';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

const props = defineProps<{ appointmentId: number | string; allowFiles?: boolean }>();
const emit = defineEmits<{ (e: 'added', comment: any): void }>();

const body = ref('');
const selectedMentions = ref<any[]>([]);
const fileIds = ref('');
const employees = ref<any[]>([]);
const allowFiles = props.allowFiles ?? false;

onMounted(async () => {
  const { data } = await api.get('/employees');
  employees.value = data;
});

async function submit() {
  const mentions = selectedMentions.value.map((m: any) => m.id ?? m);
  const files = allowFiles
    ? fileIds.value
        .split(',')
        .map((s) => parseInt(s.trim()))
        .filter((n) => !isNaN(n))
    : [];
  const { data } = await api.post(`/appointments/${props.appointmentId}/comments`, {
    body: body.value,
    mentions,
    files,
  });
  emit('added', data);
  body.value = '';
  selectedMentions.value = [];
  fileIds.value = '';
}
</script>
