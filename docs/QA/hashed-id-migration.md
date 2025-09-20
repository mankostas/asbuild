# Hashed ID Migration Checklist

The following inventory captures the API surfaces that currently rely on `PublicIdResolver` or the
`ResolvesPublicIds` request concern.  It documents which request payload fields or query
parameters are translated from public identifiers to internal primary keys, highlights the tests
that cover each endpoint, and notes the data builders they use today.  This should make it easier
to audit ID handling while migrating to hashed identifiers.

## Controllers

### `TaskController`
| Endpoint | ID-bearing inputs | Observed format in feature tests | Exercising tests & data setup |
| --- | --- | --- | --- |
| `GET /api/tasks` | Query params `type`→TaskType, `assignee`→User, `client_id`→Client (with fallback to tenant-scoped status slug)【F:backend/app/Http/Controllers/Api/TaskController.php†L33-L87】 | Tests pass raw integer primary keys for the filters today. | `TaskListFiltersTest::test_multi_filter_query_returns_expected` uses manual `Model::create` calls (no factories).【F:backend/tests/Feature/TaskListFiltersTest.php†L21-L86】 |
| `POST /api/tasks` | `TaskUpsertRequest` resolves `task_type_id`, `assigned_user_id`, nested `assignee.id`, reviewer `id` (Team/User), and `client_id`; controller also infers tenant on create.【F:backend/app/Http/Controllers/Api/TaskController.php†L93-L146】【F:backend/app/Http/Requests/TaskUpsertRequest.php†L33-L208】 | Current tests post payloads with numeric IDs (or omit the fields altogether). | `TaskCreationTest::test_new_task_has_status_and_slug` posts without IDs; other flows such as `TaskStatusFlowTest::test_update_route_allows_status_change` patch numeric IDs after manual `Task::create` setup.【F:backend/tests/Feature/TaskCreationTest.php†L17-L52】【F:backend/tests/Feature/TaskStatusFlowTest.php†L175-L213】 |
| `PATCH /api/tasks/{task}` | Same resolver-backed fields as `store`, plus optional `status` string that is split out before persistence.【F:backend/app/Http/Controllers/Api/TaskController.php†L154-L210】 | Tests exercise numeric IDs/status strings only. | `TaskStatusFlowTest` patches titles/status after creating records via helper builders rather than factories.【F:backend/tests/Feature/TaskStatusFlowTest.php†L175-L213】 |
| `POST /api/tasks/{task}/assign` | Body `assigned_user_id` resolved to User; rejects unknown identifiers.【F:backend/app/Http/Controllers/Api/TaskController.php†L212-L244】 | No direct feature test found—add coverage before changing ID handling. | N/A |
| `POST /api/tasks/{task}/status` | Body `status` (string) drives transition; no public ID resolution here but endpoint is often paired with resolver-backed requests.【F:backend/app/Http/Controllers/Api/TaskController.php†L253-L305】 | Tests post status strings only. | `TaskSubtaskTest::test_status_constraint_blocks_incomplete_subtasks` seeds subtasks via `Model::create` and posts numeric task IDs.【F:backend/tests/Feature/TaskSubtaskTest.php†L92-L109】 |

### `TaskSubtaskController`
| Endpoint | ID-bearing inputs | Observed format | Exercising tests & data setup |
| --- | --- | --- | --- |
| `POST /api/tasks/{task}/subtasks` | Optional `assigned_user_id` resolved to User before create.【F:backend/app/Http/Controllers/Api/TaskSubtaskController.php†L19-L48】 | Tests currently omit `assigned_user_id`, so only numeric task IDs are exercised. | `TaskSubtaskTest::test_crud_and_reorder_subtasks` seeds tasks/subtasks manually (no factories).【F:backend/tests/Feature/TaskSubtaskTest.php†L63-L89】 |
| `PATCH /api/tasks/{task}/subtasks/{subtask}` | Optional `assigned_user_id` resolved or nulled.【F:backend/app/Http/Controllers/Api/TaskSubtaskController.php†L51-L86】 | Tests patch numeric IDs only. | `TaskSubtaskTest::test_crud_and_reorder_subtasks` updates subtasks with numeric identifiers created via `TaskSubtask::create`.【F:backend/tests/Feature/TaskSubtaskTest.php†L63-L88】 |
| `PATCH /api/tasks/{task}/subtasks/reorder` | Array `order[*]` resolved to TaskSubtask IDs, each validated.【F:backend/app/Http/Controllers/Api/TaskSubtaskController.php†L98-L128】 | Tests post numeric IDs. | `TaskSubtaskTest::test_crud_and_reorder_subtasks` reorders using integer IDs gathered from the database.【F:backend/tests/Feature/TaskSubtaskTest.php†L75-L85】 |

