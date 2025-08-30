<template>
  <div>
    <h2 class="text-lg font-semibold mb-2">{{ t('automations.title') }}</h2>
    <div class="bg-white dark:bg-slate-800 rounded-md shadow-base">
      <div class="overflow-x-auto">
        <table
          class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700"
          aria-label="Automations"
        >
          <thead class="bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
            <tr>
              <th scope="col" class="table-th">{{ t('automations.event') }}</th>
              <th scope="col" class="table-th">{{ t('automations.status') }}</th>
              <th scope="col" class="table-th">{{ t('automations.team') }}</th>
              <th scope="col" class="table-th">{{ t('automations.enabled') }}</th>
              <th scope="col" class="table-th sr-only">{{ t('actions.save') }}</th>
            </tr>
          </thead>
          <tbody
            class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700"
          >
            <tr
              v-for="(a, idx) in automations"
              :key="a.id ?? idx"
              class="hover:bg-slate-50 dark:hover:bg-slate-700"
            >
              <td class="table-td">
                <Select
                  :id="`event-${idx}`"
                  v-model="a.event"
                  :label="t('automations.event')"
                  class="w-full"
                >
                  <option value="status_changed">status_changed</option>
                </Select>
              </td>
              <td class="table-td">
                <Select
                  :id="`status-${idx}`"
                  v-model="a.conditions_json.status"
                  :label="t('automations.status')"
                  class="w-full"
                >
                  <option
                    v-for="s in statusOptions"
                    :key="s.value"
                    :value="s.value"
                  >
                    {{ s.label }}
                  </option>
                </Select>
              </td>
              <td class="table-td">
                <Select
                  :id="`team-${idx}`"
                  v-model="a.actions_json[0].team_id"
                  :label="t('automations.team')"
                  class="w-full"
                  @change="(e) => (a.actions_json[0].team_id = Number((e.target as HTMLSelectElement).value))"
                >
                  <option
                    v-for="team in teamOptions"
                    :key="team.value"
                    :value="team.value"
                  >
                    {{ team.label }}
                  </option>
                </Select>
              </td>
              <td class="table-td">
                <Switch
                  :id="`enabled-${idx}`"
                  v-model="a.enabled"
                  :aria-label="t('automations.enabled')"
                />
              </td>
              <td class="table-td">
                <Button
                  type="button"
                  btnClass="btn-outline-primary text-xs px-3 py-1"
                  :aria-label="t('actions.save')"
                  @click="save(a)"
                >
                  {{ t('actions.save') }}
                </Button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <Button
      type="button"
      class="mt-2"
      btnClass="btn-outline-primary text-xs px-3 py-1"
      :aria-label="t('actions.add')"
      @click="addAutomation"
    >
      {{ t('actions.add') }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/services/api';
import Select from '@/components/ui/Select/index.vue';
import Switch from '@/components/ui/Switch/index.vue';
import Button from '@/components/ui/Button/index.vue';

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
const statusOptions = ref<{ value: string; label: string }[]>([]);
const teamOptions = ref<{ value: number; label: string }[]>([]);

onMounted(load);

async function load() {
  const [res, statusRes, teamRes] = await Promise.all([
    api.get(`/task-types/${props.taskTypeId}/automations`),
    api.get('/task-statuses'),
    api.get('/teams'),
  ]);
  automations.value = res.data.data;
  statusOptions.value = statusRes.data.map((s: any) => ({
    value: s.slug,
    label: s.name,
  }));
  teamOptions.value = teamRes.data.map((t: any) => ({
    value: t.id,
    label: t.name,
  }));
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
