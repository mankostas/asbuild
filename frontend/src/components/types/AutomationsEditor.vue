<template>
  <div>
    <h2 class="text-lg font-semibold mb-2">{{ t('automations.title') }}</h2>
    <table class="w-full text-sm border" aria-label="Automations">
      <thead>
        <tr class="border-b">
          <th class="p-2 text-left">{{ t('automations.event') }}</th>
          <th class="p-2 text-left">{{ t('automations.status') }}</th>
          <th class="p-2 text-left">{{ t('automations.team') }}</th>
          <th class="p-2 text-left">{{ t('automations.enabled') }}</th>
          <th class="p-2"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(a, idx) in automations" :key="a.id ?? idx" class="border-b">
          <td class="p-2">
            <label :for="`event-${idx}`" class="sr-only">{{ t('automations.event') }}</label>
            <select
              :id="`event-${idx}`"
              v-model="a.event"
              class="border rounded px-2 py-1 w-full"
            >
              <option value="status_changed">status_changed</option>
            </select>
          </td>
          <td class="p-2">
            <label :for="`status-${idx}`" class="sr-only">{{ t('automations.status') }}</label>
            <input
              :id="`status-${idx}`"
              v-model="a.conditions_json.status"
              class="border rounded px-2 py-1 w-full"
            />
          </td>
          <td class="p-2">
            <label :for="`team-${idx}`" class="sr-only">{{ t('automations.team') }}</label>
            <input
              type="number"
              :id="`team-${idx}`"
              v-model.number="a.actions_json[0].team_id"
              class="border rounded px-2 py-1 w-full"
            />
          </td>
          <td class="p-2">
            <label :for="`enabled-${idx}`" class="sr-only">{{ t('automations.enabled') }}</label>
            <input
              type="checkbox"
              :id="`enabled-${idx}`"
              v-model="a.enabled"
              class="mr-2"
            />
          </td>
          <td class="p-2">
            <button
              type="button"
              class="px-2 py-1 border rounded"
              @click="save(a)"
              :aria-label="t('actions.save')"
            >{{ t('actions.save') }}</button>
          </td>
        </tr>
      </tbody>
    </table>
    <button
      type="button"
      class="mt-2 px-2 py-1 border rounded"
      @click="addAutomation"
      :aria-label="t('actions.add')"
    >{{ t('actions.add') }}</button>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';

interface Automation {
  id?: number;
  event: string;
  conditions_json: Record<string, any>;
  actions_json: any[];
  enabled: boolean;
}

const props = defineProps<{ taskTypeId: number }>();
const { t } = useI18n();
const automations = ref<Automation[]>([]);

onMounted(load);

async function load() {
  const res = await api.get(`/task-types/${props.taskTypeId}/automations`);
  automations.value = res.data.data;
}

function addAutomation() {
  automations.value.push({
    event: 'status_changed',
    conditions_json: { status: '' },
    actions_json: [{ type: 'notify_team', team_id: null }],
    enabled: true,
  });
}

async function save(a: Automation) {
  const payload = {
    event: a.event,
    conditions_json: a.conditions_json,
    actions_json: a.actions_json,
    enabled: a.enabled,
  };
  if (a.id) {
    const res = await api.put(`/task-types/${props.taskTypeId}/automations/${a.id}`, payload);
    Object.assign(a, res.data.data);
  } else {
    const res = await api.post(`/task-types/${props.taskTypeId}/automations`, payload);
    Object.assign(a, res.data.data);
  }
}
</script>
