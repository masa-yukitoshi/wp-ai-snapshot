# WP AI Snapshot

WordPress サイトの構成情報を **JSON / Markdown** で書き出す管理ツールプラグインです。

---

## こんな時に使います

- 「このサイトの投稿タイプやフィールド定義を Claude に教えたい」
- 「ChatGPT にプラグイン構成を把握してもらってから相談したい」
- 「AI との会話が始まるたびにサイト構成を説明し直すのが面倒」

管理画面のボタン 1 つで、サイトの構成情報（投稿タイプ・フィールド・メニュー・プラグイン・SEO 設定など）をまとめた Markdown を生成し、AI にそのまま貼り付けられます。

---

## 使い方

### 方法 1：チャットにコピペ（もっとも手軽）

1. 管理画面 › **ツール › AI Snapshot** を開く
2. 出力したいセクションにチェックを入れる
3. **「Markdown をコピー」** ボタンを押す
4. Claude・ChatGPT などのチャット欄に貼り付ける

> **AI への一言例：**
> 「添付の Markdown はこのサイトの WordPress 構成情報です。これをもとに〇〇を実装してください。」

---

### 方法 2：Claude Code を使っている場合（CLAUDE.md に追記）

**Claude Code** を使って開発している場合、プロジェクトの `CLAUDE.md` に以下を書いておくと、Claude が作業前に自動でサイト構成を参照できます。

```markdown
## WordPress サイト構成

このプロジェクトの WordPress 構成情報は WP AI Snapshot で管理しています。
コーディング作業の前に、最新のスナップショットを確認してください。

取得 URL（Markdown 形式）：
https://example.com/wp-json/wp-ai-snapshot/v1/snapshot?format=md

認証：管理画面 › ユーザー › プロフィール の「アプリケーションパスワード」で発行したパスワードを使用
```

管理画面の AI Snapshot ページにある **「CLAUDE.md 用テキストをコピー」** ボタンを使うと、あなたのサイト URL が入った状態でコピーできます。

> **「アプリケーションパスワード」とは？**
> WordPress が外部ツール用に発行する専用パスワードです。管理画面 › **ユーザー › プロフィール** ページ下部の「アプリケーションパスワード」欄から発行できます。通常のログインパスワードとは別物なので、万が一漏れても本体への影響がなく安全です。

---

### 方法 3：REST API（curl・外部ツールから取得）

アプリケーションパスワードを発行済みの場合、コマンドラインから直接取得できます。

```bash
# Markdown で全セクション取得
curl -u "管理者ユーザー名:アプリケーションパスワード" \
  "https://example.com/wp-json/wp-ai-snapshot/v1/snapshot?format=md"

# JSON で特定セクションのみ取得
curl -u "管理者ユーザー名:アプリケーションパスワード" \
  "https://example.com/wp-json/wp-ai-snapshot/v1/snapshot?sections=environment,plugins,post_types"
```

**パラメータ：**

| パラメータ | 説明 | 例 |
|---|---|---|
| `format` | `json`（デフォルト）または `md` | `?format=md` |
| `sections` | 取得するセクションをカンマ区切りで指定 | `?sections=plugins,post_types` |

---

### 方法 4：WP-CLI（SSH 接続できる場合）

```bash
# Markdown でファイルに保存
wp ai-snapshot export --format=md > snapshot.md

# 特定セクションのみ出力
wp ai-snapshot export --format=md --sections=environment,plugins,post_types
```

**利用可能なセクション名：**

```
environment  site          theme         theme_json    template_files
settings     plugins       pages         post_types    taxonomies
menus        field_groups  smcf          blocks        smb
yoast        aioseo        cf7           mwform        wpsmtp
widgets      block_patterns field_samples
```

---

## 出力される情報

出力するセクションは管理画面のチェックボックスで選択できます。

