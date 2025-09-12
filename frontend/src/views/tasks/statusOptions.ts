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

  let flow: any = type?.status_flow_json || [];
  if (typeof flow === 'string') {
    try {
      flow = JSON.parse(flow);
    } catch {
      flow = [];
    }
  }
  const edges: [string, string][] = Array.isArray(flow)
    ? flow.flatMap((e: any) => {
        if (Array.isArray(e) && e.length === 2) {
          const from = typeof e[0] === 'object' ? e[0].slug : e[0];
          const to = typeof e[1] === 'object' ? e[1].slug : e[1];
          return typeof from === 'string' && typeof to === 'string'
            ? [[from, to]]
            : [];
        }
        if (e && typeof e === 'object') {
          const from = typeof e.from === 'object' ? e.from.slug : e.from;
          const to = typeof e.to === 'object' ? e.to.slug : e.to;
          return typeof from === 'string' && typeof to === 'string'
            ? [[from, to]]
            : [];
        }
        return [];
      })
    : Object.entries(flow).flatMap(([from, tos]: any) =>
        (Array.isArray(tos) ? tos : [tos]).map((to: any) => [
          from,
          typeof to === 'object' ? to.slug : to,
        ] as [string, string]),
      );

  const graph: Record<string, string[]> = {};
  edges.forEach(([from, to]) => {
    if (!graph[from]) graph[from] = [];
    graph[from].push(to);
  });

  if (edges.length === 0) {
    return opts;
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
