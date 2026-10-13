#!/usr/bin/env bash
# Instala el Sistema de Gestión de Farmacia Asistencial con Docker (Linux / macOS / Git Bash).
# Uso: ./instalar.sh
set -euo pipefail
cd "$(dirname "$0")"

paso() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }

paso "1/8 Construyendo la imagen PHP"
docker compose build app

if [ ! -f src/artisan ]; then
  paso "2/8 Descargando Laravel 12"
  mkdir -p src
  docker compose run --rm --no-deps app composer create-project laravel/laravel:^12.0 . --no-interaction --prefer-dist
else
  paso "2/8 Laravel ya estaba descargado en src/"
fi

paso "3/8 Copiando el código del sistema"
cp -R codigo/. src/
# Las pruebas de ejemplo de Laravel esperan la página de bienvenida, que este sistema no usa.
rm -f src/tests/Feature/ExampleTest.php src/tests/Unit/ExampleTest.php src/resources/views/welcome.blade.php

paso "4/8 Instalando dependencias (roles, MongoDB, análisis estático)"
docker compose run --rm --no-deps app composer require spatie/laravel-permission:^6.0 mongodb/laravel-mongodb:^5.0 --no-interaction
docker compose run --rm --no-deps app composer require --dev larastan/larastan:^3.0 --no-interaction
docker compose run --rm --no-deps app php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --no-interaction

paso "5/8 Configurando .env para Docker"
cp codigo/.env.example src/.env
sed -i.bak -e 's/^DB_HOST=.*/DB_HOST=mysql/' -e 's#^MONGODB_URI=.*#MONGODB_URI=mongodb://mongo:27017#' src/.env
rm -f src/.env.bak

paso "6/8 Levantando MySQL y MongoDB"
docker compose up -d mysql mongo
until docker compose exec -T mysql mysqladmin ping -h 127.0.0.1 -ufarmacia -pfarmacia_local --silent >/dev/null 2>&1; do
  printf '.'; sleep 2
done
echo

paso "7/8 Creando tablas y datos de demostración"
docker compose run --rm app php artisan key:generate --force
docker compose run --rm app php artisan migrate:fresh --seed --force

paso "8/8 Compilando estilos y levantando el sistema"
docker compose run --rm node sh -c "npm install && npm run build"
docker compose up -d app

cat <<'EOF'

Listo. Abre http://localhost:8000

Usuarios de demostración (contraseña: Farmacia#Demo2026)
  admin@farmacia.test    Administrador
  tecnico@farmacia.test  Técnico farmacéutico
  quimico@farmacia.test  Químico farmacéutico

Pruebas:  docker compose run --rm app php artisan test
EOF
