# Backend Patterns

## Stack Real Del Repo

- Laravel 12
- PHP `^8.2` en Composer
- runtime Docker basado en `php:8.4-fpm-alpine`
- Blade
- Tailwind CSS
- Alpine.js
- ApexCharts
- Leaflet
- Spatie Laravel Permission
- PhpSpreadsheet (`phpoffice/phpspreadsheet`)

## Config Y Fuentes De Verdad Compartidas

- `config('app.timezone')` usa `APP_TIMEZONE` y se aplica a `datetime-local`, payloads de polling, badges y labels visibles.
- `config/dashboard_goals.php` es la fuente editable para metas semanales, diarias y derivaciones usadas por Ejecutivo, Supervisor y Gerencia.
- Si una regla cambia por negocio y hoy depende de esas metas, empieza por `config/dashboard_goals.php` antes de tocar matemáticas de controladores o Blade.

## Modulos Y Rutas Vivas

### Ejecutivo

- `/work`: `WorkController`, lead activo, gauges del día/semana y atajo a acuerdo aceptado.
- `/work/reminder-notifications/*`: recordatorios del ejecutivo.
- `/mi-chamba`, `/mi-chamba/base`, `/mi-chamba/{lead}`: seguimiento e ingreso de `Mi base`.
- `/mis-ventas`: seguimiento readonly de acuerdos.
- `/mis-promociones`: biblioteca de PDFs.
- `/mi-cobertura`: consulta readonly del mapa.
- `/tmo/sessions/*` y `/activity/sessions/*`: tracking operativo.

### Supervisor

- `/supervisor/acuerdos` y `pulse`: cola operativa y refresco parcial.
- `/supervisor/acuerdos/{sale}` y `/validar`: edición y validación del acuerdo.
- `/supervisor/dashboard`: tablero analítico con arrastre diario, semanal y metas.
- `/supervisor/mi-base`: consulta de leads `mi_base` del equipo y captura SISAC.
- `/supervisor/tmo` y `/supervisor/actividad-ejecutiva`: tableros de monitoreo.
- `/supervisor/promociones`: biblioteca de PDFs.
- `/supervisor/notificaciones/mesa-control/*`: popup y dropdown de cambios SISAC.
- `/supervisor/mensajes`: emisión de mensajes internos.

### Postventa, Mesa De Control Y Gerencia

- `/gestion`: `PostSaleController`.
- `/validacion`: `ValidationController`.
- `/control-activaciones`: guardado y exportación persistida.
- `/cobertura-territorial`: configuración operativa del mapa.
- `/dashboard`, `/gerencia/acuerdos`, `/gerencia/tmo`, `/gerencia/actividad-ejecutiva`, `/gerencia/mensajes`.

### Otros Roles

- `/rrhh/formularios`
- `/mkt/frases`
- `/administrador-promociones`
- `/admin/*` para usuarios, campañas, dashboard, importación, promociones PDF, leads deshabilitados y mensajes
- `/admin/users` concentra rol, campaña, supervisor y contraseña dentro del mismo módulo modal
- `/profile` y `/profile/photo/{user}`

## Acceso Y Redirects

- El middleware de rutas hace el corte grueso: `auth`, `role:*`, `bootstrap.admin`.
- El root `/` ya redirige por rol hacia el módulo operativo correcto.
- `AuthenticatedSessionController` también respeta el redirect por rol, así que cambios de aterrizaje deben revisarse en ambos lugares.
- Varios controladores vuelven a chequear ownership, campaña o estado del registro aunque la ruta ya esté protegida.

## Convenciones De Controladores

- Validación inline dentro de la acción.
- Lógica principal del negocio dentro del controlador.
- `with([...])` explícito para relaciones necesarias.
- `paginate(10)->withQueryString()` en listados.
- Payloads chicos para JSON y para Blade.
- Helpers privados pequeños dentro del mismo controlador antes que nuevas clases.
- Cuando una pantalla tiene polling, el patrón preferido es:
  - `index()` devuelve la página completa
  - `pulse()` devuelve JSON y, si hace falta, HTML renderizado desde partial
  - el query base se comparte en un método privado

Excepciones reales que conviene preservar:

- `ProfileController` usa `ProfileUpdateRequest` y `Storage::disk('public')`.
- `AppServiceProvider` compone `layouts.app` con recordatorios del ejecutivo y notificaciones de Supervisor; no asumas que todo ese flujo vive solo en controladores de página.

## Patrones Backend Por Modulo

### `WorkController`

- Mantiene un solo lead operativo a la vez y puede reabrir un lead concreto con `focused_lead`.
- El atajo `submit_intent = agreement_shortcut` guarda la negociación y vuelve al mismo lead con `open_agreement_modal=1`.
- `specific_status` permitido hoy:
  - `contactado`: `reprogramado`, `negociacion`, `no_desea`
  - `no_contactado`: `no_contesta`, `telefono_apagado`, `no_existe`
- `interesado` y `si_verbal` ya no son válidos.
- El bloque comercial solo es obligatorio para `negociacion`.
- `no_contesta` y `telefono_apagado`:
  - incrementan `no_contact_attempts`
  - dejan `delivery_status = gestionado`
  - programan `released_at` a 10 minutos
  - al umbral 3 desasignan y deshabilitan el lead
- Al guardar una gestión, se limpian recordatorios existentes del lead.

### `MyWorkController`

