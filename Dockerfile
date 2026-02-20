FROM hyperf/hyperf:8.1-alpine-v3.16-swoole

LABEL maintainer="Ananiaslitz <ananias@example.com>"

WORKDIR /opt/www

RUN apk update && apk add --no-cache \
    git \
    unzip \
    libstdc++ \
    php81-zip \
    && rm -rf /var/cache/apk/*

COPY . /opt/www

CMD ["php", "tests/example_usage.php"]
