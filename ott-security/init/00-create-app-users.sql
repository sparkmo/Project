-- 앱 접속용 계정 생성/권한 부여
-- (덤프 파일들은 CREATE DATABASE + 테이블/데이터만 있고 계정은 만들지 않으므로 별도 처리)

CREATE DATABASE IF NOT EXISTS intranet_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS ott          CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'roror'@'%' IDENTIFIED BY 'rororpass';
GRANT ALL PRIVILEGES ON intranet_db.* TO 'roror'@'%';

CREATE USER IF NOT EXISTS 'member_admin'@'%' IDENTIFIED BY 'memberpass';
GRANT ALL PRIVILEGES ON ott.* TO 'member_admin'@'%';

FLUSH PRIVILEGES;
