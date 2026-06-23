# Workflow Map

## Reality Check

El documento funcional es más amplio que el código. Hoy el repo cubre de forma real:

- Ejecutivo:
  - `A negociar`
  - `Mi chamba`
  - `Mi base`
  - `Mis ventas`
  - `Mis promociones`
  - `Mi cobertura`
  - recordatorios ligados a `reprogramado`
  - tracking TMO y actividad
- Supervisor:
  - acuerdos
  - dashboard del equipo
  - `Mi base del equipo`
  - TMO
  - actividad ejecutiva
  - promociones
  - mensajes
  - feedback de Mesa de Control
- Postventa:
  - gestión
- Mesa de Control:
  - validación
  - cobertura territorial
  - control de activaciones
- Gerencia:
  - dashboard comercial
  - acuerdos consolidados
  - TMO
  - actividad ejecutiva
  - mensajes internos
- Administrador:
  - dashboard, usuarios, campañas, promociones PDF, importación, leads deshabilitados, mensajes
- administrador de promociones:
  - catálogo `promotion_names`
- RRHH:
  - formularios dirigidos a Ejecutivos
- MKT:
  - frases activas

No asumir todavía módulos como `Mi billete`, `Mi equipo` o `Seguimiento` si el usuario no pide construirlos.

## Entry Routes Por Rol

- Ejecutivo -> `/work`
- Supervisor -> `/supervisor/acuerdos`
- Postventa -> `/gestion`
- Mesa de Control -> `/validacion`
- Gerencia -> `/dashboard`
- RRHH -> `/rrhh/formularios`
- MKT -> `/mkt/frases`
- administrador de promociones -> `/administrador-promociones`
- Administrador -> `/admin`

Esto ya vive tanto en `routes/web.php` como en `AuthenticatedSessionController`.

## Ownership Y Asignacion

- Ejecutivo se vincula a campañas vía `campaign_user`.
- Supervisor y Ejecutivo se vinculan vía `supervisor_executive`.
- Esa relación puede estar acotada por `campaign_id`.
- `supervisor_executive` ya influye en visibilidad de Supervisor y Gerencia.

Asignación actual del lead:

1. el usuario debe tener campaña
2. primero se reutiliza un lead ya asignado al ejecutivo
3. si no hay uno activo, se toma el siguiente `disponible`
4. la asignación usa `lockForUpdate()`

## Catalogo De Estados

### Lead

- `status_general`:
  - `sin_contacto`
  - `contactado`
  - `no_contactado`
- `status_specific` vigente:
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

- `interesado`
- `si_verbal`

Hoy están remapeados a `negociacion` y ya no deben reaparecer en validación, labels ni tests nuevos.

### Sale

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

Lecturas operativas todavía normalizan:

- `pendiente_validacion`, `observado` como `En evaluación`
- `aprobado` como `Activo`

## A Negociar

Propósito actual:

- un solo lead operativo por ejecutivo
- registrar primera o siguiente gestión
- capturar oferta solo si el estado lo requiere
- actualizar snapshot del lead

Opciones visibles:

- `contactado`: `reprogramado`, `negociacion`, `no_desea`
- `no_contactado`: `no_contesta`, `telefono_apagado`, `no_existe`

Reglas reales:

- solo `negociacion` exige oferta comercial
- promociones móviles se capturan con `promotion_names`
- `submit_intent = agreement_shortcut` guarda la gestión y vuelve al mismo lead para abrir la modal de acuerdo
- `reprogramado` con `next_contact_at` alimenta recordatorios
- `no_contesta` y `telefono_apagado` incrementan `no_contact_attempts`, liberan el lead a 10 minutos y lo deshabilitan al umbral 3

## Mi Chamba Y Mi Base

- `Mi chamba` lista `reprogramado` y `negociacion`
- `Mi base` usa `source = mi_base`
- la edición permite `reprogramado`, `negociacion` y `no_desea`
- `acuerdo_aceptado` sale por flujo dedicado
- un payload idéntico no debe crear interacción nueva
- `next_contact_at` sigue siendo parte del flujo cuando el caso queda reprogramado

## Acuerdo Aceptado Y Handoff

El handoff actual es:

