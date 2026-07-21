# 씨네나잇 (CineNight)

티빙 스타일의 OTT 클론 웹사이트 예제입니다. (학습/테스트 목적)

## 아키텍처 개요

이 웹서버는 **DB에 직접 접속하지 않습니다.** 회원(로그인/가입/마이페이지)과
영상(목록/상세/등록) 데이터는 전부 별도의 **인트라넷 서버 API**를 통해서만
주고받습니다. 영상 수정/삭제 API는 아예 제공되지 않으며, 인트라넷 관리자
페이지에서만 처리됩니다 — 이 웹서버가 뚫려서 토큰이 유출되더라도 회원가입/
조회/영상 등록 이상은 할 수 없도록 하는 것이 이 구조의 의도입니다.

```
┌──────────────────────┐   HTTP + X-API-Token   ┌──────────────────────┐
│   씨네나잇 웹 컨테이너   │ ──────────────────────▶ │   인트라넷 서버 (별도)  │
│   Apache + PHP        │   (VPN 사설 IP로 통신)   │   회원/영상 API        │
└──────────────────────┘                          └──────────────────────┘
        │
        ▼
   uploads/ (로컬 디스크, 직접 업로드된 영상 파일만 저장 — 메타데이터는 API로 등록)
```

이 저장소에는 인트라넷 서버 코드가 포함되어 있지 않습니다. 로그인/가입/
영상목록까지 실제로 동작시키려면 그 API 서버가 별도로 떠 있어야 합니다.

## 폴더 구조

```
cinenight-levelhigh/
├─ Dockerfile             웹 컨테이너 이미지 정의 (PHP + curl 확장)
├─ docker-compose.yml     실행 설정
├─ .env.example           INTRANET_API_TOKEN 등 환경변수 예시 (실제 값은 .env에)
├─ common.php             세션 시작 + 공통 상수 + 인트라넷 API 호출 헬퍼
├─ config/
│   └─ intranet.php       인트라넷 API 엔드포인트 + 토큰 설정
├─ auth.php               로그인 상태·권한(is_login/is_admin/can_edit) 체크
├─ error.php              alert 후 리다이렉트/뒤로가기 헬퍼
├─ index.php              메인 페이지 (히어로 슬라이더 + 카테고리별 영상)
├─ list.php               전체 목록 (카테고리 필터 / 검색 / 페이지네이션)
├─ view.php               영상 상세 보기 (조회수 증가는 인트라넷 API가 처리)
├─ write.php              영상 업로드 (관리자 전용, 파일은 로컬 저장 + 메타데이터는 API 등록)
├─ join.php               회원가입 (인트라넷 API로 위임)
├─ login.php              로그인 (인트라넷 API로 인증 위임)
├─ logout.php             로그아웃
├─ mypage.php             마이페이지
├─ admin/
│   └─ member_lookup.php  관리자 전용 가입자 조회 (인트라넷 API 호출)
├─ includes/
│   ├─ header.php         공통 상단 네비게이션
│   └─ footer.php         공통 하단 푸터
├─ css/                   다크테마 스타일
└─ uploads/               직접 업로드(video_type=file) 영상 파일 저장 위치
```

## 영상 등록 방식 (video_type)

영상은 3가지 방식으로 유연하게 저장됩니다.

| video_type | video_source |
|---|---|
| youtube | 유튜브 영상 ID (URL 입력해도 자동 추출) |
| file | `uploads/` 폴더에 저장된 파일명 (이 웹서버가 직접 저장) |
| url | 외부에서 재생 가능한 영상 URL |

`file` 방식만 실제로 이 컨테이너의 디스크에 파일이 저장되고, 나머지는 인트라넷
API에 메타데이터(문자열)만 전달됩니다.

## 실행에 필요한 것

1. **접근 가능한 인트라넷 API 서버** — `config/intranet.php`의 `INTRANET_HOST`가
   가리키는 주소에 회원/영상 API가 떠 있어야 합니다.
2. **`INTRANET_API_TOKEN` 환경변수** — `.env.example`을 복사해 `.env`를 만들고
   실제 토큰 값을 채우세요. 이 값은 절대 커밋하지 않습니다 (`.gitignore` 참고).
3. **`uploads/` 디렉토리 쓰기 권한** — 컨테이너 안 `www-data`가 쓸 수 있어야
   직접 업로드 기능이 동작합니다.

## 사전 준비 (Ubuntu/AWS EC2, 최초 1회)

Ubuntu는 Rocky/RHEL 계열과 달리 podman이 기본으로 깔려있지 않아서 훨씬 간단합니다.

