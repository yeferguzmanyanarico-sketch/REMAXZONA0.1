# Documentación de Implementación: RNF025 – RNF030
**Proyecto:** RE/MAX Zona Inmobiliaria  
**Stack:** Laravel + Livewire + MySQL  
**Fecha:** 2026-11-18  

---

## 1. Descripción de los seis requerimientos

| Código | Requerimiento | Objetivo |
|---|---|---|
| **RNF025** | Accesibilidad Web (WCAG 2.1 AA) | Garantizar navegación semántica, por teclado, contraste suficiente, indicadores de enfoque, etiquetas accesibles y compatibilidad con lectores de pantalla en el sitio público y panel administrativo. |
| **RNF026** | Optimización SEO | Dotar al sitio público de títulos únicos, meta descripciones, URLs canónicas, metadatos Open Graph/Twitter, `robots.txt`, `sitemap.xml` dinámico y datos estructurados JSON-LD sin inventar información inmobiliaria. |
| **RNF027** | Disponibilidad del Sistema (99 % mensual) | Incorporar un endpoint de salud (`/salud`), verificación de conectividad con MySQL y almacenamiento, registro de fallos críticos y comando programable de monitoreo sin exponer credenciales. |
| **RNF028** | Copias de Seguridad y Recuperación | Implementar respaldos periódicos de base de datos y multimedia en almacenamiento privado, con separación por entorno, verificación SHA-256 y bitácora operativa, sin sobrescribir copias previas. |
| **RNF029** | Protección contra Ataques Web | Reforzar cabeceras HTTP de seguridad, escape seguro en JSON-LD, protección de `storage/app/public`, HTTPS en producción y verificar controles existentes contra SQLi, XSS, CSRF, fuerza bruta y escalada de privilegios. |
| **RNF030** | Optimización de Imágenes y Recursos | Optimizar fotografías del catálogo, vendedores, agentes, oficinas, portada y recursos gráficos mediante WebP, redimensionamiento proporcional, `loading="lazy"`, dimensiones explícitas, versionado CSS y cabeceras de caché. |

---

## 2. Cambios implementados

### RNF025 – Accesibilidad Web (WCAG 2.1 AA)
- **Navegación y landmarks:** Se agregó `aria-current="page"` en los enlaces activos de navegación principal y barra lateral de administración; se añadió `skip-link` (`#contenido-admin`) y `aria-label` al menú lateral del panel.
- **Indicadores de enfoque y utilidades:** Se agregaron reglas globales `:focus-visible` en `public/css/styles.css` y se amplió `:focus-visible` en `public/css/admin.css` para cubrir `textarea` y `[tabindex]`, además de la clase `.sr-only`.
- **Contraste:** Se ajustó `--muted` en `public/css/styles.css` a `#525e75` para asegurar relación de contraste ≥ 4.5:1 sobre fondos claros.
- **Formularios de autenticación:** Se añadió `aria-invalid` y `role="alert"` a los mensajes de error en `iniciar-sesion.blade.php`.

### RNF026 – Optimización SEO
- **Metadatos y Open Graph:** `publico.blade.php` ahora renderiza `<meta name="description">`, `<meta name="robots">`, `<link rel="canonical">`, etiquetas `og:*` y `twitter:*`, y JSON-LD `RealEstateAgent` usando datos reales de `ConfiguracionContacto`.
- **Datos por página y catálogo:** Se configuraron `metaDescription`, `canonicalUrl` y `datosEstructurados` (`ItemList` en `Propiedad::construirDatosEstructuradosCatalogo`) en los seis componentes públicos (`PaginaInicio`, `PaginaEmpresa`, `PaginaPropiedades`, `PaginaOficinas`, `PaginaPropiedadesOficina`, `PaginaContacto`).
- **Rastreo:** Se actualizó `public/robots.txt` (bloqueando `/admin` y `/livewire/`, referenciando `/sitemap.xml`) y se creó la ruta dinámica `GET /sitemap.xml` en `routes/web.php`.

### RNF027 – Disponibilidad del Sistema (99 % mensual)
- **Canal de bitácora:** Se registró el canal diario `disponibilidad` (`storage/logs/disponibilidad.log`, retención 30 días) en `config/logging.php`.
- **Servicio y endpoint de salud:** Se crearon `ServicioDisponibilidad` y `EstadoSistemaController` expuestos en `GET /salud` (`Cache-Control: no-store`), verificando modo mantenimiento, conectividad con BD (`SELECT 1`) y escritura en `storage/logs` y `storage/app/public` sin exponer credenciales.
- **Excepciones críticas y monitoreo:** Se configuró el registro de `QueryException` y `\PDOException` en `bootstrap/app.php`, y se creó el comando `php artisan sistema:verificar-salud` programado cada 5 minutos en `routes/console.php`.

