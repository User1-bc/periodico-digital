# Periódico Digital - Guía para Agentes

## Resumen del proyecto
Aplicación PHP (Apache) desplegada en **Render** (`periodico-digital.onrender.com`), base de datos **Neon PostgreSQL**. Repo: `https://github.com/User1-bc/periodico-digital.git`.

## Arquitectura clave
- **Entrypoint**: `index.php` → requiere `conexion.php` → PDO a PostgreSQL
- **Conexión BD**: `conexion.php` lee `DATABASE_URL` (env var) con fallback hardcodeado (incorrecto)
- **Deploy**: Docker (`php:8.2-apache` + `pdo_pgsql`) → Render build automático desde `main`
- **Service ID Render**: `srv-danlo0mgekts739coor0`
- **API Key Render**: en `C:\Users\Usuario\AppData\Local\Temp\opencode\map_final.ps1` (`rnd_eESqSM8inN...`)

## Estado actual (sep 2026)
- **Error en producción**: `SQLSTATE[08006] password authentication failed for user 'neondb_owner'`
- **Causa**: `DATABASE_URL` en Render tiene password equivocado (`npg_95UtNN0f2OvYtycdJ`)
- **Fix**: PUT `DATABASE_URL` con la URL verbatim del usuario (password `npg_VRBtL9lA8pSJ`) → manual deploy → poll HTTP 200 limpio
- **URL verbatim Neon**: `C:\Users\Usuario\AppData\Local\Temp\opencode\neon_url_verbatim_usuario_periodico_fn6c_ascii.txt`
- **NO borrar cuenta Render**: Neon es solo BD; Render sirve la app

## Comandos útiles
```bash
# Ver logs build/deploy Render (via API)
curl -H "Authorization: Bearer $RENDER_API_KEY" \
  "https://api.render.com/v1/services/srv-danlo0mgekts739coor0/deploys"

# Actualizar DATABASE_URL en Render
curl -X PUT -H "Authorization: Bearer $RENDER_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"value":"postgresql://neondb_owner:npg_VRBtL9lA8pSJ@ep-soft-wave-b5wklrr4-pooler.c-7.us-east-2.aws.neon.tech/neondb?sslmode=require"}' \
  "https://api.render.com/v1/services/srv-danlo0mgekts739coor0/env-vars/DATABASE_URL"

# Trigger deploy manual
curl -X POST -H "Authorization: Bearer $RENDER_API_KEY" \
  -H "Content-Type: application/json" -d '{}' \
  "https://api.render.com/v1/services/srv-danlo0mgekts739coor0/deploys"

# Verificar HTTP
curl -s -o /dev/null -w "%{http_code}" https://periodico-digital.onrender.com
```

## Gotchas
- **Nunca re-teclear la URL Neon**: leerla desde archivo verbatim en disco
- **libpq local sin SNI**: probes PHP locales fallan con "Endpoint ID is not specified"; Render sí tiene SNI
- **package.json** solo tiene `web-push` (no hay scripts de test/lint/build)
- **Zona horaria**: `America/Santo_Domingo` forzada en `index.php`
- **Tablas BD**: `noticias`, `anuncios`, `podcasts` (ver `index.php`)

## Archivos de referencia en disco (temp)
- `C:\Users\Usuario\AppData\Local\Temp\opencode\map_final.ps1` — API key Render
- `C:\Users\Usuario\AppData\Local\Temp\opencode\neon_url_verbatim_usuario_periodico_fn6c_ascii.txt` — URL Neon correcta
- `C:\Users\Usuario\AppData\Local\Temp\opencode\dump_dsm_real_envvars_deploys_http_periodico_ds4r_*.txt` — estado real env vars/deploys/HTTP