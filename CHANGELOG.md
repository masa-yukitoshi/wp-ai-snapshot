# Changelog

## [1.4.1] - 2026-06-10

### Changed
- 管理画面の REST API 表示を「AI への渡し方」セクションに刷新
  - チャットへのコピペ手順を明記
  - CLAUDE.md 用テキストのワンクリックコピーボタンを追加
  - アプリケーションパスワードの説明を追加
  - REST API URL のコピーボタンを追加
- README.md を非エンジニア向けに改訂（「こんな時に使います」から始まる構成へ）
  - Claude Code / CLAUDE.md への追記例を追加
  - アプリケーションパスワードの説明を追加

---

## [1.4.0] - 2026-06-10

### Added
- ウィジェット出力（サイドバーごとのアクティブウィジェット一覧）
- ブロックパターン出力（コアパターンを除外し、テーマ・プラグイン定義のみ）
- カスタムフィールドサンプルデータ出力（SCF/ACF の各グループから実投稿データを1件取得）

---

## [1.3.0] - 2026-06-10

### Added
- REST API エンドポイント（`GET /wp-json/wp-ai-snapshot/v1/snapshot`）
  - `format=json|md`・`sections=...` パラメータ対応
  - WP アプリケーションパスワード認証対応
- WP-CLI コマンド（`wp ai-snapshot export`）
  - `--format=json|md`・`--sections=s1,s2,...` オプション対応
- ページ内 Markdown プレビュー機能（トグル表示）
- 管理ページに REST API エンドポイント URL を表示
- Snow Monkey Blocks 対応
- MW WP Form 対応

### Changed
- ナビゲーションメニューにメニュー項目の詳細（URL・階層構造）を追加

---

## [1.2.0] - 2026-06-10

### Added
- 出力セクションのチェックボックス選択（状態は localStorage に保存）
- Markdown / JSON をクリップボードにコピーするボタン
- コピー後のトークン数目安表示
- All in One SEO Pack 対応
- Contact Form 7 対応

### Changed
- カスタム投稿タイプに公開済み件数（count）を追加
- データ収集をセクションごとに分割・フィルタリング対応に刷新

---

## [1.1.0]

### Added
- 初期リリース
- JSON / Markdown ダウンロード
- 投稿タイプ・タクソノミー・SCF/ACF・Lazy Blocks・固定ページ・プラグイン・設定・Yoast SEO の出力
