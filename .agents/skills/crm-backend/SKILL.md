---
name: crm-backend
description: "Usa esta skill cuando trabajes backend en CRM Forza: controladores Laravel, consultas Eloquent, validaciones inline, rutas, migraciones, dashboards, recordatorios, promociones, handoff entre módulos, polling, cobertura territorial, perfil, RRHH, MKT, mensajería interna o integridad de estados. Sirve para mantener el estilo real del repo: lógica principal en controladores, transacciones puntuales, payloads compactos y coherencia con la BD, los roles y las pruebas feature actuales."
---

# CRM Backend

## Modo Actual

Esta skill existe para resolver backend como ya lo resuelve este CRM, no como lo resolvería un proyecto greenfield.

Antes de tocar código backend:

1. Leer la ruta real en `routes/web.php`.
2. Leer el controlador y, si aplica, su método `pulse()` o `feed()`.
3. Leer el modelo, relaciones y columnas realmente usadas.
4. Leer la Blade o el layout que consume ese payload.
5. Si hay estados, dashboards, recordatorios o handoff, leer también las referencias de la skill.
6. Si el cambio toca reglas recientes, revisar las feature tests relacionadas antes de improvisar.

## Prioridades

- Priorizar coherencia con el repo sobre arquitectura bonita.
- Priorizar controladores claros sobre capas extra.
- Priorizar la BD real y las pruebas feature sobre documentación vieja.
- Priorizar payloads compactos y legibles para Blade o polling.
- Priorizar `config('app.timezone')` y `config/dashboard_goals.php` como fuentes de verdad compartidas.

## Checklist Rapido

Si tocas un módulo CRM, revisar siempre:

1. validación inline y mensajes de error
2. alcance por rol, ownership y middleware
3. historial y snapshot en `leads` o `sales`
4. JSON, partial o Blade que consume el cambio
5. timezone visible al usuario con `config('app.timezone')`
6. si aplica: `interactions`, `interaction_offers`, `sale_supervisor_histories`, `post_sale_updates`, `validation_updates`
7. si aplica: `reminder_notifications`, `supervisor_status_notifications`, `lead_work_sessions`, `executive_activity_sessions`, `executive_activity_events`
8. si aplica: `promotion_names`, `promo_documents`, `activation_control_records`, `territorial_province_settings`, `territorial_province_setting_histories`
9. si aplica: `sales.attachment_paths`, `agreement-attachments`, `kept_attachment_paths`, `new_agreement_attachments`
10. si aplica: redirects desde `/`, login redirect, `AppServiceProvider`, `config/dashboard_goals.php`
11. si aplica: `campaign_user`, `supervisor_executive`, `admin.users` y reglas de contraseña
12. las pruebas feature del flujo afectado

## Patrones A Preservar

- Mantener los controladores como capa principal de orquestación.
- Usar `$request->validate([...])` dentro de la acción salvo en excepciones ya existentes como perfil.
- Usar `DB::transaction()` cuando un cambio toca varias tablas o mezcla snapshot e historial.
- Mantener catálogos locales como constantes privadas cuando pertenecen a un módulo.
- Reusar relaciones con `with([...])` y closures simples para ordenar o acotar.
- En dashboards y polling, devolver arrays compactos y parciales Blade antes que inventar presenters o resources.
- Si existe `index()` + `pulse()` o `feed()`, compartir la misma regla de consulta para no desalinear la pantalla y el refresco.

Secuencia de escritura que hoy domina el repo:

1. validar request
2. normalizar ramas dependientes y limpiar campos incompatibles
3. resolver y autorizar el registro
4. abrir transacción
5. crear historial primero cuando aplica
6. crear filas relacionadas como ofertas, recipients o detalles
7. actualizar snapshot visible
8. redirigir con flash o devolver JSON compacto según el módulo

## Reglas Del Repo Que Ya Son Reales

- `WorkController` ya maneja `focused_lead` y `open_agreement_modal`; si tocas el atajo de acuerdo aceptado, conserva ese regreso al mismo lead antes de reasignar otro.
- En `A negociar`, `no_contact_attempts` sí forma parte del flujo actual: `no_contesta` y `telefono_apagado` incrementan el contador, liberan el lead a los 10 minutos y lo deshabilitan al llegar al umbral operativo de 3.
- Los recordatorios viven entre `ReminderNotificationController` y `AppServiceProvider`; solo deben existir mientras el lead siga en `reprogramado`.
- `MyWorkController` ya trata ediciones idénticas como no-op y no debe recrear `edicion_mi_chamba` si el payload normalizado no cambió.
- El handoff de `acuerdo_aceptado` sigue dentro de `MyWorkController`: crea `interaction`, replica ofertas, actualiza `lead`, crea o actualiza `sale`, cierra TMO y limpia recordatorios.
- Si el acuerdo es solo fijo, la venta usa `fixed_agreement_supports` y limpia `service_channel`, `attention_time_slot`, `attention_date`, `operator_name` y `delivery_type`.
- `SupervisorAgreementController` solo exige `approval_code` cuando el canal queda en `centralizado` y el acuerdo no es solo fijo; ese mismo valor se lee luego en Supervisor, Mis ventas, Postventa y Validación.
- `MyWorkController` y `SupervisorAgreementController` ya tratan los adjuntos como IO real: guardan en `public/agreement-attachments`, persisten `attachment_paths` y limpian archivos nuevos si falla el guardado.
- `AdminUserController` exige `password` mínimo de 8 caracteres y normaliza `campaign_id` y `supervisor_user_id` por rol antes de tocar `campaign_user` y `supervisor_executive`.
- `ValidationController` ya genera `SupervisorStatusNotification` para reflejar cambios de Mesa de Control de vuelta en Supervisor.
- `SupervisorDashboardController`, `DashboardController` y `WorkController` leen metas desde `config/dashboard_goals.php`; no dupliques números en controladores ni vistas.
- En dashboards de Supervisor y Gerencia, `goalMetricCards` son métricas por periodo, mientras `gestion_total` y `contactado` salen del snapshot actual del lead.

## Evita Estos Desencajes

- no introducir services, repositories, DTOs, enums o capas extra por defecto
- no renombrar estados a inglés
- no mover lógica de polling o payload a una arquitectura nueva si hoy vive en el controlador
- no asumir que el documento funcional está más actualizado que el código
- no inventar tablas o columnas no presentes en el repo
- no mezclar métricas de cartera actual con métricas de periodo en dashboards
- no regresar promociones móviles a montos libres si hoy dependen de `promotion_names`

## Cuando Codigo Y Documento Divergen

1. Decir brevemente dónde divergen.
2. Implementar contra el repo actual y sus pruebas.
3. Pedir esquema o migración solo si el cambio depende de una BD más nueva que no está en el repo.

## Referencias

- Leer `references/backend-patterns.md` para stack, rutas vivas, convenciones de controladores, tablas clave y hotspots de pruebas.
- Leer `references/workflow-backend-map.md` cuando el cambio toque estados, recordatorios, handoff comercial, dashboards, TMO o roles.
