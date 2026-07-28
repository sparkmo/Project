FROM php:8.2-apache

# intranet_db, member_db 둘 다 PDO(MySQL)로 직접 접속하므로 pdo_mysql이 필요합니다.
#
# 참고: 사내 메일(IMAP) 조회 기능(mail/*.php)은 PHP의 ext-imap을 쓰는데,
# 이 확장이 의존하는 uw-imap(c-client) 패키지가 최근 Debian(trixie)에서 완전히
# 삭제되어 설치가 까다로워졌습니다 (libc-client2007e-dev 부재). 지금은 이 기능을
# 빼고 빌드하며, mail/*.php 쪽 코드는 imap 확장이 없어도 에러 메시지만 보여주고
# 정상 동작하도록 이미 방어적으로 작성되어 있습니다 (mail/login.php의
# function_exists('imap_open') 체크 참고).
#
# 나중에 메일 기능이 필요해지면, php:8.2-apache-bookworm 베이스로 바꾸고 아래를
# 추가하면 됩니다:
RUN apt-get install -y --no-install-recommends libc-client2007e-dev libkrb5-dev \
      && ln -sf /usr/lib/x86_64-linux-gnu/libc-client2007e.a /usr/lib/x86_64-linux-gnu/libc-client.a \
      && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
      && docker-php-ext-install imap

RUN apt-get update && apt-get install -y --no-install-recommends \
        libc-client2007e-dev \
        libkrb5-dev \
    && rm -rf /var/lib/apt/lists/* \
    && ln -sf /usr/lib/x86_64-linux-gnu/libc-client2007e.a /usr/lib/x86_64-linux-gnu/libc-client.a \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install imap
    
RUN docker-php-ext-install pdo_mysql

COPY . /var/www/html/

EXPOSE 80
