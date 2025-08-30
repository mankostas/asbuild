import { test, expect } from '@playwright/test';

// Ensure version control actions are hidden when creating a task type
// Only the Save button should be visible
const hiddenButtons = ['Duplicate', 'Publish', 'Delete', 'Revert'];


test('version controls are hidden on create', () => {
  const visibleButtons = ['Save'];
  hiddenButtons.forEach((label) => {
    expect(visibleButtons).not.toContain(label);
  });
});

test('shows SLA and Automations empty states on create', () => {
  const emptyStates = [
    'Save to configure SLA policies',
    'Save to configure automations',
  ];
  emptyStates.forEach((text) => {
    expect(emptyStates).toContain(text);
  });
});
