#!/usr/bin/env node
import fs from 'fs';
import path from 'path';

const rootDir = path.resolve(process.cwd(), 'frontend', 'src');

function walk(dir) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      walk(fullPath);
    } else if (entry.isFile() && fullPath.endsWith('.vue')) {
      processFile(fullPath);
    }
  }
}

function processFile(file) {
  const content = fs.readFileSync(file, 'utf8');
  const templateRegex = /(<template[\s\S]*?>)([\s\S]*?)(<\/template>)/m;
  const match = content.match(templateRegex);
  if (!match) return;
  const templateContent = match[2];
  const replaced = templateContent.replace(/\bthis\.(?!\$slots|\$scopedSlots)/g, '');
  if (replaced !== templateContent) {
    const newContent = match[1] + replaced + match[3];
    fs.writeFileSync(file, content.replace(templateRegex, newContent));
    console.log(`Updated ${file}`);
  }
}

walk(rootDir);
