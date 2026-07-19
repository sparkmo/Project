FROM php:8.2-apache

# intranet_db, member_db 둘 다 PDO(MySQL)로 직접 접속하므로 pdo_mysql이 필요하고,
# 사내 메일(윈도우 메일 서버, IMAP) 조회 기능(mail/*.php)을 위해 imap 확장도 설치합니다.
# imap 확장은 시스템 라이브러리(libc-client, kerberos)가 먼저 설치되어 있어야 빌드됩니다.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libc-client-dev libkrb5-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install pdo_mysql imap

COPY . /var/www/html/

EXPOSE 80
