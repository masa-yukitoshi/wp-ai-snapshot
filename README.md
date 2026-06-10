# WP AI Snapshot

WordPress サイトの構成情報を **JSON / Markdown** で一括出力する管理ツールプラグインです。
Claude・ChatGPT などの AI にサイト構成を正確に伝えたいときにお使いください。

---

## 出力される情報

出力するセクションは管理画面のチェックボックスで取捨選択できます。

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
| SCF / ACF フィールドグループ | フィールド定義・サブフィールド（※） |
| Lazy Blocks | カスタムブロックのコントロール定義（※） |
| Snow Monkey Blocks | 登録済みブロック一覧（※） |
| Yoast SEO | OGP・パンくず・noindex 設定等（※） |
| All in One SEO | OGP・パンくず・noindex 設定等（※） |
| Contact Form 7 | フォームのフィールド定義（※） |
| MW WP Form | フォームのフィールド定義（※） |
| ウィジェット | サイドバーごとのアクティブウィジェット一覧 |
| ブロックパターン | テーマ・プラグインが登録したブロックパターン一覧 |
| カスタムフィールドサンプル | SCF/ACF の各グループから実データのサンプル（※） |

**(※) 対応プラグインが有効な場合のみ出力されます。未導入でも動作します。**

---

## 動作環境

- WordPress 6.0 以上
- PHP 7.4 以上

---

## インストール

1. `wp-ai-snapshot` フォルダごと `wp-content/plugins/` にアップロード
2. WordPress 管理画面 › プラグイン から **「WP AI Snapshot」** を有効化

---

## 使い方

### 管理画面から

**管理画面 › ツール › AI Snapshot** を開きます。

1. 出力したいセクションをチェックボックスで選択（選択状態は自動保存されます）
2. 目的に応じてボタンをクリック

| ボタン | 動作 |
|---|---|
| JSON をダウンロード | JSON ファイルをダウンロード |
| Markdown をダウンロード | Markdown ファイルをダウンロード |
| Markdown をコピー | クリップボードにコピー（トークン数目安を表示） |
| JSON をコピー | クリップボードにコピー（トークン数目安を表示） |
| プレビュー | ページ内で Markdown を確認 |

**AI への渡し方の例:**
> 「添付の Markdown はこのサイトの WordPress 構成情報です。これをもとに〇〇を実装してください。」

---

### REST API

認証が必要です。**WordPress のアプリケーションパスワード**（管理画面 › ユーザー › プロフィール）を使用してください。

```
GET /wp-json/wp-ai-snapshot/v1/snapshot
```

**パラメータ:**

| パラメータ | 説明 | 例 |
|---|---|---|
| `format` | `json`（デフォルト）または `md` | `?format=md` |
| `sections` | 取得するセクションをカンマ区切りで指定 | `?sections=plugins,post_types,cf7` |

**使用例:**

```bash
# Markdown で全セクション取得
curl -u "admin:アプリケーションパスワード" \
  "https://example.com/wp-json/wp-ai-snapshot/v1/snapshot?format=md"

# JSON で特定セクションのみ取得
curl -u "admin:アプリケーションパスワード" \
  "https://example.com/wp-json/wp-ai-snapshot/v1/snapshot?sections=environment,plugins,post_types"
```

---

### WP-CLI

```bash
# JSON で出力（デフォルト）
wp ai-snapshot export

# Markdown でファイルに保存
wp ai-snapshot export --format=md > snapshot.md

# 特定セクションのみ出力
wp ai-snapshot export --format=md --sections=environment,plugins,post_types
```

**利用可能なセクション名:**

```
environment  site          theme         theme_json    template_files
settings     plugins       pages         post_types    taxonomies
menus        field_groups  blocks        smb           yoast
aioseo       cf7           mwform        widgets       block_patterns
field_samples
```

---

## 対応オプションプラグイン

| プラグイン | 出力されるセクション |
|---|---|
| [SCF (Smart Custom Fields)](https://wordpress.org/plugins/smart-custom-fields/) | SCF / ACF フィールドグループ、カスタムフィールドサンプル |
| [Advanced Custom Fields (ACF)](https://www.advancedcustomfields.com/) | SCF / ACF フィールドグループ、カスタムフィールドサンプル |
| [Lazy Blocks](https://wordpress.org/plugins/lazy-blocks/) | Lazy Blocks |
| [Snow Monkey Blocks](https://wordpress.org/plugins/snow-monkey-blocks/) | Snow Monkey Blocks |
| [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/) | Yoast SEO |
| [All in One SEO Pack](https://wordpress.org/plugins/all-in-one-seo-pack/) | All in One SEO |
| [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) | Contact Form 7 |
| [MW WP Form](https://plugins.2inc.org/mw-wp-form/) | MW WP Form |

---

## ライセンス

[GPL-2.0-or-later](LICENSE)
