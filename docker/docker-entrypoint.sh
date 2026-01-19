#!/bin/bash

# Generate self-signed SSL certs if mkcert certs don't exist
if [ ! -f /etc/apache2/ssl/localhost.crt ] || [ ! -f /etc/apache2/ssl/localhost.key ]; then
    echo "No SSL certs found, generating self-signed certificates..."
    mkdir -p /etc/apache2/ssl
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout /etc/apache2/ssl/localhost.key \
        -out /etc/apache2/ssl/localhost.crt \
        -subj "/C=US/ST=Local/L=Local/O=Dev/CN=localhost" \
        -addext "subjectAltName=DNS:localhost,IP:127.0.0.1" \
        -addext "basicConstraints=CA:FALSE" \
        -addext "keyUsage=digitalSignature,keyEncipherment"
    echo "Self-signed certificates generated."
else
    echo "Using existing SSL certificates."
fi

# Start Apache
exec apache2-foreground
