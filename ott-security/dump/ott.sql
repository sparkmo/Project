-- ott_db 스키마 (ERD 설계도 기준 최종본)
-- member(1) - inquiry(N), video는 회원과 무관계(독립)
 
CREATE DATABASE IF NOT EXISTS ott CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ott;
 
DROP TABLE IF EXISTS inquiry;
DROP TABLE IF EXISTS video;
DROP TABLE IF EXISTS member;
 
CREATE TABLE member (
    member_id  BIGINT AUTO_INCREMENT PRIMARY KEY,       -- 회원 고유 번호
    login_id   VARCHAR(30)  NOT NULL UNIQUE,             -- 로그인 ID (중복 불가)
    password   VARCHAR(255) NOT NULL,                    -- 암호화(해시)된 비밀번호
    nickname   VARCHAR(30)  NOT NULL,                    -- 닉네임
    email      VARCHAR(100) NOT NULL,                    -- 이메일
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP  -- 가입일
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
CREATE TABLE inquiry (
    inquiry_id BIGINT AUTO_INCREMENT PRIMARY KEY,        -- 문의 번호
    member_id  BIGINT NOT NULL,                          -- 회원 번호 (FK)
    title      VARCHAR(100) NOT NULL,                    -- 문의 제목
    content    TEXT NOT NULL,                             -- 문의 내용
    status     ENUM('대기','처리완료') NOT NULL DEFAULT '대기',  -- 처리 상태
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,      -- 작성일
    FOREIGN KEY (member_id) REFERENCES member(member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
CREATE TABLE video (
    video_id    BIGINT AUTO_INCREMENT PRIMARY KEY,       -- 영상 번호
    title       VARCHAR(100) NOT NULL,                   -- 영상 제목
    description TEXT,                                    -- 영상 설명
    thumbnail   VARCHAR(255),                             -- 썸네일 경로
    category    VARCHAR(50) NOT NULL DEFAULT 'general',   -- 장르
    upload_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP -- 등록일
    -- 회원과 직접 관계 없음 (writer_id 등 FK 없음)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- 샘플 데이터 (테스트용, 비밀번호는 bcrypt 해시 -> 원문: test1234)
INSERT INTO member (login_id, password, nickname, email)
VALUES ('tester', '$2b$10$XOkYDvu./S0eqQf0AQcY7.NVNBm8Jh4iuOkKZp1rnf3Ck6m5BTkD2', '테스터', 'tester@example.com');
 
INSERT INTO video (title, description, thumbnail, category)
VALUES ('샘플 영상', '테스트용 샘플 영상입니다.', '/uploads/sample.jpg', 'general');
