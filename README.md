# 사내 인트라넷 (Intranet)

씨네나잇(OTT) 서비스의 회원/영상 데이터를 관리하고, 사내 공지사항과
직원 메일함 조회 기능을 제공하는 내부 전용 웹 애플리케이션입니다.
(학습/테스트 목적)

## 아키텍처 개요

이 서버는 두 개의 DB에 **직접** 접속합니다.

- `intranet_db` — 직원 계정(`employee`), 공지사항(`notice`) 등 인트라넷 자체 데이터
- `member_db` — 씨네나잇(OTT) 회원(`members`)·영상(`videos`) 데이터 (별도 DB 서버, 3308 포트)

씨네나잇 웹서버는 이 두 DB에 직접 접속할 수 없고, 대신 이 인트라넷 서버가
제공하는 `api/*.php` (서버-서버 API, 마스터 토큰 인증)를 통해서만 회원가입/
로그인/영상 조회·등록을 처리합니다. 영상·회원 정보의 **수정/삭제는 API로
제공하지 않으며**, 오직 이 인트라넷 관리자 페이지(`admin/*.php`)에서만
`member_db`에 직접 접근해 처리합니다.

```
┌──────────────────────┐   HTTP + X-API-Token   ┌──────────────────────┐
│   씨네나잇 웹서버       │ ──────────────────────▶ │   이 인트라넷 서버      │
│  (DB 직접 접속 안 함)  │   (사설 IP로 통신)        │  Apache + PHP + PDO   │
└──────────────────────┘                          └──────┬───────────────┘
                                                          │
                                     ┌────────────────────┼────────────────────┐
                                     ▼                    ▼                    ▼
                              intranet_db          member_db (3308)      메일 서버(IMAP)
                              (직원/공지)         (씨네나잇 회원/영상)     (윈도우, 별도 서버)
```

## 폴더 구조

```
intranet/
├─ Dockerfile             웹 컨테이너 이미지 정의 (PHP + pdo_mysql + imap 확장)
├─ docker-compose.yml     실행 설정
├─ .env.example           DB/메일/API 토큰 환경변수 예시 (실제 값은 .env에)
├─ common.php             DB 연결(intranet_db + member_db) + 세션 + 공통 상수
├─ auth.php               로그인 상태·권한(is_logged_in/require_admin) 체크
├─ error.php              공통 에러 페이지
├─ index.php              대시보드 (공지 최근글 + 씨네나잇 회원/영상 수 요약)
├─ login.php / logout.php 직원 로그인/로그아웃
├─ action.php             공지 삭제 등 단발성 액션 처리
├─ notice/                공지사항 목록/상세/작성/수정
├─ admin/
│   ├─ users.php          인트라넷 직원 계정 등급 관리
│   ├─ members.php        씨네나잇 가입 회원 조회 (member_db 직접 SELECT)
│   ├─ videos.php         씨네나잇 영상 목록/삭제 (member_db 직접 접근)
│   └─ video_edit.php     씨네나잇 영상 메타데이터 수정 (member_db 직접 UPDATE)
├─ api/
│   ├─ auth.php           씨네나잇 로그인 인증 대행
│   ├─ join.php           씨네나잇 회원가입 대행
│   ├─ members.php        씨네나잇 마이페이지용 회원 정보 조회 대행
│   ├─ videos.php         씨네나잇 영상 목록/상세 조회 대행 (읽기 전용)
│   └─ videos_upload.php  씨네나잇 영상 등록 대행 (생성 전용 - 수정/삭제 없음)
├─ config/
│   └─ mail.php           사내 메일 서버(IMAP) 접속 설정
├─ mail/                  사내 메일함 조회 기능 (신규)
│   ├─ _lib.php           IMAP 연결/세션 공용 헬퍼
│   ├─ login.php          메일 비밀번호 입력 (인트라넷 로그인과 별개)
│   ├─ inbox.php          받은 메일함 목록
│   ├─ view.php           메일 상세 보기
│   └─ logout.php         메일함 세션 로그아웃
├─ includes/              공통 헤더(사이드바)/푸터
└─ css/                   스타일
```

