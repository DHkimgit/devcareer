# 웹 프로그래밍(CSE430) 텀 프로젝트
## 프로젝트 기획 의도
개발자 지망생이 취업 준비 과정에서 필요한 다양한 기록을 한 곳에 정리할 수 있는 웹사이트를 만드는 것을 목표로 한다. 여러 플랫폼에 흩어져 있는 프로젝트, 블로그, 채용 공고, 수강 정보, 자기소개서, 이력서 등을 통합하여 관리의 편리함을 제공한다.

## 프로젝트 목표 중점
- PHP 언어 숙달
- 데이터베이스 테이블 설계
- tailwind CSS 라이브러리 학습
- 아파치 웹 서버의 이해
- 화면 설계 및 구현
- 클라우드 배포 (오라클 클라우드)

## 프로젝트 사용 기술
| 분류       | 기술 스택              |
|------------|------------------------|
| 사용 언어   | <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white"/> <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=JavaScript&logoColor=white"/> |
| 라이브러리 & 프레임워크 | <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white"/> |
| DB         | <img src="https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white"/>                  |
| 웹 서버     | Apache                 |
| 클라우드     | <img src="https://img.shields.io/badge/Oracle-F80000?style=for-the-badge&logo=oracle&logoColor=black"/>                 |

- **PHP, JavaScript** : 웹 서버를 구축하기 위한 기본적인 구현 언어로 사용.
- **tailwindcss** : tailwindcss는 자바스크립트 진영에서 사용할 수 있는 유틸리티 css 프레임워크로 HTML의 태그 class 파라미터에 정해진 값을 부여하여 별도의 css 파일을 만들 필요 없이 해당 태그에 css를 부여할 수 있다. 개발 생산성이 증가하며 tailwindcss의 문법에 익숙해 진다면 유지보수성 향상도 기대할 수 있다.
- **MySQL** : 프로젝트의 도메인을 구현하기 위해선 서버에 데이터를 저장할 필요가 생긴다. MySQL은 대표적으로 많이 사용하는 DBMS의 일종으로 문법이 표준에 가까우며 쉽게 배울 수 있다. 이번 프로젝트를 개발하면서 27개의 테이블을 구성했다.
- **Apache** : 개발한 서버를 웹으로 배포하기 위해 사용하는 http 서버다. 작성한 .php 파일을 배포해주는 역할을 담당한다.

