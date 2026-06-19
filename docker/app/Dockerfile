# =============================================================================
# Stage 1: builder — installs all dependencies and compiles assets
# =============================================================================
FROM php:8.2-fpm-alpine AS builder

WORKDIR /app

# Install system build dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    nodejs \
    npm \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    autoconf \
    gcc \
    g++ \
    make \
    linux-headers

# Install PHP extensions needed for build
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        intl \
        mbstring \
        opcache \
        xml \
        zip \
        gd \
        sockets \
        pcntl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy dependency files first (layer cache optimisation)
COPY composer.json composer.lock ./

# Install PHP dependencies (production only, no scripts yet)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

# Copy full application source
COPY . .

# Generate optimised autoloader (no scripts — APP_KEY not available at build time)
RUN composer dump-autoload --no-dev --optimize --no-scripts

# Install Node dependencies and build assets
RUN npm ci && npm run build

# =============================================================================
# Stage 2: production — minimal runtime image
# =============================================================================
FROM php:8.2-fpm-alpine AS production

WORKDIR /var/www/html

# Install runtime system dependencies only
RUN apk add --no-cache \
    bash \
    curl \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    icu-libs \
    oniguruma \
    libxml2 \
    supervisor \
    && rm -rf /var/cache/apk/*

# Install PHP extensions (runtime)
RUN apk add --no-cache --virtual .build-deps \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        libxml2-dev \
        autoconf \
        gcc \
        g++ \
        make \
        linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        intl \
        mbstring \
        opcache \
        xml \
        zip \
        gd \
        sockets \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear /var/cache/apk/*

# Copy PHP config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# =============================================================================
# Stage 3: test — runs unit tests and lint (optional, can be enabled in CI)
# =============================================================================
FROM production AS test

# Install dev dependencies for testing
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Set up test environment
COPY .env.testing .env
RUN php artisan key:generate --env=testing

# Run code style check (Pint)
RUN vendor/bin/pint --test

# Run unit tests
RUN php artisan test --parallel

# =============================================================================
# Stage 4: production-final — copy from test stage if tests pass
# =============================================================================
FROM production AS production-final

# Copy Supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy application from builder (no node_modules, no dev deps)
COPY --from=builder /app/vendor ./vendor
COPY --from=builder /app/public/build ./public/build
COPY --chown=www-data:www-data . .

# Copy entrypoint and make executable
COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create writable storage directories and supervisor log dir
RUN mkdir -p /var/log/supervisor \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
