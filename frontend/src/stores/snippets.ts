import { defineStore } from 'pinia';

export interface TaskFieldSnippet {
  id: string;
  name: string;
  fields: string[];
}

export const useSnippetsStore = defineStore('snippets', {
  state: () => ({
    snippets: [] as TaskFieldSnippet[],
  }),
  actions: {
    async create(snippet: Omit<TaskFieldSnippet, 'id'> & { id?: string }) {
      await Promise.resolve();
      const id = snippet.id ?? `${Date.now()}`;
      const newSnippet: TaskFieldSnippet = {
        id,
        name: snippet.name,
        fields: [...snippet.fields],
      };
      this.snippets.push(newSnippet);
      return newSnippet;
    },
    async get(id: string) {
      await Promise.resolve();
      return this.snippets.find((s) => s.id === id);
    },
  },
});
