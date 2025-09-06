# Dashcode UI and Board

This project uses Dashcode's component library and Kanban patterns.  Follow these guidelines to stay consistent.

## Importing Dashcode components

Use the `@dc` alias for all Dashcode imports.  Components map one‑to‑one with the original Dashcode source.

```ts
import Button from '@dc/components/Button';
import Card from '@dc/components/Card';
import Badge from '@dc/components/Badge';
import InputGroup from '@dc/components/InputGroup';
```

## Style tokens and common classes

- Containers and cards: `rounded-2xl` and `shadow-base`.
- Column layout spacing: `space-x-6` between columns and fixed width `w-[320px]`.
- Badges: soft colors with `pill` class for rounded pills.
- Buttons: reuse Dashcode utility classes such as `btn-primary`, `btn-outline`, and `btn-light`.

## Kanban patterns

Base the board on Dashcode's Kanban (`@dc/views/app/kanban/column/index.vue` and `Task.vue`).

- Columns use `<Card class="rounded-2xl shadow-base">` with a header bar inside.
- Drag and drop uses `vuedraggable` exactly as in Dashcode:

  ```vue
  <draggable
    v-model="col.tasks"
    group="tasks"
    item-key="id"
    @start="onDragStart"
    @end="(e) => onDrop(e, col)"
  >
  ```
- Each column container has width `w-[320px]`.
- Headers display the column status name.

## Abilities and endpoints

Ability string | Purpose
--- | ---
`tasks.board.view` | View the task board (mapped under the `tasks` feature).

Endpoint | Method | Abilities
--- | --- | ---
`/api/task-board` | `GET` | `tasks.view` or `tasks.manage`
`/api/task-board/column` | `GET` | `tasks.view` or `tasks.manage`
`/api/task-board/move` | `PATCH` | `tasks.update`

## Filters and chips

Filters map to backend query parameters:

Filter field | Query parameter
--- | ---
`assigneeId` | `assignee_id`
`priority` | `priority`
`sla` | `sla`
`q` | `q`
`typeIds[]` | `type_ids`
`hasPhotos` | `has_photos=1`
`mine` | `mine=1`
`dueToday` | `due_today=1`
`breachedOnly` | `breached_only=1`

Quick filter chips correspond to `mine`, `dueToday`, and `breachedOnly` and render using soft `Badge` components.
