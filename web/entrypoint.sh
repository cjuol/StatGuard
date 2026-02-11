#!/bin/bash
set -e

PROJECT_DIR="/var/www/html/statguard"
APP_USER_ID="${CHOWN_USER:-33}"  # www-data UID por defecto
APP_GROUP_ID="${CHOWN_GROUP:-33}"  # www-data GID por defecto

# Configurar usuario y grupo de Apache para que coincidan con el host
if [ "$APP_USER_ID" != "33" ] || [ "$APP_GROUP_ID" != "33" ]; then
  echo "🔧 Configurando Apache para correr como UID=$APP_USER_ID GID=$APP_GROUP_ID..."
  
  # Crear grupo si no existe
  if ! getent group appgroup > /dev/null 2>&1; then
    groupadd -g "$APP_GROUP_ID" appgroup
  fi
  
  # Crear usuario si no existe
  if ! id -u appuser > /dev/null 2>&1; then
    useradd -u "$APP_USER_ID" -g "$APP_GROUP_ID" -M -s /bin/bash appuser
  fi
  
  # Configurar Apache para usar este usuario
  export APACHE_RUN_USER=appuser
  export APACHE_RUN_GROUP=appgroup
  
  echo "✅ Apache configurado: usuario=appuser($APP_USER_ID) grupo=appgroup($APP_GROUP_ID)"
else
  export APACHE_RUN_USER=www-data
  export APACHE_RUN_GROUP=www-data
fi

if [ -f "$PROJECT_DIR/composer.json" ] && [ ! -d "$PROJECT_DIR/vendor" ]; then
  echo "Installing Composer dependencies..."
  cd "$PROJECT_DIR" && composer install --no-interaction --prefer-dist
fi

echo "StatGuard environment ready."
if command -v R >/dev/null 2>&1; then
  echo "R engine: Detected (v$(R --version | head -n 1 | awk '{print $3}'))"
else
  echo "R engine: Not installed (normal mode)"
fi
if [ "$#" -eq 0 ]; then
  set -- apache2-foreground
fi

if command -v "$1" >/dev/null 2>&1; then
  exec "$@"
fi

if [ -n "$DEFAULT_COMMAND" ]; then
  read -r -a default_cmd <<< "$DEFAULT_COMMAND"
  exec "${default_cmd[@]}" "$@"
fi

echo "Command not found: $1" >&2
exit 127