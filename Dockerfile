FROM php:8.2-apache-bookworm

# intranet_db, member_db 둘 다 PDO(MySQL)로 직접 접속하므로 pdo_mysql이 필요합니다.
RUN docker-php-ext-install pdo_mysql

# 사내 메일(IMAP) 조회 기능(mail/*.php)에 필요한 ext-imap 설치.
# uw-imap(c-client) 패키지는 최신 Debian(trixie)에서는 빠졌지만,
# bookworm 베이스에는 libc-client2007e-dev가 아직 남아있어 여기서는 설치 가능합니다.

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        libc-client2007e-dev \
        libkrb5-dev && \
    ln -sf /usr/lib/x86_64-linux-gnu/libc-client2007e.a \
           /usr/lib/x86_64-linux-gnu/libc-client.a && \
    docker-php-ext-configure imap \
        --with-kerberos \
        --with-imap-ssl && \
    docker-php-ext-install imap && \
    rm -rf /var/lib/apt/lists/*


COPY . /var/www/html/

EXPOSE 80
