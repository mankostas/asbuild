# Backend

## Tenants, Roles, Teams & Assignees

This demo application is multi-tenant. Requests include the tenant context via the `X-Tenant-ID` header. Roles with a `null` `tenant_id` are global and apply across all tenants. Tenant roles belong to a specific tenant and define an array of ability strings. Authorization—including super-admin detection—is enforced on the backend; any frontend flags are for UX only. The `super_admin` role uses the wildcard `"*"` ability to access everything.

Teams group employees within a tenant. Users are attached to teams through the `team_employee` pivot table and gain role abilities via `role_user` records. When creating resources that support assignment, include an `assignee` field in the payload with `{ id: number }` (or an `assigned_user_id` field directly). The backend maps this to an `assigned_user_id` column.

Run the database migrations with seeding to populate a super admin account. To include the optional demo tenant with sample data, set `ENABLE_DEMO_SEEDER=true` in your `.env` file before running:

```bash
php artisan migrate:fresh --seed
```

## Role Levels

Roles carry a numeric `level` used to scope what other roles they can manage. Lower numbers are more privileged: the `SuperAdmin` role is level 0, tenant administrators default to level 1, and additional roles can use higher numbers as needed. Users may not manage roles above their own lowest level.

## Features & Abilities

Features are enumerated in `config/features.php` and mapped to ability strings via `config/feature_map.php`. Each feature lists the abilities that gate access to parts of the system. The feature map is the single source of truth for ability codes; `config/abilities.php` is generated from it when configuration is loaded or cached. When introducing a new feature:

1. Add the ability strings to the appropriate feature entry in `config/feature_map.php`.
2. Optionally register the feature slug in `config/features.php` if it should be toggled.

Example for a `reports` feature:

```php
// config/feature_map.php
return [
    // ...
    'reports' => [
        'label' => 'Reports',
        'abilities' => [
            'reports.view',
            'reports.manage',
        ],
    ],
];
```

Once the feature map is updated, `config('abilities')` will automatically include the new codes (after the config cache is rebuilt, if applicable).

Remember to safeguard new resources. When adding a manage-able resource:

- Guard routes with the ability middleware, e.g. `Ability::class . ':reports.manage'`.
- Add any necessary policy checks.
- Expose a menu item (or similar frontend entry point) with `requiredAbilities` matching the new ability.

## Development

Use the bundled composer script to run the backend with detailed request logs:

```bash
composer run dev
```

This runs `php artisan serve --log`, which prints each request instead of the
default dot output so you can see complete log lines during development.
