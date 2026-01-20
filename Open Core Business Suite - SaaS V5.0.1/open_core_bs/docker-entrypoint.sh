#!/bin/bash
set -e

echo "🚀 Starting OpenCore SaaS Application..."

# Wait for database to be ready
echo "⏳ Waiting for database..."
until php artisan db:show 2>/dev/null; do
    echo "Database not ready yet, waiting..."
    sleep 2
done

echo "✅ Database is ready!"

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate --force

# Clear and cache config
echo "⚙️  Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link if not exists
if [ ! -L public/storage ]; then
    echo "🔗 Creating storage symlink..."
    php artisan storage:link
fi

# Set proper permissions
echo "🔐 Setting permissions..."
chown -R sail:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Start queue worker in background if needed
if [ "$QUEUE_CONNECTION" != "sync" ]; then
    echo "🔧 Starting queue worker..."
    php artisan queue:work --daemon &
fi

# Start scheduler in background
echo "⏰ Starting task scheduler..."
(while true; do php artisan schedule:run >> /dev/null 2>&1; sleep 60; done) &

echo "✅ OpenCore SaaS is ready!"

# Execute the main command
exec "$@"
