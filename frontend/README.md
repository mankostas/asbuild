# Frontend

## Tenant Switcher & Scope filters

The UI includes a tenant switcher for users with access to multiple tenants. Selecting a tenant updates a frontend tenant store. Every API request reads the active tenant id from this store and sends it using the `X-Tenant-ID` header. After switching tenants, reload any data stores so the UI reflects the new scope.

Many admin views offer scope filters that toggle between tenant-specific, global, or all records. Use these filters to view or manage roles, types, and statuses for the active tenant or across the system. Authorization remains enforced by the backend, so any frontend super-admin hints are purely for convenience.
