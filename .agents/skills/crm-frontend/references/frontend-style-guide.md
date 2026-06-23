# Frontend Style Guide

## Stack Y Ownership Visual

- Blade templates
- Tailwind utilities
- Alpine.js para estado local
- ApexCharts en dashboards y métricas
- Leaflet en cobertura territorial
- `layouts.app` como shell real de navegación, popups, badges y tema

Practical rule:

- si el comportamiento ya vive en `layouts.app`, no lo mudes a una vista local
- si una pantalla ya usa clases `crm-*` o `<style>` local, preserva ese patrón antes de simplificarlo a otra cosa

## Shell Del CRM

- páginas autenticadas con `x-app-layout`
- fondo general gris suave
- cards blancas con bordes o sombras suaves
- sidebar compacta y navegación por rol
- soporte real para dark theme vía `crm-dark-theme`
- navegación viva para:
  - Ejecutivo: `A negociar`, `Mi chamba`, `Mis ventas`, `Mis promociones`, `Mi cobertura`
  - Supervisor: acuerdos, dashboard, TMO, actividad, promociones, mensajes
  - Gerencia, RRHH, MKT, administrador de promociones y Admin con sus módulos propios

## Densidad

El CRM actual prefiere UI compacta y operativa.

Defaults útiles:

- wrappers y cards: `p-3`, `p-4`, `px-4 py-3`
- gaps: `gap-3`, `gap-4`, `space-y-3`
- labels: `text-[10px]`, `text-[11px]`, `text-sm`
- paneles internos: `rounded-xl` o `rounded-2xl`
- botones secundarios: `px-3 py-1.5` o similar

Regla práctica:

- reduce padding, altura y wrappers antes de tocar el ancho de la pantalla
- si una grilla deja huecos altos, separa columnas independientes

## Patrones Base De Pantalla

Orden típico:

1. flash messages
2. card principal
3. título y helper corto
4. filtros
5. lista, resumen o dashboard
6. estado vacío o paginación si aplica

Patrones repetidos:

- filtros al inicio de la card
- tarjetas redondeadas en vez de tablas, salvo módulos comparativos
- bordes sutiles y énfasis azul o neutro para prioridad
- botones oscuros para CTA primario
- modales grandes con backdrop opaco

## Formularios

- labels arriba
- inputs simples y nativos
- bloques condicionales agrupados visualmente
- `old(...)` cuando el backend ya puede devolver errores condicionales
- validaciones de negocio reflejadas con JS simple en la misma página

Casos que ya existen y deben preservarse:

- `work/show` usa selects del catálogo `promotion_names` para promociones móviles
- `my-work/show` usa confirmación compacta antes del acuerdo cuando aplica
- `partials/agreement-modal` maneja adjuntos múltiples con resumen incremental y errores inline
- formularios de acuerdo separan bien la rama fija-only de la rama móvil o mixta
- `supervisor/agreements/show` conserva adjuntos existentes y nuevos, y solo muestra `approval_code` cuando el canal es `centralizado` y el acuerdo no es fijo-only
- `admin/users/index` usa dos modales Alpine con campos de campaña y supervisor condicionados por rol

## Dashboards Y Tableros

### Supervisor

`resources/views/supervisor/dashboard/index.blade.php` ya combina:

- cards compactas de metas
- filtros por fecha
- tabla/matriz principal
- drilldown modal por ejecutivo y estado
- visualización de arrastre diario, semanal y mes lógico

Practical rule:

- no reemplazar esto por una plantilla admin genérica
- si el backend ya manda `carryover`, `weekly_carryover`, `weekly_month_breakdown`, `goal_completion` o `month_weeks`, la Blade debe seguir consumiéndolos con esa forma

### Gerencia

`resources/views/management/dashboard.blade.php` ya mezcla:

- goal cards agrupadas
- comparativo por supervisor
- foco por supervisor
- tabla más ancha que un CRUD normal

Practical rule:

