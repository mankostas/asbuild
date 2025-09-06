// listPrefs.ts
export type ListPrefs = {
  filters: {
    status: string;
    type: string;
    assignee: { id: number } | null;
    priority: string;
    dueStart: string;
    dueEnd: string;
    hasPhotos: boolean;
    mine: boolean;
  };
  sort: { field: string; type: 'asc' | 'desc' } | null;
  pageSize: number;
};

const KEY = (userId: string | number) => `asbuild:tasksList:v1:${userId}`;

export function loadListPrefs(userId: string | number): ListPrefs {
  try {
    return JSON.parse(localStorage.getItem(KEY(userId)) || '{}');
  } catch {
    return {} as any;
  }
}

export function saveListPrefs(userId: string | number, prefs: ListPrefs) {
  localStorage.setItem(KEY(userId), JSON.stringify(prefs));
}
