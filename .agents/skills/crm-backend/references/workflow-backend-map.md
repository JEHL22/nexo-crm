# Workflow Backend Map

Usa este archivo cuando el cambio toque estados, recordatorios, handoff comercial, dashboards o revisiones por rol.

## Roles Implementados

- Administrador
- Gerencia
- Supervisor
- Ejecutivo
- Postventa
- Mesa de Control
- RRHH
- MKT
- administrador de promociones

## Aterrizajes Por Rol

- Ejecutivo -> `/work`
- Supervisor -> `/supervisor/acuerdos`
- Postventa -> `/gestion`
- Mesa de Control -> `/validacion`
- Gerencia -> `/dashboard`
- RRHH -> `/rrhh/formularios`
- MKT -> `/mkt/frases`
- administrador de promociones -> `/administrador-promociones`
- Administrador -> `/admin`

Revisa tanto `routes/web.php` como `AuthenticatedSessionController` si el cambio altera la ruta inicial.

## Catalogo De Estados Vigente

### `leads`

- `delivery_status`: `disponible`, `asignado`, `gestionado`
- `status_general`: `sin_contacto`, `contactado`, `no_contactado`
- `status_specific` de escritura vigente:
  - `sin_gestion`
  - `reprogramado`
  - `negociacion`
  - `no_desea`
  - `no_contesta`
  - `telefono_apagado`
  - `no_existe`
  - `acuerdo_aceptado`
- `status_final`:
  - `sin_gestion`
  - `en_seguimiento`
  - `cerrado_sin_venta`
  - `acuerdo_aceptado`

Estados retirados:

- `interesado` -> remapeado a `negociacion`
- `si_verbal` -> remapeado a `negociacion`

No se aceptan ya en validación de `A negociar` ni `Mi chamba`.

### `sales`

- `supervisor_validation_status`: `pendiente`, `validado`
- `management_status` write path:
  - `pendiente_supervision`
  - `pendiente_validacion`
  - `aprobado`
  - `rechazado`
  - `observado`
- `sisac_status` write path:
  - `pendiente_supervision`
  - `en_evaluacion`
  - `activo`
  - `rechazado`
  - `entregado`

Normalizaciones de lectura todavía activas:

- `pendiente_validacion`, `observado` -> `en_evaluacion`
- `aprobado` -> `activo`

## A Negociar

1. El ejecutivo toma o reutiliza un lead de su campaña.
2. Si no tiene uno activo, el sistema asigna el siguiente disponible con `lockForUpdate()`.
3. Se registra una `interaction` tipo `a_negociar`.
4. Solo `negociacion` obliga a capturar oferta comercial.
5. La oferta vive en `interaction_offers`, no en columnas libres del lead.
6. El snapshot del lead se actualiza al final.

Opciones visibles hoy:

- `contactado`: `reprogramado`, `negociacion`, `no_desea`
- `no_contactado`: `no_contesta`, `telefono_apagado`, `no_existe`

Transiciones importantes:

- `reprogramado`, `negociacion` -> `status_final = en_seguimiento`, `delivery_status = gestionado`
- `no_desea`, `no_existe` -> `status_final = cerrado_sin_venta`, `delivery_status = gestionado`
- `no_contesta`, `telefono_apagado`:
  - incrementan `no_contact_attempts`
  - dejan el lead en `gestionado`
  - programan liberación a 10 minutos
  - al umbral 3 lo desasignan y deshabilitan

Guardrails actuales:

- `submit_intent = agreement_shortcut` solo es válido desde `negociacion`
- el shortcut vuelve al mismo lead con `focused_lead` y `open_agreement_modal`
- promociones móviles usan `promotion_names`

## Mi Chamba Y Mi Base

- `my-work.index` lista solo `reprogramado` y `negociacion`
- `my-work.base` opera sobre `source = mi_base`
- la edición permite `reprogramado`, `negociacion` y `no_desea`
- `acuerdo_aceptado` ya no es un simple cambio de estado en el submit de edición
- un submit idéntico al último payload normalizado se trata como no-op
- las interacciones reprogramadas con `next_contact_at` alimentan recordatorios

## Handoff De Acuerdo Aceptado

`acuerdo_aceptado` es el punto formal de paso desde Ejecutivo hacia revisión.

La creación actual:

