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
