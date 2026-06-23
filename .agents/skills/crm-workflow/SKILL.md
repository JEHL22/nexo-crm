---
name: crm-workflow
description: "Usa esta skill cuando toques lógica de negocio en CRM Forza: estados del lead, asignación, seguimiento, handoff a ventas, validación por Supervisor, Postventa, Mesa de Control, dashboards, metas, recordatorios, promociones, visibilidad por rol, RRHH, MKT, mensajes internos, TMO o cobertura territorial. Sirve para mantener el flujo real del sistema y evitar que un cambio rompa coherencia entre módulos, tablas, rutas, roles, polling y pruebas feature."
---

# CRM Workflow

## Modo Actual

Esta skill obliga a pensar el CRM como flujo conectado, no como módulos aislados.

Antes de tocar una regla de negocio:

1. Leer la ruta real.
2. Leer el controlador del módulo.
3. Leer `references/workflow-map.md`.
4. Confirmar si ese flujo ya existe en el repo o solo en documentación antigua.
5. Si toca estados, recordatorios, dashboards o handoff, revisar las pruebas feature del área.

## Prioridades

- Priorizar flujo implementado sobre documento viejo.
- Priorizar coherencia entre roles y módulos.
- Priorizar estados reales, no nombres históricos.
- Priorizar aterrizajes por rol y visibilidad efectiva.
- Priorizar la separación actual entre snapshot operativo y métricas por periodo.

## Checklist Rapido

Si cambias una regla, revisar:

1. `leads`
2. `interactions`
3. `interaction_offers`
4. `sales`
5. `post_sale_updates`
6. `validation_updates`
7. filtros y labels visibles
8. `reminder_notifications` si toca `reprogramado` o `next_contact_at`
9. `lead_work_sessions` si cambia el momento del acuerdo o TMO
10. `supervisor_status_notifications` si afecta retroalimentación de Mesa de Control
11. `supervisor_executive` y `config/dashboard_goals.php` si toca dashboards o metas
12. `attachment_paths` y módulos readonly si cambia el acuerdo aceptado o su revisión
13. redirects desde `/` y login si cambia entrada por rol

## Responsabilidad Por Rol

- Ejecutivo:
  - consume leads en `A negociar`
  - sigue positivos en `Mi chamba`
  - crea base propia en `Mi base`
  - ve acuerdos en `Mis ventas`
  - consume promociones, recordatorios y cobertura
- Supervisor:
  - valida acuerdos
  - consulta dashboard del equipo
  - revisa `Mi base del equipo`
  - monitorea TMO y actividad
  - consume promociones, mensajes y feedback de Mesa de Control
- Postventa:
  - trabaja `management_status`
- Mesa de Control:
  - trabaja `sisac_status`
  - opera cobertura territorial
  - usa `control-activaciones` como helper operativo
- Gerencia:
  - consulta dashboard comercial y acuerdos consolidados
  - monitorea TMO y actividad
  - emite mensajes internos
- RRHH:
  - publica formularios a Ejecutivos
- MKT:
  - publica frases activas
- administrador de promociones:
  - mantiene el catálogo reutilizable `promotion_names`

## Guardrails Del Flujo Real

- No tocar un estado en un solo sitio.
- No reintroducir `interesado` ni `si_verbal` salvo que el cambio incluya controllers, labels, pruebas y remapeos históricos.
- No mover metas fuera de `config/dashboard_goals.php`.
- No mezclar los totals de cartera actual con las metas por periodo de dashboards.
- No tratar recordatorios, mensajes o popups como adornos; hoy son flujo operativo real.
- No asumir módulos de Supervisor o Gerencia fuera de lo que ya está implementado.

## Reglas Clave Del Negocio Actual

### A Negociar

- `negociacion` es la única ruta visible que obliga a capturar oferta comercial.
- promociones móviles usan `promotion_names`.
- `reprogramado` y `next_contact_at` activan recordatorios.
- `no_contesta` y `telefono_apagado` ya participan en el ciclo de reintento, liberación y eventual deshabilitación del lead.

### Mi Chamba

- la lista se centra en `reprogramado` y `negociacion`
- editar con el mismo payload no debe crear ruido histórico
- `acuerdo_aceptado` sale por flujo dedicado, no por submit simple

### Acuerdo Aceptado

- crea o actualiza `sales`
- pasa primero por Supervisor
- deja `management_status` y `sisac_status` en `pendiente_supervision`
- si es fijo-only, la rama válida usa `fixed_agreement_supports`
- los adjuntos viven en `sales.attachment_paths` y siguen visibles en Supervisor, Postventa, Validación, Gerencia y Mis ventas
- el acuerdo necesita conservar contexto suficiente para Supervisor, Postventa y Validación

### Supervisor, Postventa Y Mesa De Control

- Supervisor valida y exige `approval_code` solo cuando el acuerdo queda en `centralizado` y no es fijo-only
- Postventa mueve `management_status`
- Mesa de Control mueve `sisac_status`
- cambios SISAC deben volver a Supervisor vía notificación y cola refrescada

### Dashboards

- Supervisor y Gerencia separan:
  - metas por periodo
  - snapshot actual de cartera
- Supervisor además maneja arrastre diario, semanal y breakdown mensual lógico

## Cuando El Repo Y El Documento Divergen

- Si el módulo ya existe en código, manda el código.
- Si solo existe en documento, úsalo como orientación y dilo explícitamente.
- Si falta contexto de negocio fuera del repo, ahí sí pregunta.

## Referencias

- Leer `references/workflow-map.md` cuando el cambio toque estados, handoff, roles, dashboards, promociones, recordatorios o módulos transversales.
