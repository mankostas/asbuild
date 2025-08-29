<template>
  <div class="flex flex-col gap-2">
    <Textarea
      v-model="body"
      :id="ids.body"
      :rows="3"
      label="Comment"
      placeholder="Add a comment"
    />
    <div>
      <label :for="ids.mentions" class="sr-only">Mentions</label>
      <VueSelect class="w-full">
        <template #default>
          <vSelect
            :id="ids.mentions"
            v-model="selectedMentions"
            :options="employees"
            label="name"
            multiple
            placeholder="Mention users"
          />
        </template>
      </VueSelect>
    </div>
    <Textinput
      v-if="allowFiles"
      v-model="fileIds"
      :id="ids.fileIds"
      label="File IDs"
      placeholder="File IDs comma separated"
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

const props = defineProps<{ appointmentId: number | string; allowFiles?: boolean }>();
const emit = defineEmits<{ (e: 'added', comment: any): void }>();

const body = ref('');
const selectedMentions = ref<any[]>([]);
const fileIds = ref('');
const employees = ref<any[]>([]);
const allowFiles = props.allowFiles ?? false;
const ids = {
  body: 'comment-body',
  mentions: 'comment-mentions',
  fileIds: 'comment-file-ids',
};

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
