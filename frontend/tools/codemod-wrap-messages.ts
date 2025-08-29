import { parse as parseSFC } from '@vue/compiler-sfc';
import { parse as parseTemplate, NodeTypes, ElementNode, AttributeNode, DirectiveNode } from '@vue/compiler-dom';
import fg from 'fast-glob';
import fs from 'fs';
import MagicString from 'magic-string';

const MESSAGE_VAR_RE = /\b(errors|error|formErrors|validate|validation|success|message|messages)\b/;
const CLASS_RE = /error|errors|invalid|danger|red|help|hint|success|valid|green|feedback|message|alert|warning/i;

async function run() {
  const files = await fg([
    'src/views/auth/**/*.vue',
    'src/views/auth/dashcode/**/*.vue',
    'src/views/**/*.vue',
    'src/components/**/*.vue',
    'src/Layout/**/*.vue'
  ], { cwd: process.cwd(), absolute: true });

  for (const file of files) {
    const source = fs.readFileSync(file, 'utf8');
    const { descriptor } = parseSFC(source);
    if (!descriptor.template) continue;
    const template = descriptor.template;
    const ast = parseTemplate(template.content, { comments: true });
    const s = new MagicString(source);
    const templateOffset = template.loc.start.offset;

    function walk(node: any, parent?: any) {
      if (node.type === NodeTypes.INTERPOLATION) {
        const expr = node.content.content;
        if (expr.includes('$msg(')) return;
        const hasVar = MESSAGE_VAR_RE.test(expr);
        let qualifies = hasVar;
        if (!qualifies && parent && parent.type === NodeTypes.ELEMENT) {
          const el = parent as ElementNode;
          for (const prop of el.props) {
            if (prop.type === NodeTypes.ATTRIBUTE) {
              const attr = prop as AttributeNode;
              if (attr.name === 'class' && attr.value && CLASS_RE.test(attr.value.content)) {
                qualifies = true; break;
              }
              if (attr.name === 'aria-live') { qualifies = true; break; }
              if (attr.name === 'role' && attr.value && /alert/i.test(attr.value.content)) { qualifies = true; break; }
            } else if (prop.type === NodeTypes.DIRECTIVE) {
              const dir = prop as DirectiveNode;
              if (dir.name === 'class' && dir.exp && CLASS_RE.test(dir.exp.content)) {
                qualifies = true; break;
              }
            }
          }
        }
        if (qualifies) {
          const start = templateOffset + node.content.loc.start.offset;
          const end = templateOffset + node.content.loc.end.offset;
          const trimmed = expr.trim();
          s.overwrite(start, end, `$msg(${trimmed})`);
        }
      }
      if ('children' in node && Array.isArray(node.children)) {
        for (const child of node.children) walk(child, node);
      }
    }

    walk(ast as any);
    const result = s.toString();
    if (result !== source) {
      fs.writeFileSync(file, result);
    }
  }
}

run();
