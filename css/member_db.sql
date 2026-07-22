-- member_db 스키마
-- members, videos 는 admin/members.php, admin/videos.php, api/*.php 코드에서 역추적한 구조입니다.

CREATE TABLE IF NOT EXISTS members (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nickname      VARCHAR(50) NOT NULL,
    email         VARCHAR(100) NOT NULL,
    phone         VARCHAR(20) DEFAULT NULL,
    grade         ENUM('admin','user') NOT NULL DEFAULT 'user',
    reg_date      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login    DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS videos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    writer_id    INT NOT NULL,
    title        VARCHAR(200) NOT NULL,
    description  TEXT,
    video_type   ENUM('youtube','file','url') NOT NULL DEFAULT 'youtube',
    video_source VARCHAR(500) NOT NULL,
    category     VARCHAR(50) NOT NULL DEFAULT 'general',
    view_count   INT NOT NULL DEFAULT 0,
    reg_date     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (writer_id) REFERENCES members(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- README에 언급된 members(1)-inquiry(N) 관계용 테이블 (코드에서 직접 쓰이진 않지만 참고용으로 포함)
CREATE TABLE IF NOT EXISTS inquiry (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    member_id  INT NOT NULL,
    title      VARCHAR(200) NOT NULL,
    content    TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 샘플 회원 (비밀번호: test1234, bcrypt 해시는 PHP password_hash() 호환)
INSERT INTO members (username, password_hash, nickname, email, phone, grade)
VALUES ('tester', '$2b$10$XOkYDvu./S0eqQf0AQcY7.NVNBm8Jh4iuOkKZp1rnf3Ck6m5BTkD2', '테스터', 'tester@example.com', '010-1234-5678', 'user');

INSERT INTO videos (writer_id, title, description, video_type, video_source, category)
VALUES (1, '샘플 영상', '테스트용 샘플 영상입니다.', 'youtube', 'dQw4w9WgXcQ', 'drama');
