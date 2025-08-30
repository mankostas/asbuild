<template>
  <div>
    <h2 class="text-lg font-semibold mb-2">{{ t('slaPolicies.title') }}</h2>
    <table class="w-full text-sm border" aria-label="SLA policies">
      <thead>
        <tr class="border-b">
          <th class="p-2 text-left">{{ t('slaPolicies.priority') }}</th>
          <th class="p-2 text-left">{{ t('slaPolicies.responseWithin') }}</th>
          <th class="p-2 text-left">{{ t('slaPolicies.resolveWithin') }}</th>
          <th class="p-2 text-left">{{ t('slaPolicies.calendar') }}</th>
          <th class="p-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(p, idx) in policies" :key="p.id ?? idx" class="border-b">
          <td class="p-2">
            <label :for="`priority-${idx}`" class="sr-only">{{ t('slaPolicies.priority') }}</label>
            <input
              :id="`priority-${idx}`"
              v-model="p.priority"
              class="border rounded px-2 py-1 w-full"
            />
          </td>
          <td class="p-2">
            <label :for="`response-${idx}`" class="sr-only">{{ t('slaPolicies.responseWithin') }}</label>
            <input
              type="number"
              :id="`response-${idx}`"
              v-model.number="p.response_within_mins"
              class="border rounded px-2 py-1 w-full"
            />
          </td>
          <td class="p-2">
            <label :for="`resolve-${idx}`" class="sr-only">{{ t('slaPolicies.resolveWithin') }}</label>
            <input
              type="number"
              :id="`resolve-${idx}`"
              v-model.number="p.resolve_within_mins"
              class="border rounded px-2 py-1 w-full"
            />
          </td>
          <td class="p-2">
            <label :for="`calendar-${idx}`" class="sr-only">{{ t('slaPolicies.calendar') }}</label>
            <textarea
              :id="`calendar-${idx}`"
              v-model="p.calendar_json"
              class="border rounded px-2 py-1 w-full"
              rows="2"
            />
          </td>
          <td class="p-2">
            <button
              type="button"
              class="px-2 py-1 border rounded"
              @click="save(p)"
              :aria-label="t('actions.save')"
            >{{ t('actions.save') }}</button>
          </td>
        </tr>
      </tbody>
    </table>
    <button
      type="button"
      class="mt-2 px-2 py-1 border rounded"
      @click="addPolicy"
      :aria-label="t('actions.add')"
    >{{ t('actions.add') }}</button>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';

interface Policy {
  id?: number;
  priority: string;
  response_within_mins?: number | null;
  resolve_within_mins?: number | null;
  calendar_json?: string | null;
}

const props = defineProps<{ taskTypeId: number }>();
const { t } = useI18n();
const policies = ref<Policy[]>([]);

onMounted(load);

async function load() {
  const res = await api.get(`/task-types/${props.taskTypeId}/sla-policies`);
  policies.value = res.data.data.map((p: any) => ({
    ...p,
    calendar_json: p.calendar_json ? JSON.stringify(p.calendar_json) : ''
  }));
}

function addPolicy() {
  policies.value.push({ priority: '', response_within_mins: null, resolve_within_mins: null, calendar_json: '{}' });
}

async function save(p: Policy) {
  const payload: any = {
    priority: p.priority,
    response_within_mins: p.response_within_mins,
    resolve_within_mins: p.resolve_within_mins,
    calendar_json: p.calendar_json ? JSON.parse(p.calendar_json) : null,
  };
  if (p.id) {
    const res = await api.put(`/task-types/${props.taskTypeId}/sla-policies/${p.id}`, payload);
    Object.assign(p, res.data.data, { calendar_json: p.calendar_json });
  } else {
    const res = await api.post(`/task-types/${props.taskTypeId}/sla-policies`, payload);
    Object.assign(p, res.data.data, { calendar_json: p.calendar_json });
  }
}
</script>
