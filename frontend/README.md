# Frontend

## Tenant Switcher & Scope filters

The UI includes a tenant switcher for users with access to multiple tenants. Selecting a tenant updates a frontend tenant store. Every API request reads the active tenant id from this store and sends it using the `X-Tenant-ID` header. After switching tenants, reload any data stores so the UI reflects the new scope.

Many admin views offer scope filters that toggle between tenant-specific, global, or all records. Use these filters to view or manage roles, types, and statuses for the active tenant or across the system. Authorization remains enforced by the backend, so any frontend super-admin hints are purely for convenience.

## Authorization & ability checks

The frontend keeps a single source of truth for route and navigation permissions in
[`src/constants/menu.ts`](src/constants/menu.ts). Each entry lists the
abilities a user must have (`requiredAbilities`) and whether the full list is needed
(`requireAllAbilities`). Routes copy this metadata into their `meta` block so the
global navigation guard can deny access consistently.

The auth store exposes a few helpers:

* `can('feature.action')` – returns `true` when the user has the ability or the
  corresponding `feature.manage` wildcard.
* `hasAny([...])` – used when any ability in the list is sufficient; it applies the
  same wildcard behaviour as `can` for convenience.
* `hasAll([...])` – used when a screen needs every listed ability. It also honours the
  `feature.manage` wildcard so a role that includes `tasks.manage` automatically
  satisfies checks for `tasks.view`, `tasks.update`, etc.

⚠️ The backend still enforces each ability individually. Granting `feature.manage`
does **not** implicitly grant `feature.view` server-side. Role designers should
assign every ability required by the APIs (for example both `notifications.view`
and `notifications.manage`) even though the UI will treat `feature.manage` as a
superset.

When adding a new route or navigation item, set the appropriate abilities and, if the
screen performs multiple privileged operations, mark `requireAllAbilities: true` so
the guard calls `hasAll`. This keeps the router, menus, and feature quick-actions in
sync and prevents partially-authorised users from landing on a page they cannot use.