- Gerencia sí puede ser más tabular que otros módulos, pero sigue siendo compacta

### Monitoreo

- `monitoring/index` y `activity-monitoring/index` son pantallas densas, con filtros y refresco continuo
- preservar cards, parciales y bloques laterales antes de simplificar a listas planas

## Polling, Partials Y Popups

El repo ya tiene varias superficies con polling real:

- recordatorios del ejecutivo
- popup/dropdown de Supervisor por cambios de Mesa de Control
- mensajes internos
- popup RRHH
- frase MKT
- tableros de monitoreo
- board de acuerdos del supervisor

Reglas:

- mantener payload JSON pequeño
- mantener partial Blade como fuente de render cuando ya existe
- hacer fetch al cargar, al volver foco y con cadence periódica si el módulo ya lo usa
- evitar overlapping requests con un guard local cuando el módulo ya trabaja así
- no refrescar una lista si una modal crítica abierta rompería el contexto

Ownership actual:

- `layouts.app` posee recordatorios, popup RRHH, mensajes internos, frase MKT y notificaciones de Supervisor
- páginas operativas poseen sus boards y parciales de `pulse()`

## Pantallas Ancla Del Repo

- `resources/views/work/show.blade.php`
- `resources/views/partials/agreement-modal.blade.php`
- `resources/views/my-work/index.blade.php`
- `resources/views/my-work/base.blade.php`
- `resources/views/my-work/show.blade.php`
- `resources/views/my-sales/index.blade.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/supervisor/dashboard/index.blade.php`
- `resources/views/supervisor/agreements/index.blade.php`
- `resources/views/supervisor/agreements/partials/list.blade.php`
- `resources/views/supervisor/agreements/show.blade.php`
- `resources/views/supervisor/team-base/index.blade.php`
- `resources/views/post-sale/index.blade.php`
- `resources/views/validation/index.blade.php`
- `resources/views/management/dashboard.blade.php`
- `resources/views/management/agreements.blade.php`
- `resources/views/monitoring/index.blade.php`
- `resources/views/activity-monitoring/index.blade.php`
- `resources/views/internal-messages/index.blade.php`
- `resources/views/rrhh/surveys/index.blade.php`
- `resources/views/mkt/phrases/index.blade.php`
- `resources/views/activation-control/index.blade.php`
- `resources/views/admin/promotions/index.blade.php`
- `resources/views/promotion-admin/index.blade.php`
- `resources/views/executive-promotions/index.blade.php`
- `resources/views/territorial-coverage/index.blade.php`
- `resources/views/territorial-coverage/executive.blade.php`
- `resources/views/profile/edit.blade.php`

## Mapas Y Cobertura Territorial

El módulo de cobertura ya tiene una identidad propia:

- contenedor más ancho
- mapa grande como superficie principal
- panel lateral operativo
- modal para detalle provincial
- JS local para payload, búsqueda, selección y guardado

Reglas:

- mantener Leaflet y el estado local por página
- si la vista es de Ejecutivo, mantenerla readonly
- si la vista es operativa, mantener guardado async con CSRF y recarga del payload backend

## Activation Control

`resources/views/activation-control/index.blade.php` es un módulo operativo, no un formulario bonito aislado.

Debe conservar:

- formulario compacto por grupos
- tabla de registros persistidos
- CTA de exportación basado en datos guardados
- feedback claro de éxito o error

## Perfil

`resources/views/profile/edit.blade.php` ya es parte del lenguaje del CRM:

- upload de avatar
- colores por usuario
- selector de tema
- compatibilidad con light/dark

Si tocas layout, no rompas esta pantalla ni su reflejo visual en el shell.

## Evita

- estilos globales nuevos para resolver un cambio local
- padding amplio por defecto
- reconstruir markup del backend completo en JS si ya existe partial Blade
- componentes externos o framework frontend nuevo
- esconder la complejidad real del dashboard detrás de un layout promedio
