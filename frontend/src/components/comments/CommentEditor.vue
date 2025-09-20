<template>
  <div class="flex flex-col gap-2">
    <span id="comment-body-label" class="sr-only">
      {{ t('tasks.comments.comments') }}
    </span>
    <Textarea
      id="comment-body"
      v-model="body"
      :rows="3"
      placeholder="Add a comment"
      :aria-labelledby="'comment-body-label'"
    />
    <MentionInput v-model="selectedMentions" />
    <div v-if="allowFiles" class="flex flex-col">
      <span id="comment-file-label" class="sr-only">
        {{ t('tasks.comments.file') }}
      </span>
      <input
        id="comment-file"
        type="file"
        :aria-labelledby="'comment-file-label'"
        @change="onFileChange"
      />
    </div>
    <Button text="Comment" @click="submit" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import Textarea from '@/components/ui/Textarea/index.vue';
import Button from '@/components/ui/Button/index.vue';
import MentionInput from '@/components/tasks/MentionInput.vue';
import { uploadFile } from '@/services/uploader';

const props = defineProps<{ taskId: string; allowFiles?: boolean }>();
const emit = defineEmits<{ (e: 'added', comment: any): void }>();

const body = ref('');
const selectedMentions = ref<any[]>([]);
const fileIds = ref<string[]>([]);
const allowFiles = props.allowFiles ?? false;
const { t } = useI18n();

async function onFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (!file) return;
  const uploaded = await uploadFile(file);
  fileIds.value.push(String(uploaded.file_id));
}

async function submit() {
  const mentions = selectedMentions.value
    .map((m: any) => (m?.id !== undefined ? String(m.id) : m ? String(m) : ''))
    .filter((value) => value.length > 0);
  const { data } = await api.post(`/tasks/${props.taskId}/comments`, {
    body: body.value,
    mentions,
    files: fileIds.value,
  });
  emit('added', data);
  body.value = '';
  selectedMentions.value = [];
  fileIds.value = [];
}
</script>
