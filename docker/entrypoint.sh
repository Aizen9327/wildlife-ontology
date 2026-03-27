#!/bin/bash
# Railway injects PORT env var. Apache must listen on that port.
PORT="${PORT:-80}"

# Update Apache ports
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/*:80/*:${PORT}/" /etc/apache2/sites-available/000-default.conf

exec apache2-foreground
