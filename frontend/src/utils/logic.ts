export interface LogicRule {
  if: { field: string; eq: any };
  then: Array<{ show?: string; require?: string }>;
}

export function evaluateLogic(schema: any, data: Record<string, any>) {
  const rules: LogicRule[] = schema?.logic || [];
  const visible = new Set<string>();
  const required = new Set<string>();
  const showTargets = new Set<string>();

  for (const rule of rules) {
    for (const action of rule.then || []) {
      if (action.show) {
        showTargets.add(action.show);
      }
    }
    const condField = rule.if?.field;
    const eq = rule.if?.eq;
    if (condField && data[condField] === eq) {
      for (const action of rule.then || []) {
        if (action.show) {
          visible.add(action.show);
        }
        if (action.require) {
          required.add(action.require);
        }
      }
    }
  }

  return { visible, required, showTargets };
}
