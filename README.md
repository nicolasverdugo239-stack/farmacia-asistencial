# Sistema de Gestión de Farmacia Asistencial

Proyecto Integrado · Ingeniería en Ciberseguridad · INACAP Temuco
Laravel 12 · MySQL 8 (datos) · MongoDB 7 (auditoría) · Tailwind CSS

---

## 1. Instalación con Docker (recomendada)

Necesitas **Docker Desktop** abierto. Nada más: PHP, Composer, MySQL, MongoDB y Node corren dentro de contenedores.

**Windows:** abre PowerShell en esta carpeta y ejecuta:

```powershell
powershell -ExecutionPolicy Bypass -File .\instalar.ps1
```

**macOS / Linux:**

```bash
./instalar.sh
```

La primera vez tarda entre 5 y 10 minutos (descarga Laravel y las imágenes). Al terminar abre **http://localhost:8000**.
El contenedor local usa ocho workers del servidor PHP para atender solicitudes concurrentes. Si cambias `docker-compose.yml`, recrea la aplicación con `docker compose up -d --force-recreate app`.

| Usuario | Rol | Contraseña |
|---|---|---|
| admin@farmacia.test | Administrador | `Farmacia#Demo2026` |
| tecnico@farmacia.test | Técnico farmacéutico | `Farmacia#Demo2026` |
| quimico@farmacia.test | Químico farmacéutico | `Farmacia#Demo2026` |

Recetas de ejemplo: folio **10452** (Losartán, GES) y **10453** (Clonazepam, controlado).

Comandos útiles:

```bash
docker compose up -d                              # encender
docker compose down                               # apagar
docker compose run --rm app php artisan migrate:fresh --seed   # reiniciar datos de demo
```

## 2. Instalación sin Docker (Laragon / XAMPP)