### RNF028 – Copias de Seguridad y Recuperación
- **Respaldo privado:** `ServicioRespaldo` almacena copias en `storage/app/private/respaldos/{entorno}/{fecha_hora}` (protegido con `.gitignore` y fuera de `public/`), sin sobrescribir respaldos previos ni incluir credenciales en scripts (`MYSQL_PWD` en proceso efímero para `mysqldump` o copia SQLite según el driver).
- **Alcance e integridad:** Respalda la base de datos y comprime `storage/app/public` y `public/images` en `multimedia.zip`, generando `manifiesto.json` con tamaños y hashes `sha256`.
- **Comandos:** Se crearon `php artisan sistema:respaldar` (programado diariamente a las `02:30` en `routes/console.php`) y `php artisan sistema:verificar-respaldo {directorio}` (verificación no destructiva de integridad).

### RNF029 – Protección contra Ataques Web
- **Cabeceras HTTP:** Se creó `CabecerasSeguridad` (registrado globalmente en `bootstrap/app.php`) con `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`, `X-Permitted-Cross-Domain-Policies: none`, `Content-Security-Policy` compatible con Livewire y `Strict-Transport-Security` bajo HTTPS/producción.
- **Protección de almacenamiento y XSS:** Se añadió `storage/app/public/.htaccess` para deshabilitar listado de directorios y denegar ejecución de scripts; se reforzó `json_encode` en `publico.blade.php` con `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`.
- **HTTPS y control de acceso:** Se configuró `URL::forceScheme('https')` en `AppServiceProvider` para producción con `APP_URL` HTTPS; se verificó la persistencia de `RateLimiter` en login y 2FA, validación de imágenes en servidor y autorización estricta `ADMINISTRADOR` / `MARKETING` en `VerificarRol` y componentes Livewire.

### RNF030 – Optimización de Imágenes y Recursos
- **Conversión WebP sin pérdida de originales:** `ServicioOptimizacionImagen` genera copias `.webp` redimensionadas proporcionalmente (1920 px portada, 1200 px propiedades, 1000 px oficinas, 600 px vendedores/agentes) evitando reconversiones cuando la copia está vigente.
- **Integración automática y por lotes:** Se integró en `GestionPropiedades`, `GestionVendedores`, `GestionAgentes`, `GestionOficinas` y `GestionPortada`, además del comando `php artisan sistema:optimizar-imagenes` (20 imágenes WebP generadas/actualizadas).
- **Carga y caché:** Se creó `<x-imagen-optimizada>` (`<picture>` con `<source type="image/webp">`, `width`, `height`, `decoding="async"`, `loading="lazy"` en imágenes secundarias y `fetchpriority="high"` en portada), se agregó `picture { display: contents; }` en `styles.css`, versionado `?v=filemtime(...)` para CSS y reglas `mod_deflate`, `mod_expires` y `Cache-Control` en `public/.htaccess`.

---

## 3. Archivos creados y modificados

**Creados:**
- `app/Services/ServicioDisponibilidad.php`
- `app/Http/Controllers/EstadoSistemaController.php`
- `app/Console/Commands/VerificarSaludSistema.php`
- `app/Services/ServicioRespaldo.php`
- `app/Console/Commands/RespaldarSistema.php`
- `app/Console/Commands/VerificarRespaldo.php`
- `app/Http/Middleware/CabecerasSeguridad.php`
- `app/Services/ServicioOptimizacionImagen.php`
- `app/Console/Commands/OptimizarImagenes.php`
- `resources/views/components/imagen-optimizada.blade.php`
- `storage/app/private/respaldos/.gitignore`
- `storage/app/public/.htaccess`
- `tests/Feature/RequerimientosNoFuncionalesTest.php`
- `documentacion/DOCUMENTACION_IMPLEMENTACION_RNF025_RNF030.md`

**Modificados:**
- `resources/views/components/layouts/publico.blade.php`
- `resources/views/components/layouts/admin.blade.php`
- `resources/views/livewire/autenticacion/iniciar-sesion.blade.php`
- `resources/views/livewire/publico/pagina-inicio.blade.php`
- `resources/views/livewire/publico/pagina-empresa.blade.php`
- `resources/views/livewire/publico/pagina-oficinas.blade.php`
- `resources/views/livewire/publico/pagina-propiedades.blade.php`
- `resources/views/livewire/publico/pagina-propiedades-oficina.blade.php`
- `public/css/styles.css`
- `public/css/admin.css`
- `public/robots.txt`
- `public/.htaccess`
- `app/Models/Propiedad.php`
- `app/Livewire/Publico/PaginaInicio.php`
- `app/Livewire/Publico/PaginaEmpresa.php`
- `app/Livewire/Publico/PaginaPropiedades.php`
- `app/Livewire/Publico/PaginaOficinas.php`
- `app/Livewire/Publico/PaginaPropiedadesOficina.php`
- `app/Livewire/Publico/PaginaContacto.php`
- `app/Livewire/Admin/GestionPropiedades.php`
- `app/Livewire/Admin/GestionVendedores.php`
- `app/Livewire/Admin/GestionAgentes.php`
- `app/Livewire/Admin/GestionOficinas.php`
- `app/Livewire/Admin/GestionPortada.php`
- `app/Providers/AppServiceProvider.php`
- `bootstrap/app.php`
- `config/logging.php`
- `routes/web.php`
- `routes/console.php`

