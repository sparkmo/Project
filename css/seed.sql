-- 초기 관리자 계정 (아이디: admin / 비밀번호: admin123)
-- 비밀번호 해시는 PHP password_hash()와 호환되는 bcrypt 해시입니다.
INSERT INTO users (username, password_hash, name, dept, email, grade)
VALUES ('admin', '$2b$10$hudt29ufKHKChNd.YYPMaO9DaAuWsrB1SvEAqApGFqapca9Eumj8O', '관리자', '경영지원팀', 'admin@example.com', 'admin');
