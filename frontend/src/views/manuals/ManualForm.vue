<template>
  <TransitionRoot appear :show="true" as="template">
    <Dialog as="div" class="relative z-50" @close="emit('close')">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-slate-900/60" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-lg rounded-md bg-white dark:bg-slate-800 p-6">
              <h2 class="text-lg font-bold mb-4">
                {{ isEdit ? 'Edit Manual' : 'Upload Manual' }}
              </h2>
              <form class="grid gap-4" @submit.prevent="onSubmit">
                <div>
                  <div
                    v-bind="getRootProps()"
                    class="border-dashed border-2 rounded-md p-6 text-center cursor-pointer"
                  >
                    <input v-bind="getInputProps()" class="hidden" />
                    <p v-if="!file">Drop file here or click to upload</p>
                    <p v-else>{{ file.name }}</p>
                  </div>
                </div>
                <div>
                  <label class="block mb-1">Category</label>
                  <input v-model="category" class="border p-2 w-full" />
                </div>
                <div>
                  <label class="block mb-1">Tags (comma separated)</label>
                  <input v-model="tags" class="border p-2 w-full" />
                </div>
                <div class="flex justify-end gap-2 mt-4">
                  <button type="button" class="btn btn-outline-secondary" @click="emit('close')">Cancel</button>
                  <button type="submit" class="btn btn-primary">Save</button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
} from '@headlessui/vue';
import { useDropzone } from 'vue3-dropzone';
import api from '@/services/api';

const props = defineProps<{ manualId?: string | null }>();
const emit = defineEmits(['saved', 'close']);

const file = ref<File | null>(null);
const category = ref('');
const tags = ref('');

const isEdit = computed(() => !!props.manualId);

function onDrop(accepted: File[]) {
  file.value = accepted[0] || null;
}

const { getRootProps, getInputProps } = useDropzone({ onDrop, multiple: false });

onMounted(async () => {
  if (isEdit.value && props.manualId) {
    const { data } = await api.get(`/manuals/${props.manualId}`);
    category.value = data.category || '';
    tags.value = (data.tags || []).join(', ');
  }
});

async function onSubmit() {
  const tagsArray = tags.value
    .split(',')
    .map((t) => t.trim())
    .filter((t) => t);

  if (isEdit.value && props.manualId) {
    await api.patch(`/manuals/${props.manualId}`, {
      category: category.value || null,
      tags: tagsArray,
    });
    if (file.value) {
      const form = new FormData();
      form.append('file', file.value);
      await api.post(`/manuals/${props.manualId}/replace`, form);
    }
  } else {
    if (!file.value) return;
    const form = new FormData();
    form.append('file', file.value);
    if (category.value) form.append('category', category.value);
    tagsArray.forEach((t) => form.append('tags[]', t));
    await api.post('/manuals', form);
  }

  emit('saved');
}
</script>
