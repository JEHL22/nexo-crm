# Comandos del día a día — Nexo CRM

> `php` NO está instalado en el host: todo comando Laravel va via `docker compose exec app`.

## Prender / Apagar

```bash
docker compose up -d          # Prender todo (nginx, php, mariadb, redis)
docker compose down           # Apagar (los datos de la BD se conservan)
docker compose restart        # Reiniciar
docker compose ps             # Estado de los servicios
```

→ App en **http://localhost:8080** (login local: `admin@nexo.local` / `Nexo`)

## Día a día

```bash
docker compose exec app php artisan test              # Correr tests
docker compose exec app php artisan migrate           # Aplicar migraciones nuevas
docker compose exec app php artisan optimize:clear    # Limpiar cachés (primer auxilio)
docker compose exec app bash                          # Terminal dentro del contenedor
docker compose exec app ./vendor/bin/pint --dirty     # Formatear archivos modificados
```

## Frontend

```bash
docker compose run --rm node npm run build    # Compilar assets
docker compose run --rm node npm run dev      # Dev server con hot reload
docker compose run --rm node npm install      # Solo si cambia package.json
```

## Base de datos

```bash
docker compose exec db mariadb -unexo -pnexo_pass crm_nexo   # Consola SQL
# Desde Windows (DBeaver/HeidiSQL): localhost:3307, user nexo, pass nexo_pass

docker compose exec app php artisan migrate:fresh --seed   # ⚠️ Resetea TODO y re-seedea
```

## Cuando algo falla

```bash
docker compose logs app --tail 50      # Errores de PHP
docker compose logs nginx --tail 20    # Errores de nginx
docker compose up -d --build           # Reconstruir imagen (si cambió el Dockerfile)
```

## Nuclear (casi nunca)

```bash
docker compose down -v    # ⚠️ Apaga Y BORRA el volumen de la BD
```
