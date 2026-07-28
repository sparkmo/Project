FROM php:8.2-apache-bookworm

# PDO(MySQL) 및 IMAP 확장 설치
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libc-client2007e-dev \
        libkrb5-dev && \
    ln -sf /usr/lib/x86_64-linux-gnu/libc-client2007e.a \
           /usr/lib/x86_64-linux-gnu/libc-client.a && \
    docker-php-ext-install pdo_mysql && \
    docker-php-ext-configure imap \
        --with-kerberos \
        --with-imap-ssl && \
    docker-php-ext-install imap && \
    rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/

EXPOSE 80
