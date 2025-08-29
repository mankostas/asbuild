<template>
  <div class="flex flex-col gap-2">
    <span class="sr-only">Comment</span>
    <Textarea
      id="comment-body"
      v-model="body"
      :rows="3"
      placeholder="Add a comment"
      aria-label="Comment"
    />
    <span class="sr-only">Mentions</span>
    <VueSelect class="w-full">
      <template #default>
        <vSelect
          id="mention-select"
          v-model="selectedMentions"
          :options="employees"
          label="name"
          multiple
          placeholder="Mention users"
          aria-label="Mentions"
        />
      </template>
    </VueSelect>
    <span v-if="allowFiles" class="sr-only">File IDs</span>
    <Textinput
      v-if="allowFiles"
      id="file-ids"
      v-model="fileIds"
      placeholder="File IDs comma separated"
      aria-label="File IDs"
    />
    <Button text="Comment" @click="submit" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import Textarea from '@/components/ui/Textarea/index.vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import Textinput from '@/components/ui/Textinput/index.vue';
import Button from '@/components/ui/Button/index.vue';
import vSelect from 'vue-select';

const props = defineProps<{ taskId: number | string; allowFiles?: boolean }>();
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
  const { data } = await api.post(`/tasks/${props.taskId}/comments`, {
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
