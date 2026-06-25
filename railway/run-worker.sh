#!/usr/bin/env sh
set -eu

php artisan queue:work database --sleep=3 --tries=3 --timeout=90