## 데이터베이스 테이블
![image](https://github.com/user-attachments/assets/25896622-2925-4666-999f-33349eda3f61)

| 테이블                  | 도메인       | 역할                                                                 |
|------------------------|--------------|--------------------------------------------------------------------|
| users                  | 서비스 공통   | 회원가입, 로그인 기능의 핵심 테이블. 사용자 정보 저장                                    |
| projects               | 프로젝트     | 프로젝트 도메인 핵심 테이블. 개인 프로젝트 정보 저장.                                   |
| blog_post              | 블로그       | 블로그 도메인 핵심 테이블. 프로젝트 테이블 id를 fk로 가지며, 프로젝트 게시글 정보 저장.      |
| blog_scrap             | 블로그       | 블로그 도메인 핵심 테이블. 스크랩한 게시글 저장.                                     |
| job_posting            | 채용공고     | 채용 공고 도메인 핵심 테이블. 관심 채용 정보 저장.                                    |
| college_course         | 수강정보     | 수강 정보 도메인 핵심 테이블. 수강 중인 학교 수업 정보 저장.                             |
| college_assignment     | 수강정보     | 수강 정보 도메인 핵심 테이블. 수강 중인 학교 수업의 과제물 데이터 저장.                   |
| cover_letter_question  | 자기소개서    | 자기소개서 도메인 핵심 테이블. 플랫폼에서 제공하는 공통 질문을 저장. 서비스 운영 측에서 데이터 입력해야 함. |
| cover_letter_question_category | 자기소개서 | 자기소개서 질문의 유형을 저장하는 테이블.                                          |
| cover_letter_answer    | 자기소개서    | 자기소개서 도메인 핵심 테이블. 공통 질문에 대한 사용자별 답변을 저장.                    |
| cover_letter_custom_question | 자기소개서 | 자기소개서 도메인 핵심 테이블. 사용자가 직접 자기소개서 질문 항목을 저장.                |
| cover_letter_custom_answer | 자기소개서 | 자기소개서 도메인 핵심 테이블. 사용자가 등록한 질문에 대한 답변을 저장.                  |
| algorithm_category     | 알고리즘     | 백준 사이트의 알고리즘 카테고리 정보를 크롤링해와서 정보를 저장한 테이블.                 |
| boj_problem            | 알고리즘     | 알고리즘 도메인 핵심 테이블. 사용자가 백준 사이트에서 푼 문제의 정보를 저장.              |
| programmers_problem    | 알고리즘     | 알고리즘 도메인 핵심 테이블. 사용자가 프로그래머스 사이트에서 푼 문제의 정보를 저장.         |
| boj_problem_category   | 알고리즘     | boj_problem과 algorithm_category의 N:M 연관관계를 해소하기 위한 매핑 테이블.            |
| resume_user_profile    | 이력서       | 이력서 도메인 핵심 테이블. 이력서의 사용자 관련 간단한 정보를 저장.                      |
| resume_user_channel    | 이력서       | 이력서 도메인 핵심 테이블. 이력서에 삽입되는 사용자의 연락 채널 주소 관련 정보 저장.         |
| resume_work_experience | 이력서       | 이력서 도메인 핵심 테이블. 이력서에 삽입되는 사용자 경력 관련 정보 저장.                  |
| resume_skill_reference | 이력서       | 이력서 스킬 관련 정보를 크롤링해서 저장. 배지 이미지 url 데이터를 포함.                   |
| resume_skill_category  | 이력서       | 이력서 스킬 관련 카테고리 정보를 저장. resume_skill_reference 에서 fk로 사용.            |
| resume_skill           | 이력서       | 이력서 도메인 핵심 테이블. 사용자별 이력서 스킬을 매핑. 1:M 관계로 resume_skill_reference을 fk로 가진다. |
| resume_project         | 이력서, 프로젝트 | 이력서 도메인 핵심 테이블. 이력서에 삽입되는 프로젝트 정보를 projects 테이블과 연동하기 위한 N:M 연관관계 해소 매핑 테이블. |
| project_technology_stack | 프로젝트 | 프로젝트 도메인에서 제공하는 ‘사용 기술’ 정보를 저장하기 위한 테이블.                     |
| project_technology_mapping | 프로젝트 | 프로젝트 도메인에서 사용자의 ‘사용 기술’ 정보를 저장하기 위한 N:M 연관관계 해소 매핑 테이블. |

- 개발의 편의성을 위해 테이블의 외래키에 직접적인 FK 제약조건을 설정하지 않았음. 외래키로 사용하는 컬럼에 대해서 인덱스를 추가하여 조회 성능에 문제가 없도록 함

## 요구사항 정의
| 도메인       | 요구사항                                                                                   |
|--------------|-------------------------------------------------------------------------------------------|
| 프로젝트     | 사용자는 웹 사이트의 프로젝트 페이지에서 자신이 진행한 프로젝트의 이름, 상태, 설명, 시작일, 종료일, 진행률, 사용기술, 역할, 프로젝트 저장소 URL, 데모 URL, 집중 이슈를 기록하고 저장할 수 있다. |
| 프로젝트     | 사용자는 프로젝트의 사용 기술을 입력할 때 C, C#, C++, DART 등 여러가지 프로그래밍 언어를 복합적으로 선택할 수 있다.                          |
| 프로젝트     | 사용자는 웹 사이트의 프로젝트 페이지에서 자신이 입력했던 프로젝트 정보를 확인할 수 있다.                                  |
| 프로젝트     | 사용자는 웹 사이트의 프로젝트 페이지에서 자신이 입력했던 프로젝트 정보를 수정 및 삭제 할 수 있다.                              |
| 블로그       | 사용자는 웹 사이트의 블로그 페이지에서 프로젝트 게시글과 스크랩 게시글을 등록할 수 있다.                                  |
| 블로그       | 사용자는 웹 사이트의 블로그 페이지에서 자신이 등록한 프로젝트 게시글과 스크랩 게시글을 확인할 수 있다.                          |
| 블로그       | 사용자는 웹 사이트의 블로그 페이지에서 자신이 등록한 스크랩 게시글의 링크를 통해 스크랩 한 사이트로 바로 이동할 수 있다.                   |
| 블로그       | 사용자는 웹 사이트의 블로그 페이지에서 새 프로젝트 게시글을 등록할 때, 자신이 프로젝트 도메인에서 작성했던 프로젝트를 선택할 수 있다.         |
| 블로그       | 사용자는 웹 사이트의 블로그 페이지에서 새 프로젝트 게시글을 등록할 때, 제목, 내용, 태그를 입력할 수 있다.                         |
| 블로그       | 사용자는 웹 사이트의 블로그 페이지에서 스크랩 게시글을 등록할 때, 제목, 게시글 URL, 메모, 태그를 입력할 수 있다.                       |
| 블로그       | 사용자는 웹 사이트의 블로그 페이지에서 프로젝트 게시글의 목록 제목을 입력하여 내용을 확인할 수 있다.                            |
| 채용 공고     | 사용자는 웹 사이트의 채용 공고 페이지에서 자신이 등록한 채용 공고의 회사명, 채용 직군, 기술 스택, 우대 사항, 채용 과정, 채용 마감일, 코딩테스트 정보, 공고 URL, 메모, 등록일을 확인할 수 있다. |
| 채용 공고     | 사용자는 웹 사이트의 채용 공고 페이지에서 새 채용 공고 등록하기 버튼을 눌러서 회사명, 채용 직군, 기술 스택, 우대 사항, 채용 과정, 채용 마감일, 코딩테스트 정보, 공고 URL, 메모, 등록일을 저장할 수 있다. |
| 채용 공고     | 사용자는 웹 사이트의 채용 공고 페이지에서 공고 URL을 눌러서 입력했던 채용 공고로 바로 이동할 수 있다.                           |
| 수강 정보     | 사용자는 수강 정보 페이지에서 자신이 지금까지 등록한 수강 수업 정보의 전체 학점 평균을 4.5학점 기준으로 확인할 수 있다.                   |
| 수강 정보     | 사용자는 수강 정보 페이지의 수강 수업 정보 목록 컴포넌트에서 수업명, 교수명, 수업 위치, 수업 분야, 학기, 시험 정보, 최종 학점을 확인할 수 있다. |
| 수강 정보     | 사용자는 수강 정보 페이지의 수강 수업 정보 목록 컴포넌트에서 수업 추가하기 버튼을 눌러 수업명, 교수명, 수업 위치, 수업 분야, 학기, 시험 정보, 최종 학점을 입력하여 수업을 추가할 수 있다. |
| 수강 정보     | 사용자는 수강 정보 페이지의 과제물 목록 컴포넌트에서 과제명, 관련 수업, 마감일, 제출 여부, 관련 링크/경로 를 확인할 수 있다.                 |
| 수강 정보     | 사용자는 수강 정보 페이지의 과제물 목록 컴포넌트에서 과제물 추가하기 버튼을 눌러서 과제명, 관련 수업, 마감일, 제출 여부, 관련 링크/경로 를 추가할 수 있다. |
| 자기소개서    | 사용자는 자기소개서 페이지에서 자신이 사이트에서 제공하는 질문에 대해서 답변한 기록을 확인하고 이를 수정, 삭제할 수 있다.                   |
| 자기소개서    | 사용자는 자기소개서 페이지에서 자신이 직접 기관/회사명, 카테고리, 질문, 답변을 입력하여 자기소개서 질문을 추가할 수 있다.                   |
| 자기소개서    | 사용자는 자기소개서 페이지에서 자신이 추가한 커스텀 질문을 확인하고 이에 대해 답변할 수 있다.                                 |
| 자기소개서    | 사용자는 자기소개서 페이지에서 자신이 추가한 커스텀 질문을 삭제할 수 있다.                                             |
| 자기소개서    | 사용자는 자기소개서 페이지에서 사이트에서 제공하는 질문의 회사, 유형, 질문, 카테고리를 확인하고 이에 대한 답변을 작성할 수 있다.             |
| 알고리즘      | 사용자는 알고리즘 페이지에서 현재 자신이 등록한 푼 문제의 개수를 확인할 수 있다.                                         |
| 알고리즘      | 사용자는 알고리즘 페이지에서 최근 문제 해결 날짜를 확인할 수 있다.                                                 |
| 알고리즘      | 사용자는 알고리즘 페이지에서 자신이 등록한 백준 알고리즘 문제에 대한 정보를 카드 형태로 확인할 수 있다.                         |
| 알고리즘      | 사용자는 알고리즘 페이지에서 문제 번호, 제목, 요약, 풀이, 문제링크, 문제 카테고리를 지정하여 백준 문제를 등록할 수 있다.               |
| 알고리즘      | 사용자는 알고리즘 페이지에서 자신이 등록한 프로그래머스 알고리즘 문제에 대한 정보를 카드 형태로 확인할 수 있다.                     |
| 알고리즘      | 사용자는 알고리즘 페이지에서 문제 ID, 제목, 요약, 풀이, 레벨, 문제 링크를 입력하여 프로그래머스 문제를 등록할 수 있다.                 |
| 이력서        | 사용자는 이력서 페이지에서 이름, 이메일, 연락처, 간단한 자기소개를 입력하여 이력서를 생성할 수 있다.                             |
| 이력서        | 사용자는 이력서 페이지의 채널 컴포넌트에서 플랫폼 이름과 URL을 추가하여 연락 채널, 사이트를 저장하고 확인할 수 있다.                   |
| 이력서        | 사용자는 이력서 페이지의 경력사항 컴포넌트에서 회사명, 직책, 시작일, 종료일, URL, 주요 업무, 성과를 입력하여 경력을 추가하고 수정할 수 있다. |
| 이력서        | 사용자는 이력서 페이지의 경력사항 컴포넌트에서 자신이 입력한 경력 사항들의 요약을 확인 할 수 있다.                             |
| 이력서        | 사용자는 이력서 페이지의 기술스택 컴포넌트에서 https://github.com/Envoy-VC/awesome-badges 에서 제공하는 기술스택을 지정하고 추가할 수 있다. |
| 이력서        | 사용자는 이력서 페이지의 기술스택 컴포넌트에서 자신이 선택한 기술스택을 배지 이미지 형태로 확인할 수 있다.                         |
| 이력서        | 사용자는 이력서 페이지의 개인 프로젝트 컴포넌트에서 자신이 프로젝트 페이지에서 등록했던 프로젝트를 불러 와서 표기할 수 있다.             |
| 이력서        | 사용자는 이력서 페이지의 개인 프로젝트 컴포넌트에서 자신이 프로젝트 페이지에서 등록했던 프로젝트의 표시 여부와 순서를 결정할 수 있다.       |
| 회원가입      | 사용자는 회원가입 페이지에서 이메일, 이름, 비밀번호, 비밀번호 확인을 입력하여 회원가입할 수 있다.                             |
| 회원가입      | 사용자는 회원가입 페이지에서 성공적으로 회원가입이 되었다면 로그인 페이지로 이동할 수 있다.                                 |
| 회원가입      | 사용자는 회원가입 페이지에서 우측 하단 버튼을 클릭하여 바로 로그인 페이지로 이동할 수 있다.                                 |
| 로그인        | 사용자는 로그인 페이지에서 이메일과 비밀번호를 입력하여 로그인 할 수 있다.                                             |
| 로그인        | 사용자가 로그인 페이지에서 로그인에 실패할 경우 ‘이메일 또는 비밀번호가 잘못되었습니다’ 문구가 출력된다.                       |
| 로그인        | 사용자가 로그인 페이지에서 로그인에 성공한 경우 세션을 /main 페이지로 이동할 수 있다.                                     |
| 메인 페이지    | 사용자는 메인 페이지에서 서비스에 대한 간략한 소개가 담긴 컴포넌트를 통해 각 서비스 페이지로 이동할 수 있다.                     |
| 네비바        | 사용자는 페이지 상단의 네비바를 통해 각 서비스 페이지로 이동할 수 있다.                                                 |
| 네비바        | 사용자는 페이지 상단의 네비바에서 자신의 로그인 정보를 확인할 수 있으며 버튼을 눌러서 로그아웃 할 수 있다.                       |
| 네비바        | 사용자가 페이지 상단 네비바에서 로그아웃 버튼을 누를 경우 메인 페이지로 이동한다.                                       |
| 사이드바      | 사용자는 좌측 사이드바를 통해 각 서비스 페이지로 이동할 수 있다.                                                     |

## 프로젝트 배포
- 오라클 클라우드 VM.Standard.E2.1.Micro(1core, 1gb) 환경에서 배포
- 아파치2, PHP 8.1, mysql 설치

### 1. 오라클 클라우드 컴퓨팅 인스턴스 생성
![image](https://github.com/user-attachments/assets/410ba8d0-b5e8-4370-a781-2aa91600d417)
- 인스턴스 페이지에서 인스턴스 생성 버튼을 클릭한다.

![image](https://github.com/user-attachments/assets/ac4924d3-ecef-42a3-9d73-f77d739e4b5c)

- 개발 편의를 위해 리눅스 os는 우분투를 사용했다. 구성의 경우 오라클 클라우드 프리티어에서 무료로 생성 가능한 E2.1 Micro를 선택한다. 1코어의 cpu와 1gb 메모리로 구성되어 있다.

![image](https://github.com/user-attachments/assets/9bc306a0-1082-4731-a81e-c04fb1360ba5)
- AWS의 Virtual Private Cloud (VPC)와 같은 역할을 하는 가상 클라우드 네트워크가 자동으로 생성된다. 이미 다른 인스턴스에서 만들어 놓은 VNIC가 존재하므로 그것을 사용하도록 했다.


![image](https://github.com/user-attachments/assets/525eee5f-566e-4d3c-b99d-55810bf9b31a)
- 컴퓨팅 인스턴스에 SSH 프로토콜을 이용해서 간편하게 접속하기 위해 SSH private key를 다운로드 받아야 한다.


### 2. 오라클 클라우드 컴퓨팅 인스턴스 접속
![image](https://github.com/user-attachments/assets/b06f83fc-4268-42a3-8c58-aad0752ea70d)

- 인스턴스가 생성되면 인스턴스 세부 정보에 public IP를 확인할 수 있다. 해당 IP 주소를 이용해서 인스턴스에 SSH 방식으로 접속이 가능하다.
- 터미널 프로그램은 termius 프로그램을 사용했다. 먼저 인스턴스 생성시 다운로드 받았던 SSH private key를 등록한다.
- SSH의 기본 포트는 22 이다. 오라클 클라우드의 경우 우분투 os를 사용해서 인스턴스를 생성한 경우 초기 접속 시 사용자 이름은 ubuntu로 설정되어 있고 비밀번호는 입력하지 않아도 된다. 키의 경우 앞서 등록한 private key를 사용하도록 설정해주면 된다.

### 3. 배포 환경 설정
```bash
sudo apt update
sudo apt install apache2

sudo apt-get install php
sudo apt install mysql-server -y
```
- 패키지 매니저를 업데이트 시켜준 뒤 아파치와 PHP, mysql을 설치한다.
- 새 프로그램 모두 윈도우 로컬 개발 환경에서 latest 버전을 설치했으므로 버전 호환성에 대한 고려 없이 자동으로 설치하도록 했다.

![image](https://github.com/user-attachments/assets/9b93fea4-18e5-46dd-94a0-8803899279dc)
```bash
sudo iptables -L --line
```
- iptables는 Linux 시스템에서 네트워크 패킷을 필터링하고 전달하는 규칙을 설정하고 관리하는 데 사용된다.
- iptables에서 'Chain INPUT'란에 REJECT된 부분을 삭제해서 비활성화를 제거한 뒤, 포트를 추가해서 열어줘야 한다.

```bash
sudo iptables -D INPUT 6
sudo iptables -A INPUT -m state --state NEW -p tcp --dport 80 -j ACCEPT 
sudo iptables -A INPUT -m state --state NEW -p tcp --dport 443 –j ACCEPT
sudo iptables -A INPUT -m state --state NEW -p tcp --dport 3306 -j ACCEPT
sudo netfilter-persistent save
```
- 80(http, 아파치), 443(https), 3306(mysql) 포트를 사용하므로 해당하는 포트를 열어준다.
- 서버가 재시작 되면 방화벽 설정이 초기화 될 수 있으므로 변경 사항을 iptables 구성 파일에 저장한다.


![image](https://github.com/user-attachments/assets/a923595c-2654-4371-a602-0dd010459cc0)

- 인스턴스-서브넷-보안목록-수신규칙 추가 에서 사용해야 하는 포트를 추가했다.