- `index()` lista solo `reprogramado` y `negociacion`.
- `base()` y `storeBase()` operan sobre `source = mi_base`.
- Ediciones idénticas no crean una interacción nueva.
- `acceptAgreement()`:
  - exige una captura comercial reciente
  - crea `interaction_type = acuerdo_aceptado`
  - replica `interaction_offers`
  - actualiza snapshot del lead
  - crea o actualiza `sales`
  - cierra sesiones activas de TMO
  - limpia recordatorios del ejecutivo
- Acuerdos solo fijos usan `fixed_agreement_supports` y limpian los campos de agenda/operación móvil.
- Adjuntos del acuerdo se guardan en `public/agreement-attachments` y se persisten en `sales.attachment_paths`.
- Como los archivos se mueven antes del commit, cualquier excepción posterior debe limpiar manualmente los adjuntos nuevos.

### `SupervisorAgreementController`

- `update()` conserva `kept_attachment_paths`, agrega `new_agreement_attachments` y registra trazabilidad en `sale_supervisor_histories`.
- Los adjuntos removidos se borran después del commit; los adjuntos recién subidos se limpian si la transacción falla.
- `approval_code` solo es obligatorio cuando `service_channel = centralizado` y el acuerdo no es `fijo`.

### `AdminUserController`

- `index()` arma campañas, roles, supervisores y el mapa actual `supervisor_executive`.
- `store()` y `update()` validan `password` con mínimo 8 caracteres.
- Solo `Supervisor` y `Ejecutivo` conservan `campaign_id`; solo `Ejecutivo` puede conservar `supervisor_user_id`.
- La sincronización de rol, campaña y supervisor vive en el mismo controlador; no la saques a otra capa sin necesidad.

### Recordatorios

- `ReminderNotificationController` y `AppServiceProvider` comparten la misma regla: solo cuentan interacciones `a_negociar` o `edicion_mi_chamba` con `status_specific = reprogramado` y `next_contact_at`.
- Los payloads vuelven a validar que el lead siga en `reprogramado`.
- El modelo soporta `reminder_stage`; cuando ese campo existe, la clave de unicidad y `storage_key` deben contemplarlo.
- El layout muestra badge, dropdown, programados próximos y toasts recientes; si tocas el flujo, revisa también `layouts.app`.

### Dashboards De Supervisor Y Gerencia

- `goalMetricCards` son métricas por periodo sobre interacciones y ventas mapeadas.
- `gestion_total` y `contactado` vienen del snapshot actual del lead, no del mismo query de metas.
- `SupervisorDashboardController` además arma:
  - `carryover`
  - `weekly_carryover`
  - `weekly_month_breakdown`
  - `goal_completion`
  - `month_weeks`
- El tablero de Supervisor filtra por rango de `updated_at` y usa el scope de ejecutivos mapeados con `supervisor_executive`.

### Supervisor, Postventa Y Mesa De Control

- `SupervisorAgreementController` guarda trazabilidad en `sale_supervisor_histories`.
- La validación final exige `approval_code` solo para acuerdos `centralizado` que no son `fijo`.
- Después de validar:
  - `management_status` pasa a `pendiente_validacion`
  - `sisac_status` pasa a `en_evaluacion`
  - se inicializan `post_sale_updates` y `validation_updates`
- `ValidationController` emite `SupervisorStatusNotification` para que Supervisor vea el cambio de SISAC en layout y en la cola de acuerdos.
- Si `sisac_status = entregado` en `movil` o `movil_fijo`, la validación exige detalle SIM por línea.

### Otros Modulos Reales

- `InternalMessageController`, `HrSurveyController` y `MarketingPhraseController` mantienen todo el flujo de emisor + recipients + polling dentro del mismo controlador.
- `ActivationControlController` persiste filas en `activation_control_records` y exporta desde datos guardados, no desde drafts del navegador.
- `TerritorialCoverageController` sirve payload de mapa y persiste configuración provincial con historial.
- `PromotionNameController` mantiene el catálogo reutilizable de nombres de promoción.
- `AdminPromoDocumentController` usa `Schema::hasTable('promo_documents')` como guardia operativa.

## Tablas Y Modelos Hotspot

- `leads`: snapshot actual, ownership, fuente, estado, `released_at`, `disabled_at`, `no_contact_attempts`.
- `interactions`: historial de gestión, `status_general`, `status_specific`, `next_contact_at`, `interaction_type`.
- `interaction_offers`: detalle comercial normalizado.
- `sales`: snapshot del acuerdo aceptado y estados de revisión.
- `sale_supervisor_histories`, `post_sale_updates`, `validation_updates`: trazabilidad de revisiones.
- `reminder_notifications`: recordatorios staged del ejecutivo.
- `lead_work_sessions`: TMO por lead.
- `supervisor_status_notifications`: feedback de Mesa de Control a Supervisor.
- `promotion_names`, `promo_documents`: catálogos comerciales distintos.
- `activation_control_records`: helper operativo de exportación.
- `territorial_province_settings`, `territorial_province_setting_histories`: cobertura territorial persistida.

## Hotspots De Validacion

Si el cambio toca backend sensible, revisar como mínimo:

- `tests/Feature/WorkReminderTest.php`
- `tests/Feature/MyWorkStatusTransitionTest.php`
- `tests/Feature/WorkAgreementShortcutTest.php`
- `tests/Feature/SupervisorAgreementValidationTest.php`
- `tests/Feature/SupervisorDashboardTest.php`
- `tests/Feature/GerenciaDashboardTest.php`

Practical rule:

- si una referencia vieja contradice esas pruebas, la fuente de verdad es el repo actual
- si cambias estados o metas sin revisar esas pruebas, es fácil documentar algo incorrecto
- hoy no hay feature test dedicado para `admin/users`; si tocas contraseñas o asignaciones, considera abrir esa cobertura
