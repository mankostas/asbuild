import api from './api';

export async function unpublishTaskTypeVersion(id: number) {
  const { data } = await api.put(`/task-type-versions/${id}/unpublish`);
  return data;
}

