---
name: crm-frontend
description: "Usa esta skill cuando trabajes interfaz en CRM Forza: vistas Blade, formularios, filtros, cards, modales, layouts, dashboards compactos, polling, popups operativos, navegación por rol, mapas Leaflet, promociones, perfil, RRHH, MKT o tableros de Supervisor y Gerencia. Sirve para mantener la UI real del repo: Blade + Tailwind + Alpine, densidad operativa, estilos locales cuando ya existen y coherencia con `layouts.app`, los payloads actuales y las pantallas vivas del CRM."
---

# CRM Frontend

## Modo Actual

Una pantalla nueva o editada debe sentirse del mismo CRM desde el primer vistazo y también en su comportamiento.

Antes de tocar una vista:

1. Leer la Blade objetivo.
2. Leer la pantalla hermana más parecida.
3. Leer `resources/views/layouts/app.blade.php` si hay navegación, polling, badges o popups de rol.
4. Leer `references/frontend-style-guide.md` si la edición toca una pantalla nueva, un dashboard, una modal o una superficie con polling.

## Prioridades

- Priorizar continuidad visual sobre rediseño.
- Priorizar densidad operativa sobre aire decorativo.
- Priorizar estructuras ya usadas en módulos cercanos.
- Priorizar JS simple y local sobre arquitectura frontend nueva.
- Priorizar compatibilidad con light/dark theme cuando la pantalla vive dentro de `layouts.app`.

## Anclas Visuales Del Repo

Tomar como base primero:

- `work/show`
- `partials/agreement-modal`
- `my-work/index`
- `my-work/base`
- `my-work/show`
- `my-sales/index`
- `admin/users/index`
- `supervisor/dashboard/index`
- `supervisor/agreements/index`
- `supervisor/agreements/show`
- `supervisor/team-base/index`
- `post-sale/index`
- `validation/index`
- `management/dashboard`
- `management/agreements`
- `monitoring/index`
- `activity-monitoring/index`
- `activation-control/index`
- `territorial-coverage/index`
- `territorial-coverage/executive`
- `internal-messages/index`
- `rrhh/surveys/index`
- `mkt/phrases/index`
- `admin/promotions/index`
- `promotion-admin/index`
- `executive-promotions/index`
- `profile/edit`

## Lenguaje Visual A Mantener

- Usar `x-app-layout` en pantallas autenticadas.
- Mantener wrapper centrado con card blanca principal, salvo módulos que ya usan contenedores más anchos como mapas o dashboards.
- Preferir spacing compacto:
  - `px-3 py-2.5`
  - `px-4 py-3`
  - `p-4`
  - `gap-3` o `gap-4`
- Conservar botones del proyecto:
  - primario oscuro
  - secundario claro con borde
  - acción de detalle outlined
- Mantener labels en español.
- Si la pantalla ya usa `<style>` local o clases `crm-*`, respetar esa convención antes de volver todo a utilidades planas.

## Patrones Que Ya Son Reales

- Filtros arriba de la card principal.
- Flash messages antes del contenido central.
- Cards redondeadas antes que tablas pesadas, salvo dashboards comparativos donde la tabla ya es parte del diseño.
- Modales grandes para detalle, edición o trazabilidad.
- Alpine local y scripts inline al final de la Blade.
- Polling por página o por layout con payload pequeño y rerender parcial cuando ya existe.
- Dashboards de Supervisor y Gerencia con cards de metas, tablas/matrices compactas y drilldown modal.
- `work/show` y `my-work/show` usan flujos ricos con secciones condicionales y modal de acuerdo; no simplificarlos hacia una UX más pobre.
- `partials/agreement-modal` y `supervisor/agreements/show` ya manejan adjuntos con resumen, listado y errores inline; no los rebajes a un `input[type=file]` sin contexto.
- `admin/users/index` usa modales Alpine de crear y detalle con campos condicionales por rol; conservar esa interacción antes de separar la experiencia en CRUDs planos.
- `activation-control/index` es flujo persistido con tabla guardada y exportación del backend, no un draft visual.
- `territorial-coverage/*` ya es una experiencia grande con Leaflet, payload backend y estado local por página.

## Reglas De Interaccion

- Si una pantalla depende de `pulse()` o `feed()`, conservar cadence, forma del payload, partials y guards de requests en vuelo.
- Si una notificación o popup es de layout, dejarla en `layouts.app`.
- Si una página usa `focused_lead` o `open_agreement_modal`, respetar ese retorno al mismo contexto.
- Si una vista de Supervisor muestra `approval_code`, debe seguir la regla real: solo aparece para `centralizado` y no para acuerdos solo fijos.
- Si una vista mezcla adjuntos existentes y nuevos, mantener listas separadas, resumen visible y errores inline.
- Si una pantalla muestra datos operativos compactos, reducir padding antes de achicar tipografía en exceso.
- Si la validación backend es condicional, reflejarlo con JS simple y preservar `old(...)`.

## Evita Estos Desencajes

- no introducir React, Vue, Livewire, Stimulus ni componentes globales nuevos por defecto
- no mover polling o popups de layout a una página cualquiera
- no inflar spacing solo porque hay espacio disponible
- no convertir pantallas de cards en tablas si el módulo no lo pide
- no quitar dark-mode compatibility cuando la pantalla ya vive bajo `crm-dark-theme`
- no volver `work/show` o `supervisor/dashboard/index` a layouts genéricos

## Referencias

- Leer `references/frontend-style-guide.md` para layout, densidad, dashboards, polling, mapas, modales y ownership visual de cada superficie.