## 사내 메일 (신규)

직원 메일함은 이 인트라넷 서버가 아니라 **별도의 윈도우 메일 서버**에서
관리됩니다. 인트라넷은 그 메일함을 대신 관리하지 않고, 직원이 접속할 때마다
IMAP으로 직접 조회만 해서 보여주는 "뷰어" 역할만 합니다.

- **로그인 아이디**: 직원의 회사 이메일 주소(`intranet_db.employee.email`)를 그대로 사용합니다.
- **로그인 비밀번호**: 인트라넷 로그인 비밀번호와는 **별개**입니다. `/mail/login.php`에서
  매번 입력받고, 성공하면 PHP 세션에만 잠시 보관합니다 (DB에는 절대 저장하지 않음).
- **접속 방식**: `config/mail.php`의 `MAIL_HOST`/`MAIL_PORT`/`MAIL_ENCRYPTION` 설정으로
  메일 서버에 IMAP(`ext-imap`)으로 접속합니다. 메일 서버 자체(계정 생성, 발신 설정 등)는
  이 저장소에 포함되어 있지 않습니다.
- **기능 범위**: 지금은 **읽기(수신함 조회) 전용**입니다. 메일 작성/발송 기능은 없습니다.
- **본문 표시**: 수신 메일이 HTML이어도 태그를 제거하고 텍스트로만 보여줍니다
  (수신 메일 본문을 그대로 렌더링하면 저장형 XSS 통로가 될 수 있기 때문입니다).

## DB 관계

- `intranet_db`: `employee`(1) — `notice`(N) : 직원 한 명이 여러 공지 작성 가능
- `member_db`: `members`(1) — `inquiry`(N), `members`(1) — `videos`(N, writer_id)

## 실행에 필요한 것

1. **`intranet_db` 접속 정보** (`DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS`)
2. **`member_db` 접속 정보** (`MEMBER_DB_HOST`/`PORT`/`NAME`/`USER`/`PASS`, 3308 포트)
3. **`INTRANET_API_MASTER_TOKEN`** — 씨네나잇 웹서버의 `config/intranet.php`와 반드시 동일한 값
4. **메일 서버 접속 정보** (`MAIL_HOST`/`MAIL_PORT`/`MAIL_ENCRYPTION`/`MAIL_VALIDATE_CERT`) —
   윈도우 메일 서버가 IMAP을 제공하는 주소/포트

## 사전 준비 (최초 서버 세팅 시, Rocky/RHEL/AlmaLinux/CentOS 계열)

Rocky Linux 등 RHEL 계열은 기본적으로 Docker가 설치되어 있지 않고 podman이
기본 컨테이너 런타임입니다. `docker` 명령어가 podman을 흉내만 내는 상태로
빌드/실행하면 각종 에러가 나므로, **이 프로젝트를 내려받기 전에 아래 순서대로
진짜 Docker부터 설치**하세요.

```bash
# 1. podman이 docker 명령어를 가로채고 있다면 제거
sudo dnf remove -y podman-docker

# 2. Docker CE 공식 저장소 추가 (RHEL 계열은 centos용 저장소 사용)
sudo dnf -y install dnf-plugins-core
sudo dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo

# 3. Rocky 10 최소 설치본에 흔히 빠져있는 커널 모듈 설치 후 재부팅
sudo dnf install -y kernel-modules-extra
sudo reboot
```

재부팅 후 재접속해서 계속 진행:

```bash
# 4. Docker Engine + Compose 플러그인 설치
sudo dnf install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
```

> **위 4번 명령이 `Could not connect to server` / `baseos` 관련 에러로 실패하면**,
> 서버 네트워크에서 Rocky 공식 미러(`mirrors.rockylinux.org`)가 막혀있는 경우입니다.
> Docker 설치엔 필요 없는 저장소이니 아래처럼 빼고 설치하세요.
> ```bash
> sudo dnf install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin \
>     --disablerepo=baseos --disablerepo=appstream --disablerepo=extras
> ```

