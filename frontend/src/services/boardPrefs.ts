// boardPrefs.ts
export type BoardPrefs = {
  filters: {
    statusIds: string[];
    typeIds: string[];
    assigneeId: string | null;
    priority: 'low' | 'medium' | 'high' | null;
    sla: string | null;
    q: string | null;
    hasPhotos: boolean | null;
    mine: boolean;
    dueToday: boolean;
    breachedOnly: boolean;
    dates?: { from?: string; to?: string };
  };
  sorting: { key: 'created_at' | 'due_at' | 'priority' | 'board_position'; dir: 'asc' | 'desc' };
  cardDensity: 'comfortable' | 'compact';
};
const KEY = (userId: string | number) => `asbuild:board:v1:${userId}`;
export function loadBoardPrefs(userId: string | number): BoardPrefs {
  try {
    return JSON.parse(localStorage.getItem(KEY(userId)) || '{}');
  } catch {
    return {} as any;
  }
}
export function saveBoardPrefs(userId: string | number, prefs: BoardPrefs) {
  localStorage.setItem(KEY(userId), JSON.stringify(prefs));
}
