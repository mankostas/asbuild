import { defineStore } from 'pinia';
import api from '@/services/api';

export interface FeatureDefinition {
  label: string;
  abilities: string[];
  roles?: string[];
}

export type FeatureMap = Record<string, FeatureDefinition>;

export const useFeaturesStore = defineStore('features', {
  state: () => ({
    featureMap: {} as FeatureMap,
    abilities: [] as string[],
    fetchedAt: 0,
    abilitiesFetchedAt: 0,
  }),
  getters: {
    hasFeatureData: (state) => Object.keys(state.featureMap).length > 0,
    featureList: (state) =>
      Object.entries(state.featureMap).map(([slug, data]) => ({
        slug,
        label: data.label,
        abilities: data.abilities,
        roles: data.roles ?? [],
      })),
    featureOptions: (state) =>
      Object.entries(state.featureMap).map(([slug, data]) => ({
        value: slug,
        label: data.label,
      })),
    abilitiesFor: (state) => (slug: string) =>
      state.featureMap[slug]?.abilities ?? [],
    rolesFor: (state) => (slug: string) => state.featureMap[slug]?.roles ?? [],
  },
  actions: {
    async load(force = false) {
      if (!force && this.hasFeatureData) {
        return this.featureMap;
      }

      const { data } = await api.get<FeatureMap>('/lookups/feature-map');
      this.featureMap = data;
      this.fetchedAt = Date.now();

      return this.featureMap;
    },
    async loadAbilities(force = false) {
      if (!force && this.abilities.length) {
        return this.abilities;
      }

      const { data } = await api.get<string[]>('/lookups/abilities');
      this.abilities = data;
      this.abilitiesFetchedAt = Date.now();

      return this.abilities;
    },
  },
});
