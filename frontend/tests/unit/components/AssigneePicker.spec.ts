/** @vitest-environment jsdom */
import { describe, it, expect, vi } from 'vitest';
import { createApp, nextTick } from 'vue';
import AssigneePicker from '@/components/appointments/AssigneePicker.vue';

const fetchAssignees = vi.fn().mockResolvedValue([]);

vi.mock('@/stores/lookups', () => ({
  useLookupsStore: () => ({
    assignees: { teams: [], employees: [] },
    fetchAssignees,
  }),
}));

describe('AssigneePicker', () => {
  it('fetches assignees on mount when none loaded', async () => {
    const app = createApp(AssigneePicker, { modelValue: null });
    const div = document.createElement('div');
    app.mount(div);
    await nextTick();
    expect(fetchAssignees).toHaveBeenCalledWith('all');
  });
});
