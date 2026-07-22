-- AdminServer(intranet 앱) 호환을 위한 최소 보정
-- 실제 운영 덤프(intranet_db.sql)에는 없지만, 앱 기능(가입일 표시 / 활동 로그)에
-- 필요해서 여기서 추가합니다. employee/notice 기존 데이터는 건드리지 않습니다.

USE intranet_db;

-- admin/users.php 목록에서 "가입일"로 표시하는 컬럼 (덤프에는 없어 앱 배포 시점으로 채움)
ALTER TABLE employee
    ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER email;

-- auth.php의 log_action()이 기록하는 활동 로그 테이블
CREATE TABLE IF NOT EXISTS access_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT NULL,
    ip         VARCHAR(45) DEFAULT '',
    action     VARCHAR(50) NOT NULL,
    target     VARCHAR(255) DEFAULT '-',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES employee(employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
