FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# 코드에서 실제로 쓰는 확장자는 curl 뿐입니다 (common.php의 intranet_api_get/post).
# DB(mysqli/PDO)는 직접 붙지 않으므로 관련 확장은 필요 없습니다.
RUN docker-php-ext-install curl

# 소스 복사
COPY . /var/www/html/

# uploads/ 폴더는 write.php(save_uploaded_video)가 런타임에 파일을 써야 하므로
# www-data가 쓰기 가능해야 합니다.
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80
