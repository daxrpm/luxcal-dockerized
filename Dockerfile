FROM php:8.2-apache

# OS libs + PHP extension build deps
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libsqlite3-dev \
    libicu-dev \
    pkg-config \
    unzip \
    curl \
    ghostscript \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure pdo_sqlite --with-pdo-sqlite=/usr \
    && docker-php-ext-install -j"$(nproc)" gd zip exif mbstring pdo_sqlite mysqli pdo_mysql intl \
    && docker-php-ext-enable intl \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

# PHP runtime hardening & timezone
RUN { \
    echo "date.timezone = America/Guayaquil"; \
    echo "expose_php = Off"; \
    echo "upload_max_filesize = 16M"; \
    echo "post_max_size = 16M"; \
    echo "memory_limit = 256M"; \
    } > /usr/local/etc/php/conf.d/app.ini

# Apache hardening
RUN { echo 'ServerTokens Prod'; echo 'ServerSignature Off'; } \
    > /etc/apache2/conf-available/security-hardening.conf \
    && a2enconf security-hardening

# Copy calendar code (already unzipped into your repo)
COPY luxcal_src/ /var/www/html/

# Non web-accessible data dir for SQLite + writable ownership
RUN mkdir -p /data && chown -R www-data:www-data /var/www/html /data

# Minimal security headers
RUN { \
    echo '<IfModule mod_headers.c>'; \
    echo '  Header always set X-Content-Type-Options "nosniff"'; \
    echo '  Header always set X-Frame-Options "SAMEORIGIN"'; \
    echo '  Header always set Referrer-Policy "no-referrer-when-downgrade"'; \
    echo '  Header always set X-XSS-Protection "1; mode=block"'; \
    echo '</IfModule>'; \
    } > /etc/apache2/conf-available/headers-security.conf \
    && a2enconf headers-security

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=5 \
    CMD curl -fsS http://localhost/ || exit 1

EXPOSE 80
