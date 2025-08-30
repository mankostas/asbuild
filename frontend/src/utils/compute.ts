export function getField(path: string, data: Record<string, any>): number {
  return path.split('.').reduce<any>((acc, key) => {
    if (acc && typeof acc === 'object' && key in acc) {
      return acc[key];
    }
    return 0;
  }, data) as number;
}

export function evaluate(expr: string, data: Record<string, any>): number {
  const tokens = expr.match(/[A-Za-z_][A-Za-z0-9_.]*|\d+(?:\.\d+)?|[()+\-*/]/g);
  if (!tokens) return 0;
  const output: number[] = [];
  const ops: string[] = [];
  const prec: Record<string, number> = { '+': 1, '-': 1, '*': 2, '/': 2 };

  const apply = () => {
    const op = ops.pop();
    if (!op) return;
    const b = output.pop() ?? 0;
    const a = output.pop() ?? 0;
    switch (op) {
      case '+':
        output.push(a + b);
        break;
      case '-':
        output.push(a - b);
        break;
      case '*':
        output.push(a * b);
        break;
      case '/':
        output.push(b === 0 ? 0 : a / b);
        break;
    }
  };

  for (const token of tokens) {
    if (/^\d/.test(token)) {
      output.push(parseFloat(token));
    } else if (/^[A-Za-z_]/.test(token)) {
      output.push(Number(getField(token, data)) || 0);
    } else if (token === '(') {
      ops.push(token);
    } else if (token === ')') {
      while (ops.length && ops[ops.length - 1] !== '(') {
        apply();
      }
      ops.pop();
    } else if (token in prec) {
      while (ops.length && ops[ops.length - 1] in prec && prec[ops[ops.length - 1]] >= prec[token]) {
        apply();
      }
      ops.push(token);
    }
  }

  while (ops.length) apply();
  return output.pop() ?? 0;
}
