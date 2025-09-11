export interface TransitionTask {
  previous_status_slug?: string | null;
  type?: {
    statuses?: Record<string, string[]>;
    status_flow_json?: [string, string][] | Record<string, string[]>;
  };
}

export interface TransitionColumn {
  status: { slug: string };
}

export function computeAllowedTransitions(
  task: TransitionTask,
  from: string,
  canManage: boolean,
  columns: TransitionColumn[],
): string[] {
  if (canManage) {
    return columns.map((c) => c.status.slug);
  }
  const direct = task.type?.statuses?.[from];
  const flow = Array.isArray(task.type?.status_flow_json)
    ? task.type.status_flow_json
    : Object.entries(task.type?.status_flow_json ?? {}).flatMap(
        ([f, tos]) =>
          (Array.isArray(tos) ? tos : [tos]).map((to: string) => [f, to] as [string, string]),
      );
  let allowed = direct?.length
    ? direct
    : flow.filter(([f]) => f === from).map(([, to]) => to);
  if (task.previous_status_slug && task.previous_status_slug !== from) {
    allowed = [...allowed, task.previous_status_slug];
  }
  return Array.from(new Set(allowed));
}
