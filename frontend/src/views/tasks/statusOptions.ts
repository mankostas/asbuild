export interface StatusOption {
  value: string;
  label: string;
}

interface StatusMeta {
  slug: string;
}

export interface TaskTypeLike {
  statuses?: StatusMeta[] | Record<string, StatusMeta[]> | Record<string, any>;
  status_flow_json?: [string, string][] | Record<string, string[]> | null;
}

export function computeStatusOptions(
  type: TaskTypeLike | null | undefined,
  statusBySlug: Record<string, { name?: string }>,
  isEdit: boolean,
  current?: string | null,
): StatusOption[] {
  const raw = type?.statuses;
  const typeStatuses = Array.isArray(raw)
    ? raw
    : Object.keys(raw || {}).map((slug) => ({ slug }));

  let opts: StatusOption[] = typeStatuses.map((s: any) => ({
    value: s.slug,
    label: statusBySlug[s.slug]?.name || s.slug,
  }));

  const flow = type?.status_flow_json || [];
  let graph: Record<string, string[]> = {};
  if (Array.isArray(flow)) {
    flow.forEach((e) => {
      if (Array.isArray(e) && e.length === 2) {
        const [from, to] = e as [string, string];
        if (!graph[from]) graph[from] = [];
        graph[from].push(to);
      }
    });
  } else if (flow && typeof flow === 'object') {
    graph = flow as Record<string, string[]>;
  }

  if (isEdit && current) {
    const allowed = [current, ...(graph[current] || [])];
    opts = opts.filter((o) => allowed.includes(o.value));
  } else {
    const incoming = new Set<string>();
    Object.values(graph).forEach((targets) =>
      targets.forEach((t) => incoming.add(t)),
    );
    const initial =
      typeStatuses.find((s: any) => !incoming.has(s.slug))?.slug ||
      typeStatuses[0]?.slug;
    if (initial) {
      const allowed = [initial, ...(graph[initial] || [])];
      const order = new Map(allowed.map((v, i) => [v, i]));
      opts = opts
        .filter((o) => order.has(o.value))
        .sort((a, b) => (order.get(a.value)! - order.get(b.value)!));
    }
  }

  return opts;
}