```bash
# 5. 서비스 활성화
sudo systemctl enable --now docker

# 6. 설치 확인 (Server 항목이 "Docker Engine - Community"로 나와야 정상)
docker version
docker compose version
```

`docker version`에 `Emulate Docker CLI using podman...` 문구가 보이거나 `DOCKER_HOST`
환경변수가 `unix:///run/podman/podman.sock`을 가리키고 있다면 아직 podman이 잡고
있는 상태이니, 아래로 확인하고 원인이 되는 rc 파일에서 해당 줄을 지운 뒤 재로그인하세요.

```bash
echo $DOCKER_HOST
grep -rn "DOCKER_HOST" ~/.bashrc ~/.bash_profile ~/.profile \
    /root/.bashrc /root/.bash_profile /root/.profile \
    /etc/environment /etc/profile.d/ 2>/dev/null
```

여기까지 확인됐으면 아래 "Docker로 실행" 단계로 넘어가세요.

## 프로젝트 받기 & 실행

```bash
# 1. 저장소 클론
git clone -b AdminServer https://github.com/sparkmo/Project.git
cd Project

# 2. .env 생성 (저장소엔 실제 값이 없으므로 반드시 채워야 함)
cp .env.example .env
nano .env   # DB_PASS, MEMBER_DB_PASS, INTRANET_API_MASTER_TOKEN 등 "실행에 필요한 것" 항목 채우기

# 3. 빌드 + 백그라운드 실행
sudo docker compose up --build -d

# 4. 상태 확인
docker compose ps
docker compose logs -f intranet
```

정상적으로 뜨면 브라우저에서 `http://<서버 IP>:8001` 접속.

## 자주 막히는 지점 체크리스트

- `docker compose ps`에 컨테이너가 하나도 안 보임 → `.env` 없이 실행한 경우가 많음. 위 2번 단계 확인.
- 이미지 다운로드 중 `Please select an image` 프롬프트가 뜸 → podman 환경일 때 나오는 정상 동작.
  `docker.io/library/...` 항목 선택 (진짜 Docker로 설치했다면 안 뜸).
- 8001 포트 접속이 안 됨 → `docker compose ps`로 컨테이너가 `Up` 상태인지, 서버 방화벽/보안그룹에서
  8001이 열려있는지 확인.

## 보안 참고

- 씨네나잇 ↔ 인트라넷 간 서버-서버 API는 정적 마스터 토큰 하나로만 인증합니다
  (IP 화이트리스트, mTLS, 요청 서명 없음). 이 토큰이 유출되면 `api/members.php`가
  `username` 파라미터 없이 호출될 경우 회원 전체(비밀번호 해시 포함)가 조회될 수
  있습니다. 자세한 내용은 각 `api/*.php` 파일 상단 주석 참고.
- `config/intranet.php`(씨네나잇 쪽) 및 `config/mail.php`(이 서버)는 모두 웹루트 안에
  있으므로, 어느 한쪽 서버가 뚫리면(예: 파일 업로드 취약점) 파일시스템 접근을 통해
  설정값이 노출될 수 있습니다.
- **[VULN-HIGH-4]** `INTRANET_API_MASTER_TOKEN`도 코드에 평문으로 적혀있지 않고
  `.env`/환경변수로만 관리되지만, 공격 벡터가 웹쉘(RCE)이라 실질적 방어가 되지
  못합니다. 웹쉘에서 `getenv()`/`phpinfo()`/`system('env')` 등으로 이 프로세스가
  아는 환경변수를 그대로 조회할 수 있기 때문입니다.
- 메일 비밀번호는 세션에만 잠깐 보관되며 DB에는 저장하지 않지만, 세션 자체가
  탈취되면(세션 하이재킹 등) 그 시점의 메일함 접근 권한도 함께 넘어갈 수 있습니다.
- 로그인 성공 시 `session_regenerate_id()`로 세션 고정 공격을 방지합니다.
- 이 프로젝트는 학습/테스트용 예제이며, 운영 배포 전 추가 보안 점검이 필요합니다.
