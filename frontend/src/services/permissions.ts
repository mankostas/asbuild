import { reactive, readonly } from 'vue';
import api from './api';

export interface FeatureDefinition {
  label: string;
  abilities: string[];
  roles?: string[];
}

export type FeatureMap = Record<string, FeatureDefinition>;

interface PermissionState {
  featureMap: FeatureMap;
  abilityList: string[];
  abilityLookup: Record<string, Record<string, string>>;
  loaded: boolean;
}

const state = reactive<PermissionState>({
  featureMap: {},
  abilityList: [],
  abilityLookup: {},
  loaded: false,
});

let loadPromise: Promise<void> | null = null;
const listeners: Array<() => void> = [];

function buildAbilityLookup(): void {
  const lookup: Record<string, Record<string, string>> = {};

  Object.entries(state.featureMap).forEach(([feature, definition]) => {
    const abilityMap: Record<string, string> = {};
    (definition.abilities || []).forEach((ability) => {
      const suffix = ability.includes('.')
        ? ability.slice(ability.indexOf('.') + 1)
        : ability;
      abilityMap[suffix] = ability;
    });
    if (Object.keys(abilityMap).length > 0) {
      lookup[feature] = abilityMap;
    }
  });

  state.abilityList.forEach((ability) => {
    const [feature, ...rest] = ability.split('.');
    if (!feature || rest.length === 0) {
      return;
    }
    const suffix = rest.join('.');
    if (!lookup[feature]) {
      lookup[feature] = {};
    }
    if (!lookup[feature][suffix]) {
      lookup[feature][suffix] = ability;
    }
  });

  state.abilityLookup = lookup;
}

function notifyListeners(): void {
  if (listeners.length === 0) {
    return;
  }
  const pending = listeners.splice(0, listeners.length);
  pending.forEach((listener) => listener());
}

export function loadPermissions(force = false): Promise<void> {
  if (state.loaded && !force) {
    return Promise.resolve();
  }

  if (loadPromise && !force) {
    return loadPromise;
  }

  if (force) {
    loadPromise = null;
  }

  if (!loadPromise) {
    loadPromise = Promise.allSettled([
      api.get<FeatureMap>('/lookups/feature-map'),
      api.get<string[]>('/lookups/abilities'),
    ])
      .then(([featureResult, abilityResult]) => {
        const featureLoaded = featureResult.status === 'fulfilled';
        const abilityLoaded = abilityResult.status === 'fulfilled';

        if (featureLoaded) {
          state.featureMap = featureResult.value.data ?? {};
        } else if (force) {
          state.featureMap = {};
        }

        if (abilityLoaded) {
          state.abilityList = abilityResult.value.data ?? [];
        } else if (force) {
          state.abilityList = [];
        }

        buildAbilityLookup();

        state.loaded = featureLoaded && abilityLoaded;
        if (state.loaded) {
          notifyListeners();
        }
      })
      .finally(() => {
        loadPromise = null;
      });
  }

  return loadPromise;
}

export function onPermissionsLoaded(callback: () => void): void {
  if (state.loaded) {
    callback();
    return;
  }
  listeners.push(callback);
}

export function abilityFor(feature: string, ability: string): string | undefined {
  if (!feature || !ability) {
    return undefined;
  }

  const abilityName = state.abilityLookup[feature]?.[ability];
  if (abilityName) {
    return abilityName;
  }

  const fallback = `${feature}.${ability}`;
  if (state.abilityList.includes(fallback)) {
    return fallback;
  }

  return undefined;
}

export function featureLabel(feature: string, fallback?: string): string {
  return state.featureMap[feature]?.label ?? fallback ?? feature;
}

export function featureAbilities(feature: string): string[] {
  return state.featureMap[feature]?.abilities ?? [];
}

export function featureRoles(feature: string): string[] {
  return state.featureMap[feature]?.roles ?? [];
}

export const permissionsState = readonly(state);
