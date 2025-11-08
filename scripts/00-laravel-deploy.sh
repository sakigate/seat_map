#!/usr/bin/env bash
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

echo "Caching config..."
php artisan config:cache

echo "Caching routes..."
php artisan route:cache

echo "Running migrations..."
php artisan migrate --force

echo "Installing frontend dependencies..."
npm install

echo "Building frontend assets..."
npm run build

echo "Publishing Livewire assets..."
php artisan livewire:publish --assets

echo "Running migrations with seeds..."
php artisan migrate:fresh --seed --force