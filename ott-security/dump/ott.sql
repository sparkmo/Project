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
    role       ENUM('user','admin') NOT NULL DEFAULT 'user',  -- 이 줄 추가
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
    video_id     BIGINT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(100) NOT NULL,
    description  TEXT,
    thumbnail    VARCHAR(255),
    category     VARCHAR(50) NOT NULL DEFAULT 'general',
    video_type   ENUM('youtube','file','url') NOT NULL DEFAULT 'youtube',  -- 추가
    video_source VARCHAR(255),                                             -- 추가
    upload_date  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 

-- 시나리오용 관리자 계정 (인트라넷 employee.admin01 = a@hi.xyz 와 동일 인물 → 이메일 통일)
INSERT INTO member (login_id, password, nickname, email, role)
VALUES ('ott_admin_gh', '$2b$10$XOkYDvu./S0eqQf0AQcY7.NVNBm8Jh4iuOkKZp1rnf3Ck6m5BTkD2', '관리자', 'a@hi.xyz', 'admin');

--기존 계정 중 하나를 관리자로 지정하고 싶다면:
--UPDATE member SET role='admin' WHERE login_id='기존_아이디';

-- 더미 일반 회원 (비밀번호는 모두 'cine1234', password_hash()로 미리 해싱해둠 → 실제 로그인 테스트 가능)
INSERT INTO member (login_id, password, nickname, email, role) VALUES
('honggd',  '$2b$10$VpEwo1rcSBAAUMJPLG6la.2HE.AXUCOLmKvzJ1oNTHBVX4QvXMAmW', '길동이',   'honggd@example.com',  'user'),
('kimyuna', '$2b$10$UUT6y7khik7C8jax6jBR7.fd6aSL/AXECSQofpgGHwI8qwBpg08.O', '유나킴',   'kimyuna@example.com', 'user'),
('parkjs',  '$2b$10$o.Iscn5rmKucS3Rc11wPg.e7dHNTHzi3W6t2i7fJbHSTAaX8h1CJy', '박제이',   'parkjs@example.com',  'user'),
('leesm',   '$2b$10$/ZXhoQH6lBcMViesX8T0vOFYFiIYk4/Eri7UT2eDAuX1i9FCnLDe2', '이수민',   'leesm@example.com',   'user');
 
--기존 계정 중 하나를 관리자로 지정하고 싶다면:
--UPDATE member SET role='admin' WHERE login_id='기존_아이디';
 
-- 더미 영상 (WebServer의 CATEGORIES 키: general/movie/drama/ent/anime 와 맞춰둠)
INSERT INTO video (title, description, thumbnail, category, video_type, video_source) VALUES
('샘플 영상',        '테스트용 샘플 영상입니다.',       '/uploads/sample.jpg',  'general', 'youtube', 'dQw4w9WgXcQ'),
('심연의 도시',      '느와르풍 스릴러 영화.',           '/uploads/no-thumb.png','movie',   'youtube', 'dQw4w9WgXcQ'),
('우리 집 이야기',   '가족 드라마 1화 파일럿.',         '/uploads/no-thumb.png','drama',   'youtube', 'dQw4w9WgXcQ'),
('웃음 폭탄 토크쇼', '게스트와 함께하는 예능 토크쇼.',   '/uploads/no-thumb.png','ent',     'url',     'https://example.com/videos/talkshow.mp4'),
('별빛 소년단',      '판타지 배경 애니메이션 1화.',      '/uploads/no-thumb.png','anime',   'file',    'sample.mp4'),
('오늘의 브이로그',  '일상 브이로그 콘텐츠.',            '/uploads/no-thumb.png','general', 'youtube', 'dQw4w9WgXcQ');


-- 더미 문의 (member_id는 위에서 만든 회원 순서 기준: 1=admin, 2=honggd, 3=kimyuna, 4=parkjs, 5=leesm)
INSERT INTO inquiry (member_id, title, content, status) VALUES
(2, '재생이 안 돼요',           '영상이 로딩만 되고 재생이 안 됩니다. 확인 부탁드려요.',            '대기'),
(3, '결제 관련 문의',            '구독 결제가 중복으로 나간 것 같습니다.',                          '대기'),
(4, '자막 요청',                 '해당 영상에 한글 자막 추가해주실 수 있나요?',                     '처리완료'),
(5, '계정 잠금 문의',            '비밀번호를 여러 번 틀려서 로그인이 안 됩니다.',                   '대기'),
(2, '콘텐츠 추가 요청',          '애니메이션 카테고리에 더 많은 작품이 있었으면 좋겠어요.',          '처리완료');