| セクション | 内容 |
|---|---|
| 環境情報 | WordPress・PHP バージョン |
| サイト情報 | サイト名・URL |
| テーマ | 有効テーマ名・バージョン・作者 |
| theme.json | FSE テーマの theme.json 内容（存在する場合） |
| テンプレートファイル | テーマの PHP ファイル一覧（ディレクトリ別） |
| WordPress 設定 | タイムゾーン・パーマリンク・フロントページ・登録済み画像サイズ等 |
| プラグイン | 有効なプラグイン一覧（名前・バージョン・説明） |
| 固定ページ | ID・タイトル・スラッグ・テンプレート（親子構造付き） |
| カスタム投稿タイプ | supports・関連タクソノミー・アーカイブ設定・公開済み件数 |
| タクソノミー | ターム一覧付き |
| ナビゲーションメニュー | 登録ロケーション・項目一覧（URL・階層構造付き） |
| ACF / Secure Custom Fields フィールドグループ | フィールド定義・サブフィールド（※） |
| Smart Custom Fields フィールドグループ | グループキー・繰り返し有無・フィールド定義（※） |
| Lazy Blocks | カスタムブロックのコントロール定義（※） |
| Snow Monkey Blocks | 登録済みブロック一覧（※） |
| Yoast SEO | OGP・パンくず・noindex 設定等（※） |
| All in One SEO | OGP・パンくず・noindex 設定等（※） |
| Contact Form 7 | フォームのフィールド定義（※） |
| MW WP Form | フォームのフィールド定義（※） |
| WP Mail SMTP | 送信方法・From メール・SMTP 設定等（※） |
| ウィジェット | サイドバーごとのアクティブウィジェット一覧 |
| ブロックパターン | テーマ・プラグインが登録したブロックパターン一覧 |
| カスタムフィールドサンプル | ACF / Secure Custom Fields の各グループから実データのサンプル（※） |

**(※) 対応プラグインが有効な場合のみ出力されます。未導入でも動作します。**

---

## 対応オプションプラグイン

| プラグイン | 出力されるセクション |
|---|---|
| [Advanced Custom Fields (ACF)](https://www.advancedcustomfields.com/) | ACF / Secure Custom Fields フィールドグループ、カスタムフィールドサンプル |
| [Secure Custom Fields](https://wordpress.org/plugins/secure-custom-fields/) | ACF / Secure Custom Fields フィールドグループ、カスタムフィールドサンプル |
| [Smart Custom Fields](https://wordpress.org/plugins/smart-custom-fields/) | Smart Custom Fields フィールドグループ |
| [Custom Post Type UI](https://wordpress.org/plugins/custom-post-type-ui/) | カスタム投稿タイプ、タクソノミー（標準セクションで自動取得） |
| [Lazy Blocks](https://wordpress.org/plugins/lazy-blocks/) | Lazy Blocks |
| [Snow Monkey Blocks](https://wordpress.org/plugins/snow-monkey-blocks/) | Snow Monkey Blocks |
| [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/) | Yoast SEO |
| [All in One SEO Pack](https://wordpress.org/plugins/all-in-one-seo-pack/) | All in One SEO |
| [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) | Contact Form 7 |
| [MW WP Form](https://plugins.2inc.org/mw-wp-form/) | MW WP Form |
| [WP Mail SMTP](https://wordpress.org/plugins/wp-mail-smtp/) | WP Mail SMTP |

---

## 動作環境

- WordPress 6.0 以上
- PHP 7.4 以上

---

## インストール

### zip ファイルをダウンロードしてインストール（かんたん）

1. **[wp-ai-snapshot.zip をダウンロード](https://github.com/masa-yukitoshi/wp-ai-snapshot/releases/latest/download/wp-ai-snapshot.zip)**
2. WordPress 管理画面 › プラグイン › 新規追加 › **「プラグインのアップロード」** を選択
3. ダウンロードした zip ファイルをアップロード → 有効化

### 手動でインストール

1. `wp-ai-snapshot` フォルダごと `wp-content/plugins/` にアップロード
2. WordPress 管理画面 › プラグイン から **「WP AI Snapshot」** を有効化

---

## ライセンス

[GPL-2.0-or-later](LICENSE)