---

## 4. Decisiones técnicas importantes

1. **WebP mediante extensión GD nativa:** Se evitó instalar paquetes externos aprovechando `imagewebp()` de PHP GD y manteniendo intactas las imágenes originales en disco.
2. **`<picture>` con `display: contents`:** Permite servir WebP con respaldo automático sin alterar selectores ni cuadrículas CSS existentes.
3. **Respaldos en `storage/app/private/respaldos`:** Garantiza aislamiento respecto a `public/`, separación por entorno (`APP_ENV`) y verificación SHA-256 sin exponer credenciales en archivos.
4. **Restauración no automatizada:** Por seguridad, no se implementó restauración destructiva automática; la restauración debe ejecutarse manualmente previa verificación con `sistema:verificar-respaldo` y autorización explícita.

---

## 5. Pruebas ejecutadas y resultados

| Comando / Prueba | Resultado | Detalle |
|---|---|---|
| `php artisan sistema:optimizar-imagenes` | Exitoso | 20 imágenes WebP generadas/actualizadas sin errores. |
| `php artisan test` | Exitoso | **36 pruebas aprobadas, 157 aserciones** (30 existentes + 6 en `RequerimientosNoFuncionalesTest`). |

---

## 6. Evidencias de cumplimiento

- `tests/Feature/RequerimientosNoFuncionalesTest.php` valida automáticamente:
  - Skip-links, `aria-current="page"` y landmarks en layouts público y admin (RNF025).
  - Metadatos SEO, JSON-LD `ItemList` y `GET /sitemap.xml` (RNF026).
  - Respuesta JSON y cabeceras `no-store` de `GET /salud` (RNF027).
  - Generación de respaldo privado, `multimedia.zip`, `manifiesto.json` y validación SHA-256 (RNF028).
  - Cabeceras de seguridad HTTP y restricción `403` para rol `MARKETING` en módulos de `ADMINISTRADOR` (RNF029).
  - Generación WebP conservando imagen original (RNF030).

---

## 7. Configuraciones necesarias para producción

- **Programador de tareas (Cron / Task Scheduler):** Configurar `* * * * * php artisan schedule:run` para ejecutar `sistema:verificar-salud` (cada 5 min) y `sistema:respaldar` (diario 02:30).
- **Binario `mysqldump`:** Asegurar disponibilidad en `PATH` o definir `MYSQLDUMP_PATH` en `.env` de producción.
- **Monitor externo de disponibilidad:** Configurar un monitor HTTP externo apuntando a `https://tudominio/salud` con alertas ante respuestas distintas de `200`.
- **Certificado SSL/TLS y `.env`:** Configurar `APP_ENV=production`, `APP_DEBUG=false` y `APP_URL=https://...` para activar `URL::forceScheme('https')` y HSTS.
- **Almacenamiento externo de respaldos:** Replicar `storage/app/private/respaldos/production` hacia un almacenamiento externo/off-site.
- **Módulos Apache:** Verificar que `mod_rewrite`, `mod_deflate`, `mod_expires` y `mod_headers` estén habilitados en el servidor web.

---

## 8. Limitaciones externas

- El cumplimiento efectivo del **99 % de disponibilidad mensual** depende del servidor, red, base de datos y monitoreo externo en producción (no medible en entorno local).
- La compresión HTTP y caché de navegador definidas en `.htaccess` dependen de Apache con los módulos habilitados (o su equivalente en Nginx).
- La ejecución real de `mysqldump` depende de que el binario cliente de MySQL esté instalado en el servidor.

---

## 9. Tareas pendientes

- Configurar cron de Laravel Scheduler y monitoreo externo sobre `/salud` en el servidor de producción.
- Configurar copia off-site de `storage/app/private/respaldos/production`.
- Ejecutar una auditoría visual/automática (ej. Lighthouse) sobre la URL de producción una vez desplegada.

---

## 10. Matriz final de cumplimiento

| Código | Requerimiento | Estado |
|---|---|---|
| **RNF025** | Accesibilidad Web (WCAG 2.1 AA) | Implementado y verificado |
| **RNF026** | Optimización SEO | Implementado y verificado |
| **RNF027** | Disponibilidad del Sistema (99 % mensual) | Depende de infraestructura externa |
| **RNF028** | Copias de Seguridad y Recuperación | Implementado y verificado |
| **RNF029** | Protección contra Ataques Web | Implementado y verificado |
| **RNF030** | Optimización de Imágenes y Recursos | Implementado y verificado |
