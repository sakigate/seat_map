#!/usr/bin/env bash
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "Caching configuration..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Caching views..."
php artisan view:cache

echo "Publishing Livewire assets..."
php artisan livewire:publish --assets

echo "Running migrations..."
php artisan migrate --force --seed

echo "Installing frontend dependencies..."
npm install

echo "Building frontend assets..."
npm run build