- genera una `interaction` tipo `acuerdo_aceptado`
- copia ofertas desde la captura comercial reciente
- actualiza `lead.status_specific` y `lead.status_final` a `acuerdo_aceptado`
- crea o actualiza `sales`
- deja la venta con:
  - `status = acuerdo_aceptado`
  - `supervisor_validation_status = pendiente`
  - `management_status = pendiente_supervision`
  - `sisac_status = pendiente_supervision`

Reglas actuales:

- si el acuerdo es solo fijo, la rama válida usa `fixed_agreement_supports`
- si hay portabilidad, se exigen números por línea
- si no existe captura comercial reciente, no se puede cerrar acuerdo
- los adjuntos del acuerdo viven en `sales.attachment_paths` y arrancan desde el cierre comercial del Ejecutivo

## Supervisor, Postventa Y Mesa De Control

### Supervisor

- lista acuerdos pendientes en `supervisor.agreements.*`
- puede editar datos antes de validar
- puede conservar adjuntos existentes o subir nuevos antes de validar
- cada cambio relevante genera `sale_supervisor_histories`
- la validación final exige `approval_code` solo si el acuerdo queda en `centralizado` y no es `fijo`

Al validar:

- `supervisor_validation_status` pasa a `validado`
- `management_status` pasa a `pendiente_validacion`
- `sisac_status` pasa a `en_evaluacion`
- se siembran filas base en `post_sale_updates` y `validation_updates`

### Postventa

- trabaja sobre `management_status`
- registra historial en `post_sale_updates`

### Mesa De Control

- trabaja sobre `sisac_status`
- registra historial en `validation_updates`
- si llega a `entregado` para `movil` o `movil_fijo`, exige detalle SIM por línea
- emite `SupervisorStatusNotification`

## Recordatorios

- solo aplican a `interaction_type` `a_negociar` o `edicion_mi_chamba`
- solo cuentan si `status_specific = reprogramado`
- dependen de `next_contact_at`
- el backend vuelve a verificar que el lead siga en `reprogramado`
- el modelo soporta `reminder_stage`
- el layout y el controller comparten la limpieza de recordatorios obsoletos

Practical rule:

- si cambias `reprogramado`, `next_contact_at` o `reminder_stage`, revisa `WorkController`, `MyWorkController`, `ReminderNotificationController`, `AppServiceProvider` y `layouts.app`

## Dashboards Y Metas

### Ejecutivo

- `WorkController` arma gauges diarios y semanales
- las metas salen de `config/dashboard_goals.php`

### Supervisor

- el tablero usa ejecutivos mapeados por `supervisor_executive`
- mezcla dos verdades a propósito:
  - metas por periodo en `goalMetricCards`
  - snapshot actual en `gestion_total` y `contactado`
- además calcula:
  - arrastre diario
  - arrastre semanal
  - breakdown semanal del mes
  - flags de cumplimiento

### Gerencia

- conserva la misma separación entre metas por periodo y snapshot actual
- se apoya también en `supervisor_executive`

Practical rule:

- no unifiques en un solo `total` lo que hoy está separado entre snapshot y periodo

## Reglas De Decisión Al Codificar

- Si cambia un estado, actualiza validación, queries, labels, historial y pruebas juntos.
- Si cambia un reminder, revisa también dropdowns, payloads, toasts y apertura del lead.
- Si cambia un dashboard, revisa controladores, Blade y pruebas feature.
- Si cambia el handoff comercial, revisa `WorkController`, `MyWorkController`, `SupervisorAgreementController`, `MySalesController`, `PostSaleController` y `ValidationController`.
- Si cambia `approval_code`, revisa todos los módulos readonly y de revisión que lo muestran.
- Si cambian adjuntos del acuerdo, revisa `MyWorkController`, `SupervisorAgreementController`, `my-sales`, `post-sale`, `validation` y las vistas de Gerencia.
- Si cambia una meta, empieza por `config/dashboard_goals.php`.

## Hotspots De Validacion

- `tests/Feature/WorkReminderTest.php`
- `tests/Feature/MyWorkStatusTransitionTest.php`
- `tests/Feature/WorkAgreementShortcutTest.php`
- `tests/Feature/SupervisorDashboardTest.php`
- `tests/Feature/GerenciaDashboardTest.php`
- `database/migrations/2026_03_28_120000_remap_removed_contact_statuses.php` cuando el cambio toca estados retirados
