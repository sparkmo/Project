# DB 서버 (intranet_db + ott)

AdminServer(인트라넷 앱)가 붙는 실제 DB 서버입니다. `intranet_db`, `ott` 두 데이터베이스를
이 서버 한 대가 서비스하며, MariaDB `server_audit` 플러그인으로 모든 CONNECT/QUERY를
`ott-security/logs/audit.log`에 감사 로그로 남깁니다 (`ott-security/conf/audit.cnf`).

## 실행 (docker run 방식)

이 서버는 `chaning1004/ott_db:v2` 이미지를 받아서 `docker run`으로 띄웁니다.
(참고: 저장소에 있는 `docker-compose.yml`은 공식 `mariadb:11` 이미지 + 덤프 자동
임포트 방식의 대안입니다. 아래 방식을 쓸 거면 compose는 무시하세요.)



```bash
# 1. 이미지 받기
docker pull chaning1004/ott_db:v2

# 2. 컨테이너 실행
docker run -itd \
  --name ott_db \
  -e MARIADB_ROOT_PASSWORD='zhfldk26#' \
  -e MARIADB_DATABASE=ott \
  -v ott-db-data:/var/lib/mysql \
  -v /docker/ott-security/conf/audit.cnf:/etc/mysql/conf.d/audit.cnf:ro \
  -v /docker/ott-security/logs:/var/log/mariadb \
  chaning1004/ott_db:v2
```

- `MARIADB_ROOT_PASSWORD` : root 비밀번호
- `MARIADB_DATABASE=ott` : 최초 기동 시 빈 `ott` 데이터베이스 자동 생성
  (이미지 안에 이미 데이터가 구워져 있다면 이 옵션은 사실상 무의미 — 상관없음)
- `ott-db-data` : DB 파일이 쌓이는 named volume (컨테이너 삭제해도 데이터 유지)
- `audit.cnf` : 감사로그(server_audit) 설정, 읽기전용 마운트
- `logs` : `audit.log`가 실제로 쌓이는 호스트 폴더

### 확인해야 할 것

- **`ott_db:v2` 이미지에 `ott`/`intranet_db` 데이터가 이미 들어있는지** 먼저
  확인하세요. 들어있다면 아래 3, 4번은 생략 가능합니다.
  ```bash
  docker exec -it ott_db mariadb -uroot -p'zhfldk26#' -e "SHOW DATABASES;"
  ```
- **`intranet_db`가 없다면** 직접 넣어야 합니다 (`MARIADB_DATABASE`는 `ott`만 만듦):
  ```bash
  docker exec -i ott_db mariadb -uroot -p'zhfldk26#' < ott-security/dump/intranet_db.sql
  ```
- **앱 전용 계정이 없다면** (지금은 `root`로만 접속 가능한 상태) 아래처럼 만들어서
  AdminServer `.env`의 `DB_USER`/`MEMBER_DB_USER`와 맞추세요:
  ```bash
  docker exec -i ott_db mariadb -uroot -p'zhfldk26#' < ott-security/init/00-create-app-users.sql
  ```
- **AdminServer 호환용 컬럼/테이블**(`employee.created_at`, `access_logs`)이 필요하면:
  ```bash
  docker exec -i ott_db mariadb -uroot -p'zhfldk26#' < ott-security/init/03-adminserver-app-support.sql
  ```
- **접속 포트**: 위 `docker run`에 `-p` 옵션이 없어서 컨테이너 내부(3306)만 열려있고
  호스트에서는 접속이 안 됩니다. AdminServer가 이 컨테이너 밖(다른 서버/호스트)에서
  접속해야 한다면 `-p 3306:3306 -p 3308:3306`를 추가하세요.


## 폴더 구조

```
ott-security/
├─ conf/
│   ├─ audit.cnf         MariaDB server_audit 플러그인 설정 (감사 로그 활성화)
│   └─ ott_backup.sql    (참고용 백업 원본 — init 스크립트로 쓰지 않음)
├─ init/
│   ├─ 00-create-app-users.sql        앱 접속 계정/권한 생성
│   ├─ 03-adminserver-app-support.sql AdminServer 앱 호환용 보정(컬럼/테이블 추가)
│   └─ (01/02는 ../dump 의 실제 덤프를 compose에서 바로 마운트)
├─ dump/
│   ├─ intranet_db.sql   실제 운영 덤프 (employee, notice)
│   └─ ott.sql           실제 운영 덤프 (users — email/phone/password/name 암호화 저장)
├─ keys/cl.key           (용도 확인 필요 — 아래 "보안 참고" 참조)
└─ logs/                 audit.log 등 감사 로그 (컨테이너와 바인드 마운트)
```

## 스키마 차이 주의

이 서버의 실제 덤프는 AdminServer 코드가 원래 가정하던 스키마와 다릅니다
(예: `users`→`employee`, `member_db`→`ott`, 컬럼명 상이). AdminServer 쪽 `auth.php`,
`admin/users.php`, `common.php`는 이 실제 스키마에 맞게 수정했지만, **회원/영상
관리(`admin/members.php`, `admin/videos.php`, `api/*.php`)는 아직 미반영**입니다.
`ott.sql`에는 `videos` 테이블 자체가 없고, `users` 테이블의 email/phone/password/name은
`varbinary`로 암호화되어 있어(아마 `keys/cl.key`가 그 키) 그대로 조회하면 바이너리
그대로 노출됩니다. 이 부분은 별도로 진행해야 합니다.

## 보안 참고 (학습/테스트용 예제)

- `ott-security/keys/cl.key`, `ott-security/conf/ott_backup.sql`이 웹/앱 서버가 아닌
  DB 서버 자체에, 그것도 평문 설정 폴더 안에 같이 있다는 것 자체가 이 프로젝트가
  다루는 취약점 시나리오의 일부일 수 있습니다 (DB 서버가 뚫리면 암호화 키와 백업
  덤프가 동시에 노출). 운영 환경이라면 키/백업/설정을 물리적으로 분리 보관하세요.
- `employee.password`는 평문 저장입니다(bcrypt 아님) — AdminServer의 로그인 로직도
  이에 맞춰 평문 비교로 수정했습니다. 실제 서비스라면 반드시 해시로 전환해야 합니다.