```bash
# 1. 필요한 패키지 + Docker 공식 GPG 키 등록
sudo apt update
sudo apt install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

# 2. Docker 공식 저장소 추가
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update

# 3. Docker Engine + Compose 플러그인 설치
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# 4. 서비스 활성화
sudo systemctl enable --now docker

# 5. sudo 없이 docker 쓰려면 현재 계정을 docker 그룹에 추가
sudo usermod -aG docker $USER
```

5번 실행 후에는 **로그아웃 후 재접속**(또는 `newgrp docker`)해야 그룹 권한이 적용됩니다.

```bash
docker version
docker compose version
```

두 명령 다 정상 출력되면 준비 완료입니다.

> **AWS 보안그룹(Security Group) 확인 필수** — 이 앱은 8000번 포트를 씁니다.
> EC2 인스턴스의 보안그룹 인바운드 규칙에 **TCP 8000번 포트**(테스트 중엔 내 IP만,
> 필요하면 0.0.0.0/0)가 열려있지 않으면, 컨테이너가 멀쩡히 떠 있어도 외부에서
> 접속이 안 됩니다. 서버 안에서 `docker compose ps`로 컨테이너 상태 확인하는 것과는
> 별개의 문제이니 꼭 같이 확인하세요.

## 프로젝트 받기 & 실행

```bash
# 1. 소스 받기 (git clone 또는 zip 업로드 후 압축 해제)
cd ~
# git clone -b <브랜치명> <repo주소>.git
cd Project-WebServer

# 2. .env 생성 (인트라넷 서버 쪽 INTRANET_API_MASTER_TOKEN과 반드시 동일한 값으로)
cp .env.example .env
vi .env   # INTRANET_API_TOKEN 값 채우기

# 3. 빌드 + 백그라운드 실행
docker compose up --build -d

# 4. 상태 확인
docker compose ps
docker compose logs -f web
```

정상적으로 뜨면 `http://<EC2 퍼블릭 IP>:8000` 접속.

> **인트라넷 API 서버가 아직 로컬에 없다면**: `config/intranet.php`의
> `INTRANET_HOST`(기본값 `http://192.168.20.10`)로 실제 접속이 안 되면, 로그인/
> 가입/영상목록처럼 인트라넷 API를 호출하는 기능은 전부 "인트라넷 서버에
> 연결할 수 없습니다" 에러가 뜹니다. 인트라넷 서버(별도 저장소)를 같은 서버에
> Docker로 같이 띄웠다면, `docker-compose.yml` 하단에 주석 처리된 `intranet` 서비스
> 블록을 참고해서 두 프로젝트를 같은 docker 네트워크로 묶고 `INTRANET_HOST`를
> 그 서비스명(예: `http://intranet-api`)으로 바꿔주세요.

## 자주 막히는 지점 체크리스트

- `docker compose ps`엔 `Up`인데 브라우저 접속이 안 됨 → AWS 보안그룹 8000번 포트 확인
- 로그인/가입 시도 시 "인트라넷 서버에 연결할 수 없습니다" → `INTRANET_HOST`가
  가리키는 주소에 인트라넷 API가 실제로 떠 있고, 이 서버에서 네트워크로 닿는지 확인
  (`docker compose exec web curl -v <INTRANET_HOST>/api/auth.php`)
- 로그인/가입은 되는데 매번 실패("토큰 불일치" 류) → `.env`의 `INTRANET_API_TOKEN`이
  인트라넷 서버 `.env`의 `INTRANET_API_MASTER_TOKEN`과 정확히 같은 값인지 확인

## 보안 참고

- `config/intranet.php`는 웹 루트 안에 있으므로, 이 서버가 뚫리면(예: 파일 업로드
  취약점) 파일시스템 접근을 통해 노출될 수 있습니다. 토큰은 최소 권한으로 발급하고
  주기적으로 회전시키는 것을 권장합니다.
- **[VULN-HIGH-4]** `INTRANET_API_TOKEN`은 코드에 평문으로 적혀있지 않고 `.env`/환경변수로만
  관리되지만, 공격 벡터가 웹쉘(RCE)이라 실질적인 방어가 되지 못합니다. 웹쉘에서
  `getenv('INTRANET_API_TOKEN')` 한 줄이나 `phpinfo()`, `system('env')` 등으로 이
  프로세스가 아는 환경변수를 그대로 조회할 수 있기 때문입니다. 진짜 문제는 토큰이
  코드에 있는지 env에 있는지가 아니라, 웹쉘(RCE) 자체를 막지 못했다는 점입니다.
- 로그인 성공 시 `session_regenerate_id()`로 세션 고정 공격을 방지합니다.
- 이 프로젝트는 학습/테스트용 예제이며, 운영 배포 전 추가 보안 점검이 필요합니다.