1. PHP 8.2+ con las extensiones `pdo_mysql` y **`mongodb`** (en Windows: descarga `php_mongodb.dll` para tu versión de PHP desde https://github.com/mongodb/mongo-php-driver/releases, cópiala en `ext/` y agrega `extension=mongodb` al `php.ini`).
2. MySQL con una base `farmacia` y MongoDB Community Server corriendo.
3. En una carpeta vacía:
   ```bash
   composer create-project laravel/laravel:^12.0 farmacia
   ```
4. Copia **todo el contenido** de `codigo/` dentro de `farmacia/` (reemplazar archivos) y borra `tests/Feature/ExampleTest.php` y `tests/Unit/ExampleTest.php`.
5. Dentro de `farmacia/`:
   ```bash
   composer require spatie/laravel-permission:^6.0 mongodb/laravel-mongodb:^5.0
   composer require --dev larastan/larastan:^3.0
   php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
   cp .env.example .env        # ajusta DB_PASSWORD si corresponde
   php artisan key:generate
   php artisan migrate:fresh --seed
   npm install && npm run build
   php artisan serve
   ```

## 3. Pruebas

| Tipo | Herramienta | Comando |
|---|---|---|
| Unitarias + integración | PHPUnit | `docker compose run --rm app php artisan test` |
| Solo un grupo | PHPUnit | `docker compose run --rm app php artisan test --filter=ControladosTest` |
| Rendimiento | k6 | `docker run --rm -i -e BASE_URL=http://host.docker.internal:8000 grafana/k6 run - < src/tests/performance/carga.js` |
| Análisis estático | Larastan | `docker compose run --rm app ./vendor/bin/phpstan analyse` |
| Dependencias vulnerables | Composer | `docker compose run --rm app composer audit` |
| Manuales | Checklist | `docs/pruebas-manuales.md` |

Las pruebas automáticas usan SQLite en memoria y guardan la auditoría en memoria (`AUDIT_DRIVER=array`), así que no necesitan MySQL ni MongoDB.

**Qué cubren** (51 casos):

- `Unit/RutTest` · validación del dígito verificador, normalización y enmascarado.
- `Unit/HashLibroTest` · el hash del libro es determinista y cambia si se altera cualquier campo.
- `Feature/FefoTest` · se sugiere el lote que vence primero, sin vencidos ni lotes con stock insuficiente.
- `Feature/DispensacionTest` · descuento de stock, FEFO, receta vencida, doble dispensación, datos del tercero, RUT cifrado, alerta de stock, técnico no dispensa controlados.
- `Feature/ControladosTest` · firma con contraseña, técnico/admin no firman, **detección de alteración directa en la BD**, libro inmodificable, alertas de descuadre.
- `Feature/SeguridadTest` · login, usuario inactivo, bloqueo tras 5 intentos, **matriz de permisos por rol**, sesión cerrada al desactivar, cabeceras de seguridad, política de contraseñas, auditoría sin datos personales.
- `Feature/CarroTest` · consumo por sección, alerta del carro, reposición desde bodega (FEFO).
- `Feature/RemesaTest` · recepción, lote vencido, controlados solo por QF, rechazo de entradas maliciosas.

## 4. Dónde está cada cosa

| Qué | Archivo |
|---|---|
| Modelo de datos (tablas, PK, FK, índices) | `database/migrations/2026_09_29_*` |
| Matriz de roles y permisos | `database/seeders/RolesPermisosSeeder.php` |
| Rutas protegidas por permiso | `routes/web.php` |
| FEFO | `app/Services/FefoService.php` |
| Dispensación (transacción + bloqueo) | `app/Services/DispensacionService.php` |
| Libro de controlados con hash encadenado | `app/Services/LibroControladosService.php` |
| Auditoría en MongoDB | `app/Services/AuditoriaService.php`, `app/Models/AuditLog.php`, `app/Models/Concerns/Auditable.php` |
| Cifrado de datos del paciente | `app/Models/Paciente.php`, `app/Models/RetiroTercero.php` |
| Validación de RUT | `app/Rules/Rut.php` |
| Login con bloqueo por intentos | `app/Http/Requests/LoginRequest.php` |
| Validación de entradas | `app/Http/Requests/*` |
| Cabeceras de seguridad | `app/Http/Middleware/SecurityHeaders.php` |
| Política de contraseñas | `app/Providers/AppServiceProvider.php` |
| Tareas diarias (vencimientos, integridad del libro) | `routes/console.php`, `app/Console/Commands/*` |

## 5. Estándar de codificación segura aplicado

| # | Regla | Dónde se ve en el código |
|---|---|---|
| 1 | Toda entrada pasa por un Form Request | `app/Http/Requests/*` (incluye RUT con DV y listas blancas por regex) |
| 2 | Nada de SQL concatenado | Solo Eloquent / Query Builder con parámetros |
| 3 | Salida escapada | Las vistas usan solo `{{ }}`; no hay `{!! !!}` |
| 4 | Autorización en el servidor | Middleware `permission:` en cada ruta + `authorize()` en cada Form Request |
| 5 | Asignación masiva controlada | `$fillable` explícito en todos los modelos |
| 6 | Secretos fuera del código | Solo en `.env` (que no se sube al repositorio) |
| 7 | Datos personales cifrados y fuera de los logs | Casts `encrypted`, `$hidden` y enmascarado `[protegido]` en la auditoría |
| 8 | Movimientos de stock atómicos | `DB::transaction` + `lockForUpdate()` en todos los servicios |
| 9 | Errores genéricos en producción | `APP_DEBUG=false`; mensaje de login que no revela si el correo existe |
| 10 | Análisis estático y dependencias | Larastan nivel 5 y `composer audit` |

## 6. Pendiente / mejoras futuras

- Segundo factor (2FA) para Administrador y QF con Laravel Fortify.
- Respaldo automático: `mysqldump` diario + `mongodump`, copiados fuera del servidor.
- Lectura de código de barras (HU13), exportar PDF/Excel (HU08) e impresión de rótulos (RF15): el modelo de datos ya los soporta.
- Mensajes de validación de Laravel en español (paquete `laravel-lang/lang`).
