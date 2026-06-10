# WP AI Snapshot

WordPress サイトの構成情報を **JSON / Markdown** で一括出力する管理ツールプラグインです。
Claude・ChatGPT などの AI にサイト構成を正確に伝えたいときにお使いください。

---

## 出力される情報

| セクション | 内容 |
|---|---|
| 環境 | WordPress バージョン、PHP バージョン |
| サイト情報 | サイト名、URL |
| テーマ | 有効テーマ名・バージョン |
| WordPress 設定 | タイムゾーン・パーマリンク・フロントページ・画像サイズ等 |
| テンプレートファイル | テーマの PHP ファイル一覧（ディレクトリ別） |
| theme.json | FSE テーマの theme.json 内容（存在する場合） |
| 有効プラグイン | 名前・バージョン・説明 |
| 固定ページ | ID・タイトル・スラッグ・テンプレート（親子構造付き） |
| カスタム投稿タイプ | supports・関連タクソノミー・アーカイブ設定等 |
| タクソノミー | ターム一覧付き |
| ナビゲーションメニュー | 登録ロケーションと割り当てメニュー |
| SCF / ACF フィールドグループ | フィールド定義・サブフィールド（※） |
| Lazy Blocks | カスタムブロックのコントロール定義（※） |
| Yoast SEO | OGP・パンくず・noindex 設定等（※） |

**(※) 対応プラグインが有効な場合のみ出力されます。未導入でも動作します。**

---

## 動作環境

- WordPress 6.0 以上
- PHP 7.4 以上

## インストール

1. `wp-ai-snapshot` フォルダごと `wp-content/plugins/` にアップロード
2. WordPress 管理画面 › プラグイン から「WP AI Snapshot」を有効化

## 使い方

1. 管理画面 › **ツール › AI Snapshot** を開く
2. **「JSON をダウンロード」** または **「Markdown をダウンロード」** をクリック
3. ダウンロードされたファイルを AI に添付・貼り付け

**AI への渡し方の例（Markdown の場合）:**
> 「添付の Markdown はこのサイトの WordPress 構成情報です。これをもとに〇〇を作ってください。」

---

## 対応オプションプラグイン

| プラグイン | 出力されるセクション |
|---|---|
| [SCF (Smart Custom Fields)](https://wordpress.org/plugins/smart-custom-fields/) | SCF フィールドグループ |
| [Advanced Custom Fields (ACF)](https://www.advancedcustomfields.com/) | SCF フィールドグループ（ACF 互換） |
| [Lazy Blocks](https://wordpress.org/plugins/lazy-blocks/) | Lazy Blocks |
| [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/) | Yoast SEO |

---

## ライセンス

[GPL-2.0-or-later](LICENSE)
