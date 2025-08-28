<template>
  <div>
    <form @submit.prevent="submit" class="grid gap-4 max-w-lg">
      <Textinput label="Name" v-model="form.name" />
      <Textinput label="Description" v-model="form.description" />
      <VueSelect label="Employees">
        <vSelect
          v-model="selectedEmployees"
          :options="employeeOptions"
          :reduce="(e: any) => e.id"
          label="name"
          multiple
        />
      </VueSelect>
      <Button
        type="submit"
        :text="isEdit ? 'Save' : 'Create'"
        btnClass="btn-dark"
      />
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/services/api';
import Textinput from '@/components/ui/Textinput/index.vue';
import VueSelect from '@/components/ui/Select/VueSelect.vue';
import Button from '@/components/ui/Button/index.vue';
import vSelect from 'vue-select';
import { useTeamsStore } from '@/stores/teams';

const route = useRoute();
const router = useRouter();
const teamsStore = useTeamsStore();

const isEdit = computed(() => route.name === 'teams.edit');

const form = ref({
  name: '',
  description: '',
});

const employeeOptions = ref<any[]>([]);
const selectedEmployees = ref<number[]>([]);

async function loadEmployees() {
  const { data } = await api.get('/employees');
  employeeOptions.value = data;
}

async function loadTeam() {
  if (!isEdit.value) return;
  const team = await teamsStore.get(Number(route.params.id));
  if (team) {
    form.value.name = team.name || '';
    form.value.description = team.description || '';
    selectedEmployees.value = (team.employees || []).map((e: any) => e.id);
  }
}

async function submit() {
  const payload = {
    name: form.value.name,
    description: form.value.description,
  };
  let teamId: number;
  if (isEdit.value) {
    const updated = await teamsStore.update(Number(route.params.id), payload);
    teamId = updated.id;
  } else {
    const created = await teamsStore.create(payload);
    teamId = created.id;
  }
  await teamsStore.syncEmployees(teamId, selectedEmployees.value);
  router.push({ name: 'teams.list' });
}

onMounted(async () => {
  await loadEmployees();
  await loadTeam();
});
</script>