### `TaskStatusController`
| Endpoint | ID-bearing inputs | Observed format | Exercising tests & data setup |
| --- | --- | --- | --- |
| `GET /api/task-statuses` | Query `tenant_id` resolved when scoping (including `scope=tenant` and super-admin overrides).【F:backend/app/Http/Controllers/Api/TaskStatusController.php†L24-L67】 | Tests pass integer tenant IDs via query string/header. | `TaskStatusTenantVisibilityTest` seeds statuses with `::create` and issues numeric tenant IDs.【F:backend/tests/Feature/TaskStatusTenantVisibilityTest.php†L18-L101】 |
| `POST /api/task-statuses` | `TaskStatusUpsertRequest` resolves optional `tenant_id` and computes prefixed slug.【F:backend/app/Http/Controllers/Api/TaskStatusController.php†L69-L85】【F:backend/app/Http/Requests/TaskStatusUpsertRequest.php†L27-L91】 | Payloads in tests use raw integers (or omit `tenant_id`). | `TaskStatusCreateAbilityTest` and `TaskStatusSlugTest` post bodies with primitive values after manual model setup.【F:backend/tests/Feature/TaskStatusCreateAbilityTest.php†L17-L68】【F:backend/tests/Feature/TaskStatusSlugTest.php†L17-L52】 |
| `PUT /api/task-statuses/{task_status}` | Same resolver-backed `tenant_id` handling as `store` via request class.【F:backend/app/Http/Controllers/Api/TaskStatusController.php†L94-L111】 | No feature test currently asserts hashed ID behavior—add before migrating. | N/A |
| `POST /api/task-statuses/{task_status}/copy` | Body `tenant_id` resolved for the copy target.【F:backend/app/Http/Controllers/Api/TaskStatusController.php†L121-L143】 | Coverage missing; only indirect unit/feature checks today. | N/A |

### `RoleController`
| Endpoint | ID-bearing inputs | Observed format | Exercising tests & data setup |
| --- | --- | --- | --- |
| `GET /api/roles` | Query `tenant_id` resolved for super-admin scope switching; non-admin paths derive tenant automatically.【F:backend/app/Http/Controllers/Api/RoleController.php†L26-L81】 | Tests call the route with numeric IDs and expect filtered results. | `RoleRoutesTest::test_crud_routes_work` and `RoleRoutesTest::test_super_admin_filters_roles_by_tenant` seed models with `Model::create` and query using integers.【F:backend/tests/Feature/RoleRoutesTest.php†L42-L129】 |
| `POST /api/roles` | `RoleUpsertRequest` resolves optional `tenant_id` and filters abilities against tenant features.【F:backend/app/Http/Controllers/Api/RoleController.php†L83-L113】【F:backend/app/Http/Requests/RoleUpsertRequest.php†L19-L82】 | Feature tests post payloads with integers (or omit the field). | `RoleRoutesTest::test_crud_routes_work` and `RolesTest::test_super_admin_can_assign_any_ability` build payloads manually (no factories).【F:backend/tests/Feature/RoleRoutesTest.php†L49-L88】【F:backend/tests/Feature/RolesTest.php†L102-L134】 |
| `PUT /api/roles/{role}` | Same resolver-backed request class plus tenant/level gating.【F:backend/app/Http/Controllers/Api/RoleController.php†L128-L168】 | Tests patch numeric IDs. | `RoleRoutesTest::test_crud_routes_work` updates roles created with `Role::create`.【F:backend/tests/Feature/RoleRoutesTest.php†L73-L85】 |
| `POST /api/roles/{role}/assign` | Body `user_id` (User) and optional `tenant_id` resolved; falls back to role or actor tenant.【F:backend/app/Http/Controllers/Api/RoleController.php†L189-L243】 | Tests post numeric IDs for user/role/tenant. | `RolesTest::test_role_assignment_persists` attaches roles after manual `User::create` setup.【F:backend/tests/Feature/RolesTest.php†L63-L100】 |

