ARG BUILD_FROM
FROM ${BUILD_FROM}

# Install required packages
RUN \
    apk add --no-cache \
        nginx \
        php83 \
        php83-fpm \
        php83-pdo \
        php83-pdo_sqlite \
        php83-session \
        php83-sqlite3 \
        php83-json \
        php83-gd && \
    mkdir -p /etc/services.d/nginx && \
    mkdir -p /etc/services.d/php-fpm83 && \
    mkdir -p /var/www/public && \
    mkdir -p /etc/nginx && \
    mkdir -p /etc/php83

# Copy root filesystem and set permissions
COPY --chmod=755 files/general/etc/services.d/nginx/* /etc/services.d/nginx/
COPY --chmod=755 files/php83/etc/services.d/php-fpm83/* /etc/services.d/php-fpm83/
COPY files/general/www/public/* /var/www/public/
COPY files/general/etc/nginx/nginx.conf /etc/nginx/
COPY files/php83/etc/php83/php-fpm.conf /etc/php83/

# Create required directories with correct permissions
RUN \
    mkdir -p /var/www/public/uploads/photos && \
    mkdir -p /var/www/public/uploads/signatures && \
    chown -R nginx:nginx /var/www && \
    chmod -R 755 /var/www/public/uploads

WORKDIR /var/www/public

# Build arguments
ARG BUILD_ARCH
ARG BUILD_DATE
ARG BUILD_DESCRIPTION
ARG BUILD_NAME
ARG BUILD_REF
ARG BUILD_REPOSITORY
ARG BUILD_VERSION

# Labels
LABEL \
    io.hass.name="${BUILD_NAME}" \
    io.hass.description="${BUILD_DESCRIPTION}" \
    io.hass.arch="${BUILD_ARCH}" \
    io.hass.type="addon" \
    io.hass.version=${BUILD_VERSION} \
    maintainer="Mediflower" \
    org.opencontainers.image.title="${BUILD_NAME}" \
    org.opencontainers.image.description="${BUILD_DESCRIPTION}" \
    org.opencontainers.image.vendor="Mediflower Add-ons" \
    org.opencontainers.image.authors="Mediflower" \
    org.opencontainers.image.licenses="MIT" \
    org.opencontainers.image.url="https://github.com/goatboynz/People-Tracker" \
    org.opencontainers.image.source="https://github.com/goatboynz/People-Tracker" \
    org.opencontainers.image.documentation="https://github.com/goatboynz/People-Tracker/blob/main/README.md" \
    org.opencontainers.image.created=${BUILD_DATE} \
    org.opencontainers.image.revision=${BUILD_REF} \
    org.opencontainers.image.version=${BUILD_VERSION}
