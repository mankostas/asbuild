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
                  <option value="status_changed">{{ t('automations.events.statusChanged') }}</option>
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
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { can } from '@/stores/auth';
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
  _saved?: boolean;
}

const props = defineProps<{
  taskTypeId?: number;
  tenantId?: number | '';
  statuses?: string[];
}>();
const { t } = useI18n();
const automations = ref<Automation[]>([]);
const statusOptions = ref<{ value: string; label: string }[]>([]);
const allStatusOptions = ref<{ value: string; label: string }[]>([]);
const teamOptions = ref<{ value: number; label: string }[]>([]);
const initialized = ref(false);

watch(
  () => props.tenantId,
  async (id: number | '' | undefined) => {
    if (id) {
      await load(id);
      initialized.value = true;
    } else if (initialized.value) {
      statusOptions.value = [];
      allStatusOptions.value = [];
      teamOptions.value = [];
      automations.value = [];
    }
  },
  { immediate: true },
);

async function load(id: number | string) {
  try {
    const statusRes = await api.get('/task-statuses', {
      params: { scope: 'tenant', tenant_id: id, per_page: 100 },
    });
    const statusData = statusRes.data.data ?? statusRes.data;
    allStatusOptions.value = statusData.map((s: any) => ({
      value: s.slug,
      label: s.name,
    }));
    filterStatuses();

    if (can('teams.view')) {
      const teamRes = await api.get('/teams', { params: { tenant_id: id } });
      const teamData = teamRes.data.data ?? teamRes.data;
      teamOptions.value = teamData.map((t: any) => ({
        value: t.id,
        label: t.name,
      }));
    } else {
      teamOptions.value = [];
    }

    if (props.taskTypeId) {
      const res = await api.get(`/task-types/${props.taskTypeId}/automations`);
      automations.value = res.data.data ?? res.data;
    }
  } catch (e) {
    statusOptions.value = [];
    allStatusOptions.value = [];
    teamOptions.value = [];
    automations.value = [];
  }
}

function addAutomation() {
  automations.value.push({
    event: 'status_changed',
    conditions_json: { status: '' },
    actions_json: [{ type: 'notify_team', team_id: null }],
    enabled: true,
    _saved: false,
  });
}

async function save(a: Automation) {
  if (!props.taskTypeId) {
    a._saved = true;
    return;
  }
  const payload = {
    event: a.event,
    conditions_json: a.conditions_json,
    actions_json: a.actions_json,
    enabled: a.enabled,
  };
  if (a.id) {
    const res = await api.put(
      `/task-types/${props.taskTypeId}/automations/${a.id}`,
      payload,
    );
    Object.assign(a, res.data.data);
  } else {
    const res = await api.post(
      `/task-types/${props.taskTypeId}/automations`,
      payload,
    );
    Object.assign(a, res.data.data);
  }
  a._saved = true;
}

defineExpose({
  getAutomations: () => automations.value,
  reload: (id?: number | string) => {
    if (id || props.tenantId) {
      load(id || (props.tenantId as number | string));
      initialized.value = true;
    }
  },
});

watch(
  () => props.statuses,
  () => {
    filterStatuses();
  },
  { immediate: true },
);

function filterStatuses() {
  if (props.statuses && props.statuses.length) {
    statusOptions.value = allStatusOptions.value.filter((s) =>
      props.statuses!.includes(s.value),
    );
  } else {
    statusOptions.value = [...allStatusOptions.value];
  }
}
</script>
