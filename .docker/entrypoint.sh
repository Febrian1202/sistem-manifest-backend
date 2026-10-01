#!/bin/sh
set -e

# Only perform web setup (caching & migrations) when starting the primary php-fpm app server
if [ "$1" = "php-fpm" ]; then
    echo "==> Preparing Laravel environment..."

    # Ensure storage and cache permissions are correct
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

    # Sync latest frontend build assets if mounted via external volume
    if [ -d /var/www/html/public-build-source ]; then
        echo "==> Syncing fresh build assets to public volume..."
        mkdir -p /var/www/html/public/build
        cp -rf /var/www/html/public-build-source/* /var/www/html/public/build/ 2>/dev/null || true
    fi

    # Wait for database connection if DB_HOST is configured
    if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" != "sqlite" ]; then
        echo "==> Waiting for database connection ($DB_HOST)..."
        max_tries=30
        count=0
        until php -r '
            $host = getenv("DB_HOST");
            $port = getenv("DB_PORT") ?: "3306";
            $db   = getenv("DB_DATABASE");
            $user = getenv("DB_USERNAME");
            $pass = getenv("DB_PASSWORD");
            try {
                $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass, [
                    PDO::ATTR_TIMEOUT => 2,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                exit(0);
            } catch (\Throwable $e) {
                exit(1);
            }
        ' 2>/dev/null; do
            count=$((count + 1))
            if [ $count -ge $max_tries ]; then
                echo "==> Database connection failed after $max_tries seconds! Continuing anyway..."
                break
            fi
            sleep 1
        done
        if [ $count -lt $max_tries ]; then
            echo "==> Database connection established."
        fi
    fi

    # Create storage symlink if it doesn't exist
    if [ ! -L /var/www/html/public/storage ]; then
        echo "==> Creating storage symlink..."
        php artisan storage:link --no-interaction || true
    fi

    # Run database migrations
    if [ "${AUTORUN_MIGRATIONS:-true}" = "true" ]; then
        echo "==> Running database migrations..."
        php artisan migrate --force --no-interaction || echo "==> Migration failed or already up to date."
    fi

    # Optimize caches for production
    if [ "${AUTORUN_OPTIMIZATIONS:-true}" = "true" ]; then
        echo "==> Caching configuration, routes, and views..."
        php artisan config:cache --no-interaction || true
        php artisan route:cache --no-interaction || true
        php artisan view:cache --no-interaction || true
        php artisan event:cache --no-interaction || true
    fi

    echo "==> Laravel environment ready. Starting PHP-FPM..."
fi

exec "$@"
