# Instala el Sistema de Gestión de Farmacia Asistencial con Docker Desktop (Windows / PowerShell).
# Uso: clic derecho > "Ejecutar con PowerShell", o en una terminal:  .\instalar.ps1
$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

function Paso($texto) { Write-Host "`n==> $texto" -ForegroundColor Cyan }
function Ejecutar { & docker @args; if ($LASTEXITCODE -ne 0) { throw "Falló: docker $args" } }

Paso '1/8 Construyendo la imagen PHP'
Ejecutar compose build app

if (-not (Test-Path 'src/artisan')) {
    Paso '2/8 Descargando Laravel 12'
    New-Item -ItemType Directory -Force -Path src | Out-Null
    Ejecutar compose run --rm --no-deps app composer create-project 'laravel/laravel:^12.0' . --no-interaction --prefer-dist
} else {
    Paso '2/8 Laravel ya estaba descargado en src/'
}

Paso '3/8 Copiando el código del sistema'
Copy-Item -Path 'codigo/*' -Destination 'src' -Recurse -Force
Copy-Item -Path 'codigo/.env.example' -Destination 'src/.env.example' -Force
# Las pruebas de ejemplo de Laravel esperan la página de bienvenida, que este sistema no usa.
Remove-Item -Force -ErrorAction SilentlyContinue 'src/tests/Feature/ExampleTest.php', 'src/tests/Unit/ExampleTest.php', 'src/resources/views/welcome.blade.php'

Paso '4/8 Instalando dependencias (roles, MongoDB, análisis estático)'
Ejecutar compose run --rm --no-deps app composer require 'spatie/laravel-permission:^6.0' 'mongodb/laravel-mongodb:^5.0' --no-interaction
Ejecutar compose run --rm --no-deps app composer require --dev 'larastan/larastan:^3.0' --no-interaction
Ejecutar compose run --rm --no-deps app php artisan vendor:publish '--provider=Spatie\Permission\PermissionServiceProvider' --no-interaction

Paso '5/8 Configurando .env para Docker'
$envDocker = (Get-Content 'codigo/.env.example') `
    -replace '^DB_HOST=.*', 'DB_HOST=mysql' `
    -replace '^MONGODB_URI=.*', 'MONGODB_URI=mongodb://mongo:27017'
# UTF-8 sin BOM (compatible con Windows PowerShell 5.1 y PowerShell 7).
[System.IO.File]::WriteAllLines((Join-Path $PSScriptRoot 'src/.env'), $envDocker, (New-Object System.Text.UTF8Encoding $false))

Paso '6/8 Levantando MySQL y MongoDB'
Ejecutar compose up -d mysql mongo
$env:MYSQL_PWD = 'farmacia_local'
try {
    $mysqlDeadline = (Get-Date).AddMinutes(2)
    do {
        Start-Sleep -Seconds 2
        Write-Host -NoNewline '.'
        docker compose exec -T -e MYSQL_PWD mysql mysqladmin ping -h 127.0.0.1 -ufarmacia --silent *> $null
        $mysqlReady = $LASTEXITCODE -eq 0
    } while (-not $mysqlReady -and (Get-Date) -lt $mysqlDeadline)
} finally {
    Remove-Item Env:MYSQL_PWD -ErrorAction SilentlyContinue
}
if (-not $mysqlReady) {
    throw 'MySQL no estuvo disponible después de 2 minutos. Revisa los contenedores con docker compose logs mysql.'
}
Write-Host ''

Paso '7/8 Creando tablas y datos de demostración'
Ejecutar compose run --rm app php artisan key:generate --force
Ejecutar compose run --rm app php artisan migrate:fresh --seed --force

Paso '8/8 Compilando estilos y levantando el sistema'
Ejecutar compose run --rm node sh -c 'npm install && npm run build'
Ejecutar compose up -d app

Write-Host @'

Listo. Abre http://localhost:8000

Usuarios de demostración (contraseña: Farmacia#Demo2026)
  admin@farmacia.test    Administrador
  tecnico@farmacia.test  Técnico farmacéutico
  quimico@farmacia.test  Químico farmacéutico

Pruebas:  docker compose run --rm app php artisan test
'@ -ForegroundColor Green