1. Ejecutivo registra `acuerdo_aceptado`
2. se crea interacción específica
3. se copian ofertas comerciales
4. se actualiza el snapshot del lead
5. se crea o actualiza `sales`
6. el caso entra a Supervisor

Estados iniciales reales en `sales`:

- `status = acuerdo_aceptado`
- `supervisor_validation_status = pendiente`
- `management_status = pendiente_supervision`
- `sisac_status = pendiente_supervision`

Guardrails actuales:

- si no existe captura comercial reciente, no hay acuerdo
- si el acuerdo es fijo-only, la operación válida usa `fixed_agreement_supports`
- si hay portabilidad, se exigen números por línea
- los adjuntos del acuerdo se guardan en `sales.attachment_paths` y viajan al resto de módulos de revisión

## Supervisor, Postventa Y Mesa De Control

### Supervisor

- revisa acuerdos del equipo
- puede corregir datos
- puede conservar adjuntos existentes o sumar nuevos antes de validar
- registra trazabilidad en `sale_supervisor_histories`
- exige `approval_code` antes de la validación final solo si el canal es `centralizado` y el acuerdo no es `fijo`

### Postventa

- trabaja `management_status`
- registra `post_sale_updates`

### Mesa De Control

- trabaja `sisac_status`
- registra `validation_updates`
- cuando la venta llega a `entregado` para `movil` o `movil_fijo`, exige detalle SIM por línea
- emite `SupervisorStatusNotification`

Practical rule:

- si cambias revisión o handoff, revisa módulos de lectura y de escritura juntos
- si cambias adjuntos del acuerdo, revisa también las superficies readonly donde se consultan

## Dashboards Y Metas

### Ejecutivo

- `WorkController` ya muestra metas diarias y semanales
- las metas salen de `config/dashboard_goals.php`

### Supervisor

El dashboard del equipo ya maneja:

- metas por periodo (`goalMetricCards`)
- snapshot actual por ejecutivo (`gestion_total`, `contactado`)
- arrastre diario
- arrastre semanal
- breakdown semanal acumulado del mes
- flags de cumplimiento

No colapses estas capas en una sola métrica.

### Gerencia

- conserva la misma separación entre snapshot actual y metas por periodo
- depende de ejecutivos mapeados por `supervisor_executive`

## Recordatorios Y Popups

### Recordatorios Del Ejecutivo

- dependen de `status_specific = reprogramado`
- dependen de `next_contact_at`
- permanecen activos solo mientras el lead siga en `reprogramado`
- usan `reminder_stage` cuando la columna existe
- viven entre layout y endpoints dedicados

### Feedback A Supervisor

- cambios SISAC desde Mesa de Control vuelven por popup/dropdown y por refresco de la cola de acuerdos
- no tocar solo la notificación sin tocar la lista

### Otros Flujos Poll-Driven

- mensajes internos
- formularios RRHH
- frase MKT
- monitoreo TMO
- actividad ejecutiva

Todos son parte de la operación real, no adornos de UI.

## Flujos De Soporte Que Ya Importan Al Negocio

- `promotion_names` es un catálogo reutilizable distinto del tablero de PDFs promo
- `promo_documents` es consumo de biblioteca, no el mismo flujo que el catálogo de nombres
- `control-activaciones` vive después de validación como helper operativo de exportación
- cobertura territorial ya es configuración persistida y consumo readonly para Ejecutivo
- perfil visual y tema del usuario ya afectan la experiencia real del CRM

## Decision Rules

- Si cambia un estado, actualiza validación, queries, labels y pruebas.
- Si cambia `reprogramado`, revisa recordatorios y layout.
- Si cambia el acuerdo, revisa Supervisor, Mis ventas, Postventa y Validación.
- Si cambia una meta, empieza por `config/dashboard_goals.php`.
- Si cambia un dashboard, revisa también las feature tests de Supervisor o Gerencia.

## Hotspots De Validacion

- `tests/Feature/WorkReminderTest.php`
- `tests/Feature/MyWorkStatusTransitionTest.php`
- `tests/Feature/WorkAgreementShortcutTest.php`
- `tests/Feature/SupervisorDashboardTest.php`
- `tests/Feature/GerenciaDashboardTest.php`
