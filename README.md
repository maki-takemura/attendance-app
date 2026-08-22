# coachtech 勤怠管理アプリ

ユーザーの勤怠と管理を目的とする勤怠管理アプリです。

## 作成者

竹村 麻紀

## 使用技術（実行環境）

### バックエンド

- PHP 8.2
- Laravel 10.x
- Laravel Fortify（認証）
- Laravel Sanctum（公開API認証・応用機能）

### データベース

- MySQL 8.4

### フロントエンド

- Blade
- CSS
- JavaScript
- Vite

> ※Bladeテンプレート・CSS・JavaScriptは提供された完成品を使用します。

### 開発環境

- Docker
- Laravel Sail
- phpMyAdmin
- Mailpit

## ER図

追記予定

## URL

- 開発環境：[http://localhost](http://localhost)
- phpMyAdmin：[http://localhost:8080](http://localhost:8080)
- Mailpit：[http://localhost:8025](http://localhost:8025)

## 動作環境

- Docker
- Docker Compose

> ※Windowsの場合はWSL2の利用を推奨します。

## 環境構築

### 1. リポジトリをクローン

以下のコマンドで任意のディレクトリにリポジトリをクローンします。

```bash
git clone リポジトリURL
cd ディレクトリ名
```

### 2. envファイルの準備

`.env.example`をコピーして`.env`を作成します。

```bash
cp .env.example .env
```

`.env`ファイル内の以下のDB接続情報を確認・設定します。  
Sailを使用するため、以下のように変更してください。

```bash
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

メール設定を以下のように変更してください。

```bash
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

### 3. Composer依存パッケージのインストール

初回セットアップ時は`vendor`ディレクトリが存在しないため、以下のDockerコマンドで`composer install`を実行します。

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    composer:latest \
    composer install --ignore-platform-reqs
```

### 4. Laravel Sailの起動

以下のコマンドでDockerコンテナを起動します。

```bash
./vendor/bin/sail up -d
```

> ※以降の手順で`sail`コマンドを使用するため、以下のエイリアスを設定してください。

- Bash（Linux）の場合:

```bash
echo "alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'" >> ~/.bashrc
exec $SHELL
```

- Zsh（Mac）の場合:

```bash
echo "alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'" >> ~/.zshrc
exec $SHELL
```

### 5. アプリケーションキーの作成

以下のコマンドでアプリケーションキーを作成します。

```bash
sail artisan key:generate
```

### 6. データベースのマイグレーションと初期データ投入

以下のコマンドでテーブルを作成し、ダミーデータを投入します。

```bash
sail artisan migrate:fresh --seed
```

#### マイグレーション実行時にデータベースのアクセスエラーが発生した場合

既存のDockerボリュームに以前のデータベース設定が残っている場合、マイグレーション実行時に`Access denied`エラーが発生することがあります。

その場合は、以下のコマンドを順に実行してください。

> ※`sail down -v`を実行すると、本プロジェクトのDockerボリュームと保存されているデータベースのデータが削除されます。

```bash
sail down -v
sail up -d
```

MySQLコンテナが起動するまで30秒程度待ってから、再度マイグレーションと初期データ投入を実行してください。

```bash
sail artisan migrate:fresh --seed
```

### 7. フロントエンド環境の準備

以下のコマンドでフロントエンドの依存パッケージをインストールし、開発サーバーを起動します。

```bash
sail npm install
sail npm run dev
```

※`npm run dev`は開発中起動したままにする必要があります。別ターミナルで実行してください。

### 8. アプリケーションへのアクセス

ブラウザで [http://localhost](http://localhost) にアクセスします。

phpMyAdminは [http://localhost:8080](http://localhost:8080) にアクセスします。

Mailpitは [http://localhost:8025](http://localhost:8025) にアクセスします。

### 9. テストユーザーでのログイン　※実装後確認要

Seederにより、管理者ユーザーと一般ユーザーの動作確認用データを作成します。

| 種別 | メールアドレス | パスワード |
| --- | --- | --- |
| 一般ユーザー1 | `user1@example.com` | `password` |
| 一般ユーザー2 | `user2@example.com` | `password` |
| 管理者ユーザー | `user3@example.com` | `password` |

ログインURLは実装後に追記予定。

## テスト実行手順

本プロジェクトではPHPUnitを使用してテストを実施します。

### 確認事項

- テストがすべて成功すること
- テスト内容は要件シート「テストケース一覧」に従うこと

### テスト実行

```bash
sail artisan test
```

### カバレッジ確認

追記予定

## 機能一覧
- 一般ユーザーの会員登録・ログイン・ログアウト機能
- 管理者ユーザーのログイン・ログアウト機能
- 一般ユーザーの勤怠打刻機能
- 一般ユーザーの勤怠一覧・勤怠詳細確認・修正申請機能
- 一般ユーザーの修正申請一覧・申請詳細確認機能
- 管理者ユーザーの日次勤怠一覧・勤怠詳細確認・修正機能
- 管理者ユーザーのスタッフ一覧・スタッフ別月次勤怠一覧確認機能
- 管理者ユーザーの修正申請一覧・申請詳細確認・承認機能
- 一般ユーザーのメール認証・認証メール再送機能
- 管理者ユーザーのスタッフ別月次勤怠CSV出力機能
- 一般ユーザーのマイ勤怠レポート機能
- 外部アプリケーション向け勤怠公開API機能

## APIエンドポイント一覧
| HTTPメソッド | URI | 説明 | 認証・認可 |
| --- | --- | --- | --- |
| GET | `/api/v1/attendance-records` | 勤怠一覧を取得する | 不要 |
| GET | `/api/v1/attendance-records/{attendanceRecord}` | 勤怠詳細を取得する | 不要 |
| POST | `/api/v1/attendance-records` | 勤怠を新規登録する | Sanctum 必須 |
| PUT | `/api/v1/attendance-records/{attendanceRecord}` | 勤怠を更新する | Sanctum + AttendanceRecordPolicy（本人のみ） |
| DELETE | `/api/v1/attendance-records/{attendanceRecord}` | 勤怠を削除する | Sanctum + AttendanceRecordPolicy（本人のみ） |

## 補足

### コーディング規約

PHPコードは提供されたコーディング規約を基準とします。

コーディング規約内に明らかな誤記または一般的なLaravel・PHPの規約との不整合がある場合は、一般的なLaravel・PHPの規約に従います。

### 使用パッケージ

指定された技術スタック以外のパッケージは追加しません。

日本語化を行う場合も外部の翻訳パッケージは使用せず、`lang/`へメッセージファイルを手動配置して対応します。
