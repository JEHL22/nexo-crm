# Nexo CRM — Contexto del proyecto

CRM de call center para campaña B2B de telecomunicaciones Claro.
Pipeline: lead → contacto → negociación → acuerdo → validación supervisor → Mesa de Control → entrega/activación.

## Stack
- Laravel 12 / PHP 8.4, MariaDB 11.4, Redis (cache)
- Blade + Alpine.js + Tailwind CSS, Vite
- Spatie Laravel Permission (9 roles)
- Docker: nginx :8080, app (php-fpm), db :3307, redis. Node solo bajo demanda (profile `assets`)

## Comandos (php NO está en el host — todo via Docker)
```bash
docker compose up -d                                  # levantar
docker compose exec app php artisan test              # tests (sqlite in-memory, seguros)
docker compose exec app php artisan migrate           # migraciones
docker compose run --rm node npm run build            # compilar frontend
docker compose exec app ./vendor/bin/pint --dirty     # estilo en archivos modificados
```

## Roles y home routes
Ejecutivo→`work.show` · Supervisor→`supervisor.agreements.index` · Mesa de Control→`validation.index` · Gerencia→`dashboard` · Postventa→`post-sale.index` · RRHH→`rrhh.surveys.index` · MKT→`mkt.phrases.index` · administrador de promociones→`promotion-admin.index` · Administrador→`admin.home`

## Convenciones del proyecto (respetarlas)
- **Validación inline** con `$request->validate([])` en controllers — NO crear FormRequests nuevos
- **Autorización**: `abort_if`/`abort_unless` en controllers o middleware `role:` en rutas
- **Sin API REST**: form POST + redirect; endpoints `*/pulse` devuelven JSON para polling (todos con `throttle:120,1`)
- **Multi-tabla**: `DB::transaction(fn () => ...)`
- **Constantes de estado**: viven en los modelos — `Sale::STATUS_ACCEPTED`, `Sale::MANAGEMENT_STATUSES`, `Sale::SISAC_STATUSES`, `Sale::SUPERVISOR_VALIDATION_*`, `Lead::SOURCE_*`, `Lead::SPECIFIC_*`, `Lead::FINAL_*`. NO escribir strings de estado sueltos en código nuevo. OJO: `'acuerdo_aceptado'` existe en 3 dominios distintos (Sale.status, Lead/Interaction.status_specific, Lead.status_final) — usar la constante del dominio correcto
- **Lógica compartida**: clases estáticas en `app/Support/` (`PromotionRows`, `AgreementProducts`, `AgreementAttachmentStorage`)
- **Métodos cortos**: >40 líneas → extraer privados con nombre descriptivo

## Reglas de seguridad establecidas
- Adjuntos de acuerdos (contratos de clientes) viven en `storage/app/private/agreement-attachments/` — NUNCA en `public/`. Se sirven por `agreements.attachments.show` con autorización. Usar siempre `AgreementAttachmentStorage`
- `sales.interaction_id` es NOT NULL a propósito: toda venta nace de una interacción de acuerdo
- PostSale/Validation `update()` exigen el mismo scope que su index (venta aceptada + validada por supervisor)
- Tests de archivos usan `Storage::fake('local')` — JAMÁS borrar directorios reales en cleanup de tests

## Esquema — trampas conocidas
- `WorkController extends MyWorkController` — no cambiar firmas/visibilidad de métodos de MyWorkController sin revisar el hijo
- Crear una Sale en tests requiere: Campaign → Lead → Interaction → Sale (ver helper en `tests/Feature/AgreementAttachmentDownloadTest.php`)
- Solo existe `User::factory()` — Lead/Sale/Interaction se crean con `::create`
- Migraciones de índices/columnas: siempre idempotentes (`Schema::hasColumn`/`hasIndex`) porque producción puede divergir

## Escalabilidad (regla de diseño)
Módulos con listas que crecen (leads, ventas, interacciones): page load solo con conteos/agregados; detalle on-demand por AJAX con caché en memoria del cliente. Batching para N+1 (1-2 queries globales + groupBy en PHP).

## CI
GitHub Actions (`.github/workflows/tests.yml`) corre la suite en cada push/PR. Los tests usan sqlite in-memory + build de Vite.