### `TaskCommentController`
| Endpoint | ID-bearing inputs | Observed format | Exercising tests & data setup |
| --- | --- | --- | --- |
| `POST /api/tasks/{task}/comments` | Arrays `files[*]`→File, `mentions[*]`→User resolved individually; rejects null/empty values.【F:backend/app/Http/Controllers/Api/TaskCommentController.php†L36-L77】【F:backend/app/Http/Controllers/Api/TaskCommentController.php†L134-L180】 | Tests send integer IDs for both attachments and mentions. | `TaskCommentAttachmentTest::test_comment_with_attachment_saves_file_relation` and `TaskCommentMentionTest::test_cross_tenant_mention_fails` create related records manually via `Model::create`.【F:backend/tests/Feature/TaskCommentAttachmentTest.php†L18-L55】【F:backend/tests/Feature/TaskCommentMentionTest.php†L53-L102】 |
| `PATCH /api/task-comments/{comment}` | Same resolver arrays when `files` or `mentions` provided.【F:backend/app/Http/Controllers/Api/TaskCommentController.php†L85-L125】 | No feature test currently covers updating with resolved IDs—add before migrating. | N/A |

## Request Classes Using `ResolvesPublicIds`
| Request class | Resolved fields | Exercising tests & data setup |
| --- | --- | --- |
| `TaskUpsertRequest` | `task_type_id`, `assigned_user_id`, nested `assignee.id`, reviewer `id` (Team/User), `client_id`; recalculates computed form fields post-resolution.【F:backend/app/Http/Requests/TaskUpsertRequest.php†L33-L208】 | Covered indirectly by task feature tests that post numeric IDs (`TaskCreationTest`, `TaskStatusFlowTest`, etc.).【F:backend/tests/Feature/TaskCreationTest.php†L17-L52】【F:backend/tests/Feature/TaskStatusFlowTest.php†L175-L213】 |
| `TaskStatusUpsertRequest` | Optional `tenant_id` (and derived slug) resolved in both `prepareForValidation` and `validated`.【F:backend/app/Http/Requests/TaskStatusUpsertRequest.php†L27-L91】 | `TaskStatusCreateAbilityTest`/`TaskStatusSlugTest` cover create flows with integer tenants; update/copy lack coverage. |
| `RoleUpsertRequest` | Optional `tenant_id` resolved before persistence; also constrains abilities against tenant features.【F:backend/app/Http/Requests/RoleUpsertRequest.php†L19-L82】 | `RoleRoutesTest` and `RolesTest` submit numeric `tenant_id` values where applicable using manual builders.【F:backend/tests/Feature/RoleRoutesTest.php†L49-L88】【F:backend/tests/Feature/RolesTest.php†L102-L134】 |
| `TeamUpsertRequest` | Optional `tenant_id` and `lead_id` resolved.【F:backend/app/Http/Requests/TeamUpsertRequest.php†L20-L88】 | No feature test currently posts to team CRUD endpoints—only membership syncing is covered. Add coverage before changing ID formats. |
| `TaskTypeRequest` | Optional `tenant_id` and `client_id` resolved; JSON strings decoded to arrays.【F:backend/app/Http/Requests/TaskTypeRequest.php†L20-L125】 | `TaskTypeBuilderTest` and related suites post numeric IDs/JSON strings after manual model seeding.【F:backend/tests/Feature/TaskTypeBuilderTest.php†L57-L203】 |
| `TypeUpsertRequest` | Optional `tenant_id` resolved; JSON payloads decoded for persistence.【F:backend/app/Http/Requests/TypeUpsertRequest.php†L19-L70】 | Task type builder/import/export tests exercise this via numeric tenant IDs when editing existing types.【F:backend/tests/Feature/TaskTypeBuilderTest.php†L171-L203】 |

### Additional Notes
- Several endpoints that rely on `PublicIdResolver` (e.g., task assignment, task-status copy, role updates with explicit `tenant_id`) currently lack direct feature coverage that submits resolver-controlled fields.  Add regression tests that post hashed IDs before flipping the default format.
- Existing tests rely on manual `Model::create` data builders rather than factories, so switching ID formats will require updating those helper records (or introducing factories) in tandem with the controller/request changes.
- When adding hashed-ID coverage, prefer asserting both numeric and hashed inputs during the transition window to confirm backwards compatibility.
