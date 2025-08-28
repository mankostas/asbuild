# Backend

## Tenants, Roles, Teams & Assignees

This demo application is multi-tenant. Requests include the tenant context via the `X-Tenant-ID` header. Roles with a `null` `tenant_id` are global and apply across all tenants. Tenant roles belong to a specific tenant and define an array of ability strings. Authorization—including super-admin detection—is enforced on the backend; any frontend flags are for UX only. The `super_admin` role uses the wildcard `"*"` ability to access everything.

Teams group employees within a tenant. Users are attached to teams through the `team_employee` pivot table and gain role abilities via `role_user` records. When creating resources that support assignment, include an `assignee` field in the payload. The backend maps `{ kind: 'team' | 'employee', id: number }` to `assignee_type` and `assignee_id` fields.

Run the tenant bootstrap seeder to populate a sample tenant with roles, a team, users, and default appointment types and statuses:

```bash
php artisan migrate:fresh --seed --seeder=TenantBootstrapSeeder
```

## Development

Use the bundled composer script to run the backend with detailed request logs:

```bash
composer run dev
```

This runs `php artisan serve --log`, which prints each request instead of the
default dot output so you can see complete log lines during development.
