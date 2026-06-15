<?php
/**
 * Plugin Name: WP AI Snapshot
 * Description: WordPress の構成情報（投稿タイプ・タクソノミー・フィールド・メニュー・プラグイン・SEO設定等）をJSON / Markdownで一括出力します。Claude・ChatGPT 等のAIへサイト構成を伝える際にお使いください。REST API・WP-CLI にも対応しています。
 * Version:     1.5.0
 * Author:      Den LLC.
 */

if ( ! defined( 'ABSPATH' ) ) exit;


// ============================================================
// セクション定義
// ============================================================
function wpais_sections() {
	return [
		'environment'    => '環境情報',
		'site'           => 'サイト情報',
		'theme'          => 'テーマ',
		'theme_json'     => 'theme.json',
		'template_files' => 'テンプレートファイル',
		'settings'       => 'WordPress 設定',
		'plugins'        => 'プラグイン',
		'pages'          => '固定ページ',
		'post_types'     => 'カスタム投稿タイプ',
		'taxonomies'     => 'タクソノミー',
		'menus'          => 'ナビゲーションメニュー',
		'field_groups'   => 'ACF / Secure Custom Fields フィールドグループ',
		'smcf'           => 'Smart Custom Fields フィールドグループ',
		'blocks'         => 'Lazy Blocks',
		'smb'            => 'Snow Monkey Blocks',
		'yoast'          => 'Yoast SEO',
		'aioseo'         => 'All in One SEO',
		'cf7'            => 'Contact Form 7',
		'mwform'         => 'MW WP Form',
		'wpsmtp'         => 'WP Mail SMTP',
		'widgets'        => 'ウィジェット',
		'block_patterns' => 'ブロックパターン',
		'field_samples'  => 'カスタムフィールドサンプル',
	];
}


// ============================================================
// 管理メニュー（ツール）
// ============================================================
add_action( 'admin_menu', function () {
	add_management_page(
		'AI Snapshot',
		'AI Snapshot',
		'manage_options',
		'wp-ai-snapshot',
		'wpais_page'
	);
} );

function wpais_page() {
	$sections   = wpais_sections();
	$copy_nonce = wp_create_nonce( 'wpais_copy' );
	$ajax_url   = admin_url( 'admin-ajax.php' );
	$rest_url   = rest_url( 'wp-ai-snapshot/v1/snapshot' );
	?>
	<div class="wrap">
		<h1>AI Snapshot</h1>
		<p>WordPressの構成情報（投稿タイプ・タクソノミー・ACF / Smart Custom Fields フィールド・Lazy Blocks・固定ページ・プラグイン・設定等）を出力します。</p>

		<div id="wpais-sections" style="margin:16px 0;padding:16px;background:#fff;border:1px solid #c3c4c7;border-radius:3px;">
			<div style="margin-bottom:10px;">
				<strong>出力するセクション</strong>
				&ensp;
				<a href="#" id="wpais-all" style="font-size:12px;">すべて選択</a>
				&thinsp;/&thinsp;
				<a href="#" id="wpais-none" style="font-size:12px;">すべて解除</a>
			</div>
			<div style="display:flex;flex-wrap:wrap;gap:6px 24px;">
				<?php foreach ( $sections as $key => $label ) : ?>
				<label style="white-space:nowrap;cursor:pointer;font-size:13px;">
					<input type="checkbox" class="wpais-section" value="<?php echo esc_attr( $key ); ?>" checked>
					<?php echo esc_html( $label ); ?>
				</label>
				<?php endforeach; ?>
			</div>
		</div>

		<div style="display:flex;align-items:flex-end;gap:16px;flex-wrap:wrap;">
			<div>
				<button type="button" id="wpais-preview-btn" class="button">プレビュー</button>
			</div>

			<div>
				<p style="margin:0 0 5px;font-size:11px;color:#50575e;">Markdown — AI チャット・Claude Code との連携に</p>
				<div style="display:flex;gap:6px;">
					<button type="button" id="wpais-copy-md" class="button button-primary">Markdown をコピー</button>
					<form method="post" id="wpais-form-md" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
						<?php wp_nonce_field( 'wpais_download' ); ?>
						<input type="hidden" name="action" value="wpais_download_md">
						<span id="wpais-inject-md"></span>
						<button type="submit" class="button">Markdown をダウンロード</button>
					</form>
				</div>
			</div>

			<div>
				<p style="margin:0 0 5px;font-size:11px;color:#50575e;">JSON — プログラムからの処理・自動化に</p>
				<div style="display:flex;gap:6px;">
					<button type="button" id="wpais-copy-json" class="button">JSON をコピー</button>
					<form method="post" id="wpais-form-json" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
						<?php wp_nonce_field( 'wpais_download' ); ?>
						<input type="hidden" name="action" value="wpais_download_json">
						<span id="wpais-inject-json"></span>
						<button type="submit" class="button">JSON をダウンロード</button>
					</form>
				</div>
			</div>

			<span id="wpais-status" style="font-size:13px;color:#2271b1;"></span>
		</div>

		<p id="wpais-tokens" style="margin-top:8px;font-size:12px;color:#666;"></p>

		<div id="wpais-preview-area" style="display:none;margin-top:16px;">
			<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
				<strong style="font-size:13px;">Markdown プレビュー</strong>
				<button type="button" id="wpais-preview-close" class="button button-small">閉じる</button>
			</div>
			<pre id="wpais-preview-content" style="max-height:420px;overflow:auto;padding:14px;background:#f6f7f7;border:1px solid #c3c4c7;border-radius:3px;font-size:12px;line-height:1.7;white-space:pre-wrap;word-break:break-word;"></pre>
			<p id="wpais-preview-tokens" style="margin-top:6px;font-size:12px;color:#666;"></p>
		</div>

		<div style="margin-top:24px;border-top:1px solid #dcdcde;padding-top:20px;">
			<p style="font-size:14px;font-weight:600;margin:0 0 14px;">AI への渡し方</p>
			<div style="display:grid;gap:12px;">

				<div style="padding:14px;background:#fff;border:1px solid #c3c4c7;border-radius:3px;">
					<p style="margin:0 0 5px;font-size:13px;font-weight:600;">① チャットにコピペ &ensp;<span style="font-weight:400;font-size:12px;color:#2271b1;">いちばん手軽</span></p>
					<p style="margin:0;font-size:13px;color:#3c434a;">上の「<strong>Markdown をコピー</strong>」ボタンを押して、Claude・ChatGPT などのチャット欄に貼り付けてください。</p>
				</div>

				<div style="padding:14px;background:#fff;border:1px solid #c3c4c7;border-radius:3px;">
					<p style="margin:0 0 5px;font-size:13px;font-weight:600;">② Claude Code を使っている場合 — CLAUDE.md に追記 &ensp;<span style="font-weight:400;font-size:12px;color:#2271b1;">おすすめ</span></p>
					<p style="margin:0 0 8px;font-size:13px;color:#3c434a;">プロジェクトの <code>CLAUDE.md</code> に以下を書いておくと、Claude が作業前に自動でサイト構成を参照できます。</p>
<pre id="wpais-claudemd-snippet" style="margin:0 0 8px;padding:10px;background:#f6f7f7;border:1px solid #c3c4c7;border-radius:3px;font-size:11px;line-height:1.7;white-space:pre-wrap;word-break:break-all;">## WordPress サイト構成

このプロジェクトの WordPress 構成情報は WP AI Snapshot で管理しています。
コーディング作業の前に、最新のスナップショットを確認してください。

取得 URL（Markdown 形式）：
<?php echo esc_html( $rest_url ); ?>?format=md

認証：管理画面 › ユーザー › プロフィール の「アプリケーションパスワード」で発行したパスワードを使用
（アプリケーションパスワードは WordPress 管理画面で発行できる外部ツール用の専用パスワードです）</pre>
					<button type="button" id="wpais-copy-claudemd" class="button button-small">CLAUDE.md 用テキストをコピー</button>
				</div>

				<div style="padding:14px;background:#fff;border:1px solid #c3c4c7;border-radius:3px;">
					<p style="margin:0 0 5px;font-size:13px;font-weight:600;">③ REST API — curl や外部ツールから直接取得</p>
					<p style="margin:0 0 6px;font-size:13px;color:#3c434a;">
						URL：<code><?php echo esc_html( $rest_url ); ?></code>
						&ensp;<button type="button" id="wpais-copy-resturl" class="button button-small">URL をコピー</button>
					</p>
					<p style="margin:0;font-size:12px;color:#666;">
						アクセスには認証が必要です。管理画面 › <strong>ユーザー › プロフィール</strong> ページ下部の「アプリケーションパスワード」欄から専用パスワードを発行してください。<br>
						アプリケーションパスワードとは、外部ツール用に WordPress が発行する専用パスワードです。通常のログインパスワードを使い回さずに済むため安全です。<br>
						パラメータ：<code>?format=md</code> で Markdown 形式、<code>?format=json</code> で JSON 形式
					</p>
				</div>

			</div>
		</div>
	</div>

	<script>
	(function () {
		const STORAGE_KEY = 'wpais_sections_v1';
		const boxes       = document.querySelectorAll('.wpais-section');
		const ajaxUrl     = <?php echo wp_json_encode( $ajax_url ); ?>;
		const copyNonce   = <?php echo wp_json_encode( $copy_nonce ); ?>;

		// localStorage から状態を復元
		const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
		if (Array.isArray(saved)) {
			boxes.forEach(cb => { cb.checked = saved.includes(cb.value); });
		}

		function save() {
			localStorage.setItem(STORAGE_KEY, JSON.stringify(
				Array.from(boxes).filter(cb => cb.checked).map(cb => cb.value)
			));
		}
		boxes.forEach(cb => cb.addEventListener('change', save));

		document.getElementById('wpais-all').addEventListener('click', e => {
			e.preventDefault(); boxes.forEach(cb => cb.checked = true); save();
		});
		document.getElementById('wpais-none').addEventListener('click', e => {
			e.preventDefault(); boxes.forEach(cb => cb.checked = false); save();
		});

		function selected() {
			return Array.from(boxes).filter(cb => cb.checked).map(cb => cb.value);
		}

		function injectHidden(containerId) {
			const c = document.getElementById(containerId);
			c.innerHTML = '';
			selected().forEach(v => {
				const i = document.createElement('input');
				i.type = 'hidden'; i.name = 'wpais_sections[]'; i.value = v;
				c.appendChild(i);
			});
		}

		document.getElementById('wpais-form-json').addEventListener('submit', () => injectHidden('wpais-inject-json'));
		document.getElementById('wpais-form-md').addEventListener('submit',   () => injectHidden('wpais-inject-md'));

		async function fetchContent(format) {
			const params = new URLSearchParams({
				action: 'wpais_copy',
				_ajax_nonce: copyNonce,
				format: format,
			});
			selected().forEach(v => params.append('wpais_sections[]', v));

			const res  = await fetch(ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: params.toString(),
			});
			const json = await res.json();
			if (!json.success) throw new Error(json.data || 'unknown error');
			return json.data.content;
		}

		function tokenEstimate(text) {
			return Math.ceil(text.length / 2.5).toLocaleString();
		}

		// コピーボタン
		async function copyContent(format) {
			const status  = document.getElementById('wpais-status');
			const tokenEl = document.getElementById('wpais-tokens');
			status.textContent  = '取得中…';
			tokenEl.textContent = '';
			try {
				const content = await fetchContent(format);
				await navigator.clipboard.writeText(content);
				status.textContent  = '✓ コピーしました';
				tokenEl.textContent = `約 ${tokenEstimate(content)} tokens（目安）`;
				setTimeout(() => status.textContent = '', 3000);
			} catch (err) {
				status.textContent = 'コピー失敗: ' + err.message;
			}
		}

		document.getElementById('wpais-copy-md').addEventListener('click',  () => copyContent('md'));
		document.getElementById('wpais-copy-json').addEventListener('click', () => copyContent('json'));

		// プレビュー
		const previewArea    = document.getElementById('wpais-preview-area');
		const previewContent = document.getElementById('wpais-preview-content');
		const previewTokens  = document.getElementById('wpais-preview-tokens');
		const previewBtn     = document.getElementById('wpais-preview-btn');

		previewBtn.addEventListener('click', async function () {
			if (previewArea.style.display !== 'none') {
				previewArea.style.display = 'none';
				previewBtn.textContent = 'プレビュー';
				return;
			}
			previewArea.style.display = 'block';
			previewBtn.textContent    = 'プレビューを閉じる';
			previewContent.textContent = '読み込み中…';
			previewTokens.textContent  = '';
			try {
				const content = await fetchContent('md');
				previewContent.textContent = content;
				previewTokens.textContent  = `約 ${tokenEstimate(content)} tokens（目安）`;
			} catch (err) {
				previewContent.textContent = 'エラー: ' + err.message;
			}
		});

		document.getElementById('wpais-preview-close').addEventListener('click', () => {
			previewArea.style.display = 'none';
			previewBtn.textContent    = 'プレビュー';
		});

		// ② CLAUDE.md テキストコピー
		document.getElementById('wpais-copy-claudemd').addEventListener('click', async function () {
			const text = document.getElementById('wpais-claudemd-snippet').textContent;
			try {
				await navigator.clipboard.writeText(text);
				this.textContent = '✓ コピーしました';
				setTimeout(() => { this.textContent = 'CLAUDE.md 用テキストをコピー'; }, 2500);
			} catch (err) {
				alert('コピーに失敗しました: ' + err.message);
			}
		});

		// ③ REST API URL コピー
		document.getElementById('wpais-copy-resturl').addEventListener('click', async function () {
			try {
				await navigator.clipboard.writeText(<?php echo wp_json_encode( $rest_url ); ?>);
				this.textContent = '✓ コピーしました';
				setTimeout(() => { this.textContent = 'URL をコピー'; }, 2500);
			} catch (err) {
				alert('コピーに失敗しました: ' + err.message);
			}
		});
	})();
	</script>
	<?php
}


// ============================================================
// ダウンロード処理 — JSON
// ============================================================
add_action( 'admin_post_wpais_download_json', function () {
	check_admin_referer( 'wpais_download' );
	if ( ! current_user_can( 'manage_options' ) ) wp_die( '権限がありません。' );

	try {
		$sections = isset( $_POST['wpais_sections'] ) ? array_map( 'sanitize_key', (array) $_POST['wpais_sections'] ) : null;
		$data     = wpais_generate( $sections );
		$filename = 'wp-snapshot-' . gmdate( 'Y-m-d' ) . '.json';

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: no-cache, no-store' );

		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	} catch ( Throwable $e ) {
		wp_die( 'エラーが発生しました: ' . esc_html( $e->getMessage() ) . ' (' . esc_html( $e->getFile() ) . ':' . $e->getLine() . ')' );
	}
	exit;
} );


// ============================================================
// ダウンロード処理 — Markdown
// ============================================================
add_action( 'admin_post_wpais_download_md', function () {
	check_admin_referer( 'wpais_download' );
	if ( ! current_user_can( 'manage_options' ) ) wp_die( '権限がありません。' );

	try {
		$sections = isset( $_POST['wpais_sections'] ) ? array_map( 'sanitize_key', (array) $_POST['wpais_sections'] ) : null;
		$data     = wpais_generate( $sections );
		$md       = wpais_to_markdown( $data );
		$filename = 'wp-snapshot-' . gmdate( 'Y-m-d' ) . '.md';

		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: no-cache, no-store' );

		echo $md;
	} catch ( Throwable $e ) {
		wp_die( 'エラーが発生しました: ' . esc_html( $e->getMessage() ) . ' (' . esc_html( $e->getFile() ) . ':' . $e->getLine() . ')' );
	}
	exit;
} );


// ============================================================
// AJAX — クリップボードコピー / プレビュー
// ============================================================
add_action( 'wp_ajax_wpais_copy', function () {
	check_ajax_referer( 'wpais_copy' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( '権限がありません。' );

	try {
		$sections = isset( $_POST['wpais_sections'] ) ? array_map( 'sanitize_key', (array) $_POST['wpais_sections'] ) : null;
		$format   = ( isset( $_POST['format'] ) && $_POST['format'] === 'json' ) ? 'json' : 'md';
		$data     = wpais_generate( $sections );
		$content  = $format === 'json'
			? wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
			: wpais_to_markdown( $data );

		wp_send_json_success( [ 'content' => $content ] );
	} catch ( Throwable $e ) {
		wp_send_json_error( $e->getMessage() );
	}
} );


// ============================================================
// REST API
// ============================================================
add_action( 'rest_api_init', function () {
	register_rest_route( 'wp-ai-snapshot/v1', '/snapshot', [
		'methods'             => 'GET',
		'callback'            => 'wpais_rest_handler',
		'permission_callback' => function () {
			return current_user_can( 'manage_options' );
		},
		'args' => [
			'format' => [
				'type'    => 'string',
				'enum'    => [ 'json', 'md' ],
				'default' => 'json',
			],
			'sections' => [
				'type' => 'string',
			],
		],
	] );
} );

function wpais_rest_handler( WP_REST_Request $request ) {
	$sections_param = $request->get_param( 'sections' );
	$sections       = $sections_param
		? array_map( 'sanitize_key', array_filter( explode( ',', $sections_param ) ) )
		: null;

	$data   = wpais_generate( $sections );
	$format = $request->get_param( 'format' );

	if ( $format === 'md' ) {
		return rest_ensure_response( [ 'content' => wpais_to_markdown( $data ) ] );
	}

	return rest_ensure_response( $data );
}


// ============================================================
// WP-CLI
// ============================================================
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	class WPAIS_CLI_Command {

		/**
		 * WordPress の構成情報をスナップショットとして出力します。
		 *
		 * ## OPTIONS
		 *
		 * [--format=<format>]
		 * : 出力形式。json（デフォルト）または md。
		 *
		 * [--sections=<sections>]
		 * : 出力するセクションをカンマ区切りで指定。省略時は全セクション。
		 * : 例: environment,plugins,post_types
		 *
		 * ## EXAMPLES
		 *
		 *   wp ai-snapshot export
		 *   wp ai-snapshot export --format=md
		 *   wp ai-snapshot export --format=md > snapshot.md
		 *   wp ai-snapshot export --sections=environment,plugins,post_types
		 */
		public function export( $args, $assoc_args ) {
			$format       = $assoc_args['format'] ?? 'json';
			$sections_str = $assoc_args['sections'] ?? null;
			$sections     = $sections_str
				? array_map( 'sanitize_key', array_filter( explode( ',', $sections_str ) ) )
				: null;

			$data = wpais_generate( $sections );

			if ( $format === 'md' ) {
				WP_CLI::log( wpais_to_markdown( $data ) );
			} else {
				WP_CLI::log( wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
			}
		}
	}

	WP_CLI::add_command( 'ai-snapshot', 'WPAIS_CLI_Command' );
}


// ============================================================
// データ収集
// ============================================================
function wpais_generate( $sections = null ) {
	$generators = [
		'environment'    => 'wpais_environment',
		'site'           => 'wpais_site',
		'theme'          => 'wpais_theme',
		'theme_json'     => 'wpais_theme_json',
		'template_files' => 'wpais_template_files',
		'settings'       => 'wpais_settings',
		'plugins'        => 'wpais_plugins',
		'pages'          => 'wpais_pages',
		'post_types'     => 'wpais_post_types',
		'taxonomies'     => 'wpais_taxonomies',
		'menus'          => 'wpais_menus',
		'field_groups'   => 'wpais_scf',
		'smcf'           => 'wpais_smcf',
		'blocks'         => 'wpais_lazy_blocks',
		'smb'            => 'wpais_smb',
		'yoast'          => 'wpais_yoast',
		'aioseo'         => 'wpais_aioseo',
		'cf7'            => 'wpais_cf7',
		'mwform'         => 'wpais_mwform',
		'wpsmtp'         => 'wpais_wpsmtp',
		'widgets'        => 'wpais_widgets',
		'block_patterns' => 'wpais_block_patterns',
		'field_samples'  => 'wpais_field_samples',
	];

	$allowed = ( $sections !== null ) ? array_flip( $sections ) : null;
	$data    = [ 'generated_at' => current_time( 'c' ) ];

	foreach ( $generators as $key => $fn ) {
		if ( $allowed !== null && ! isset( $allowed[ $key ] ) ) continue;
		$data[ $key ] = $fn();
	}

	return $data;
}


// ---- 環境情報 ----
function wpais_environment() {
	return [
		'wordpress_version' => get_bloginfo( 'version' ),
		'php_version'       => phpversion(),
	];
}


// ---- theme.json ----
function wpais_theme_json() {
	$path = get_template_directory() . '/theme.json';
	if ( ! file_exists( $path ) ) return null;
	$json = json_decode( file_get_contents( $path ), true );
	return $json ?: null;
}


// ---- テンプレートファイル一覧 ----
function wpais_template_files() {
	$theme_dir = get_template_directory();
	$result    = [];

	$root_files = glob( $theme_dir . '/*.php' );
	sort( $root_files );
	foreach ( $root_files as $file ) {
		$result['root'][] = basename( $file );
	}

	$subdirs = array_filter( glob( $theme_dir . '/*' ), 'is_dir' );
	foreach ( $subdirs as $dir ) {
		$dirname = basename( $dir );
		if ( in_array( $dirname, [ 'node_modules', '.git', '_data', '_plugins', 'assets' ], true ) ) continue;
		$files = glob( $dir . '/*.php' );
		if ( ! $files ) continue;
		sort( $files );
		foreach ( $files as $file ) {
			$result[ $dirname ][] = basename( $file );
		}
	}

	return $result;
}


// ---- サイト情報 ----
function wpais_site() {
	return [
		'name' => get_bloginfo( 'name' ),
		'url'  => get_site_url(),
	];
}


// ---- テーマ情報 ----
function wpais_theme() {
	$theme = wp_get_theme();
	return [
		'name'    => $theme->get( 'Name' ),
		'version' => $theme->get( 'Version' ),
		'author'  => $theme->get( 'Author' ),
	];
}


// ---- WordPress 設定 ----
function wpais_settings() {
	$front_type    = get_option( 'show_on_front' );
	$front_page_id = get_option( 'page_on_front' );
	$posts_page_id = get_option( 'page_for_posts' );

	$raw_sizes     = wp_get_registered_image_subsizes();
	$wp_auto_sizes = [ 'medium_large', '1536x1536', '2048x2048' ];
	$image_sizes   = [];
	foreach ( $raw_sizes as $name => $size ) {
		if ( in_array( $name, $wp_auto_sizes, true ) ) continue;
		if ( $size['width'] === 0 && $size['height'] === 0 ) continue;
		$image_sizes[ $name ] = [
			'width'  => $size['width'],
			'height' => $size['height'],
			'crop'   => $size['crop'],
		];
	}

	return [
		'timezone'        => get_option( 'timezone_string' ) ?: get_option( 'gmt_offset' ),
		'date_format'     => get_option( 'date_format' ),
		'permalink'       => get_option( 'permalink_structure' ),
		'posts_per_page'  => (int) get_option( 'posts_per_page' ),
		'front_page_type' => $front_type,
		'front_page'      => ( $front_type === 'page' && $front_page_id )
			? get_the_title( $front_page_id ) : null,
		'posts_page'      => $posts_page_id ? get_the_title( $posts_page_id ) : null,
		'image_sizes'     => $image_sizes,
	];
}


// ---- プラグイン（有効なもの）----
function wpais_plugins() {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$all_plugins    = get_plugins();
	$active_plugins = (array) get_option( 'active_plugins', [] );
	$result         = [];

	foreach ( $active_plugins as $plugin_file ) {
		if ( ! isset( $all_plugins[ $plugin_file ] ) ) continue;
		$p        = $all_plugins[ $plugin_file ];
		$result[] = [
			'name'        => $p['Name'],
			'version'     => $p['Version'],
			'description' => wp_strip_all_tags( $p['Description'] ),
			'author'      => wp_strip_all_tags( $p['Author'] ),
		];
	}

	return $result;
}


// ---- 固定ページ（親→子の順にソート）----
function wpais_pages() {
	$pages = get_posts( [
		'post_type'      => 'page',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	] );

	$map    = [];
	$result = [];

	foreach ( $pages as $page ) {
		$map[ $page->ID ] = [
			'id'       => $page->ID,
			'title'    => $page->post_title,
			'slug'     => $page->post_name,
			'parent'   => $page->post_parent ?: null,
			'template' => get_page_template_slug( $page->ID ) ?: 'default',
		];
	}

	$flatten = function ( $parent_id ) use ( &$flatten, &$map, &$result ) {
		foreach ( $map as $id => $item ) {
			if ( (int) $item['parent'] !== (int) $parent_id ) continue;
			$result[] = $item;
			$flatten( $id );
		}
	};
	$flatten( 0 );

	return $result;
}


// ---- カスタム投稿タイプ ----
function wpais_post_types() {
	$post_types = get_post_types( [ '_builtin' => false ], 'objects' );
	$result     = [];

	$exclude = [
		'acf-taxonomy', 'acf-post-type', 'acf-ui-options-page', 'acf-field-group',
		'lazyblocks', 'lazyblocks_templates',
		'wpcf7_contact_form',
		'mw-wp-form',
		'smart-custom-fields',
	];

	foreach ( $post_types as $pt ) {
		if ( in_array( $pt->name, $exclude, true ) ) continue;
		if ( ! $pt->public && ! $pt->show_ui ) continue;

		$counts   = wp_count_posts( $pt->name );
		$result[] = [
			'name'         => $pt->name,
			'label'        => $pt->label,
			'singular'     => $pt->labels->singular_name ?? '',
			'count'        => (int) ( $counts->publish ?? 0 ),
			'supports'     => array_keys( get_all_post_type_supports( $pt->name ) ),
			'taxonomies'   => get_object_taxonomies( $pt->name ),
			'hierarchical' => $pt->hierarchical,
			'has_archive'  => $pt->has_archive,
			'rewrite'      => is_array( $pt->rewrite ) ? ( $pt->rewrite['slug'] ?? $pt->name ) : $pt->name,
		];
	}

	return $result;
}


// ---- タクソノミー + ターム一覧 ----
function wpais_taxonomies() {
	$taxonomies = get_taxonomies( [ '_builtin' => false ], 'objects' );
	$result     = [];

	foreach ( $taxonomies as $tax ) {
		$raw_terms = get_terms( [
			'taxonomy'   => $tax->name,
			'hide_empty' => false,
			'orderby'    => 'term_id',
		] );

		$terms = [];
		if ( ! is_wp_error( $raw_terms ) ) {
			foreach ( $raw_terms as $term ) {
				$terms[] = [
					'term_id' => $term->term_id,
					'name'    => $term->name,
					'slug'    => urldecode( $term->slug ),
					'parent'  => $term->parent,
					'count'   => $term->count,
				];
			}
		}

		$result[] = [
			'name'         => $tax->name,
			'label'        => $tax->label,
			'object_type'  => $tax->object_type,
			'hierarchical' => $tax->hierarchical,
			'terms'        => $terms,
		];
	}

	return $result;
}


// ---- ナビゲーションメニュー（項目詳細付き）----
function wpais_menus() {
	$locations = get_registered_nav_menus();
	$assigned  = get_nav_menu_locations();
	$result    = [];

	foreach ( $locations as $location => $description ) {
		$menu_id   = $assigned[ $location ] ?? null;
		$menu      = $menu_id ? wp_get_nav_menu_object( $menu_id ) : null;
		$raw_items = $menu_id ? wp_get_nav_menu_items( $menu_id ) : [];

		$result[] = [
			'location'    => $location,
			'description' => $description,
			'menu_name'   => $menu ? $menu->name : null,
			'items'       => $raw_items ? wpais_build_menu_tree( $raw_items ) : [],
		];
	}

	return $result;
}

function wpais_build_menu_tree( array $items, $parent_id = 0 ) {
	$tree = [];

	foreach ( $items as $item ) {
		if ( (int) $item->menu_item_parent !== $parent_id ) continue;

		$node = [
			'title' => $item->title,
			'url'   => $item->url,
			'type'  => $item->object,
		];

		$children = wpais_build_menu_tree( $items, $item->ID );
		if ( $children ) $node['children'] = $children;

		$tree[] = $node;
	}

	return $tree;
}


// ---- ACF / Secure Custom Fields フィールドグループ ----
function wpais_scf() {
	if ( ! function_exists( 'acf_get_field_groups' ) ) return null;

	$groups = acf_get_field_groups();
	$result = [];

	foreach ( $groups as $group ) {
		$fields   = acf_get_fields( $group['key'] ) ?: [];
		$result[] = [
			'key'      => $group['key'],
			'title'    => $group['title'],
			'location' => $group['location'],
			'fields'   => wpais_scf_fields( $fields ),
		];
	}

	return $result;
}

function wpais_scf_fields( array $fields ) {
	$result = [];

	foreach ( $fields as $field ) {
		$item = [
			'key'   => $field['key'],
			'name'  => $field['name'],
			'label' => $field['label'],
			'type'  => $field['type'],
		];

		if ( ! empty( $field['choices'] ) )    $item['choices']       = $field['choices'];
		if ( isset( $field['return_format'] ) ) $item['return_format'] = $field['return_format'];
		if ( ! empty( $field['sub_fields'] ) )  $item['sub_fields']    = wpais_scf_fields( $field['sub_fields'] );

		$result[] = $item;
	}

	return $result;
}


// ---- Smart Custom Fields ----
function wpais_smcf() {
	if ( ! post_type_exists( 'smart-custom-fields' ) ) return null;

	global $wpdb;
	$rows = $wpdb->get_results(
		"SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'smart-custom-fields' AND post_status = 'publish' ORDER BY ID ASC"
	);

	if ( empty( $rows ) ) return [];

	$result = [];
	foreach ( $rows as $row ) {
		$post_id   = (int) $row->ID;
		$condition = get_post_meta( $post_id, 'smart-cf-condition', true );
		$settings  = get_post_meta( $post_id, 'smart-cf-setting', true );

		$result[] = [
			'title'     => $row->post_title,
			'condition' => is_array( $condition ) ? $condition : [],
			'groups'    => wpais_smcf_parse_groups( $settings ),
		];
	}

	return $result;
}

function wpais_smcf_parse_groups( $settings ) {
	$result = [];
	if ( ! is_array( $settings ) ) return $result;

	foreach ( $settings as $group ) {
		if ( ! is_array( $group ) ) continue;

		$fields = [];
		foreach ( ( $group['fields'] ?? [] ) as $field ) {
			if ( ! is_array( $field ) ) continue;
			$item = [
				'type'  => $field['type'] ?? '',
				'name'  => $field['name'] ?? '',
				'label' => $field['label'] ?? '',
			];
			if ( ! empty( $field['notes'] ) ) $item['notes'] = $field['notes'];
			$fields[] = $item;
		}

		$result[] = [
			'group_name' => $group['group-name'] ?? '',
			'repeat'     => ! empty( $group['repeat'] ),
			'fields'     => $fields,
		];
	}

	return $result;
}

function wpais_smcf_condition_summary( array $condition ) {
	if ( empty( $condition ) ) return '（条件なし）';
	// smart-cf-condition は投稿タイプスラッグの配列: ['post', 'product'] 等
	return 'post_type == ' . implode( ', ', $condition );
}


// ---- Lazy Blocks ----
function wpais_lazy_blocks() {
	if ( ! post_type_exists( 'lazyblocks' ) ) return null;

	$posts  = get_posts( [
		'post_type'      => 'lazyblocks',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	] );
	$result = [];

	foreach ( $posts as $post ) {
		$controls = get_post_meta( $post->ID, 'lazyblocks_controls', true );
		$supports = get_post_meta( $post->ID, 'lazyblocks_supports', true );
		$result[] = [
			'title'    => $post->post_title,
			'slug'     => urldecode( $post->post_name ),
			'controls' => is_array( $controls ) ? $controls : ( $controls ? json_decode( $controls, true ) : [] ),
			'supports' => is_array( $supports ) ? $supports : ( $supports ? json_decode( $supports, true ) : [] ),
		];
	}

	return $result;
}


// ---- Snow Monkey Blocks ----
function wpais_smb() {
	$registry  = WP_Block_Type_Registry::get_instance();
	$all_types = $registry->get_all_registered();
	$blocks    = [];

	foreach ( $all_types as $name => $block ) {
		if ( strpos( $name, 'snow-monkey-blocks/' ) !== 0 ) continue;
		$blocks[] = [
			'name'  => $name,
			'title' => $block->title ?? str_replace( 'snow-monkey-blocks/', '', $name ),
		];
	}

	if ( empty( $blocks ) ) return null;

	return [
		'version' => defined( 'SNOW_MONKEY_BLOCKS_VERSION' ) ? SNOW_MONKEY_BLOCKS_VERSION : null,
		'blocks'  => $blocks,
	];
}


// ---- Yoast SEO ----
function wpais_yoast() {
	if ( ! defined( 'WPSEO_VERSION' ) ) return null;

	$titles = get_option( 'wpseo_titles', [] );
	$social = get_option( 'wpseo_social', [] );

	$post_types = get_post_types( [ '_builtin' => false, 'public' => true ], 'names' );
	$noindex    = [];
	foreach ( $post_types as $pt ) {
		if ( ! empty( $titles[ 'noindex-' . $pt ] ) ) $noindex[] = $pt;
	}

	$sep_map = [
		'sc-dash'   => '–', 'sc-ndash' => '–', 'sc-mdash'  => '—',
		'sc-pipe'   => '|', 'sc-tilde' => '~', 'sc-arrow'  => '›',
		'sc-laquo'  => '«', 'sc-raquo' => '»', 'sc-lt'    => '<',
		'sc-gt'     => '>', 'sc-bullet' => '·',
	];
	$sep_code = $titles['separator'] ?? 'sc-dash';

	return [
		'version'              => WPSEO_VERSION,
		'breadcrumbs_enabled'  => ! empty( $titles['breadcrumbs-enable'] ),
		'breadcrumb_separator' => $titles['breadcrumbs-sep'] ?? '»',
		'separator'            => $sep_map[ $sep_code ] ?? $sep_code,
		'noindex_post_types'   => $noindex,
		'og_enabled'           => ! empty( $social['opengraph'] ),
		'twitter_enabled'      => ! empty( $social['twitter'] ),
	];
}


// ---- All in One SEO ----
function wpais_aioseo() {
	if ( ! defined( 'AIOSEO_VERSION' ) ) return null;

	$raw = get_option( 'aioseo_options', [] );
	if ( is_string( $raw ) ) $raw = json_decode( $raw, true );
	if ( ! is_array( $raw ) ) return [ 'version' => AIOSEO_VERSION ];

	$post_types = get_post_types( [ '_builtin' => false, 'public' => true ], 'names' );
	$noindex    = [];
	foreach ( $post_types as $pt ) {
		$meta = $raw['searchAppearance']['postTypes'][ $pt ]['advanced']['robotsMeta'] ?? [];
		if ( ! empty( $meta['noindex'] ) ) $noindex[] = $pt;
	}

	return [
		'version'              => AIOSEO_VERSION,
		'og_enabled'           => ! empty( $raw['social']['facebook']['general']['enableOpenGraph'] ),
		'twitter_enabled'      => ! empty( $raw['social']['twitter']['general']['enableTwitterCard'] ),
		'breadcrumbs_enabled'  => ! empty( $raw['breadcrumbs']['enable'] ),
		'breadcrumb_separator' => $raw['breadcrumbs']['separator'] ?? null,
		'separator'            => $raw['searchAppearance']['global']['separator'] ?? null,
		'noindex_post_types'   => $noindex,
	];
}


// ---- Contact Form 7 ----
function wpais_cf7() {
	if ( ! post_type_exists( 'wpcf7_contact_form' ) ) return null;

	$posts  = get_posts( [
		'post_type'      => 'wpcf7_contact_form',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	] );
	$result = [];

	foreach ( $posts as $post ) {
		$body     = (string) get_post_meta( $post->ID, '_form', true );
		$result[] = [
			'id'     => $post->ID,
			'title'  => $post->post_title,
			'fields' => wpais_cf7_parse_fields( $body ),
		];
	}

	return $result;
}

function wpais_cf7_parse_fields( $body ) {
	$result  = [];
	$exclude = [ 'submit', 'response', 'acceptance', 'recaptcha', 'recaptcha_v3', 'quiz', 'hidden', 'captchac', 'captchar' ];

	preg_match_all( '/\[([a-zA-Z0-9_-]+)(\*)?\s+([\w-]+)(?:\s[^\]]*)?]/', $body, $matches, PREG_SET_ORDER );

	foreach ( $matches as $m ) {
		if ( in_array( $m[1], $exclude, true ) ) continue;
		$result[] = [
			'type'     => $m[1],
			'name'     => $m[3],
			'required' => $m[2] === '*',
		];
	}

	return $result;
}


// ---- MW WP Form ----
function wpais_mwform() {
	if ( ! post_type_exists( 'mw-wp-form' ) ) return null;

	$posts  = get_posts( [
		'post_type'      => 'mw-wp-form',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	] );
	$result = [];

	foreach ( $posts as $post ) {
		$result[] = [
			'id'     => $post->ID,
			'title'  => $post->post_title,
			'fields' => wpais_mwform_parse_fields( $post->post_content ),
		];
	}

	return $result;
}

function wpais_mwform_parse_fields( $content ) {
	$result  = [];
	$exclude = [ 'mwform_submit', 'mwform_confirm', 'mwform_back', 'mwform_complete', 'mwform_unsubscribe' ];

	// [mwform_type name="fieldname" ...]
	preg_match_all( '/\[mwform_([a-zA-Z0-9_-]+)\s[^\]]*name=["\']([^"\']+)["\'][^\]]*\]/', $content, $matches, PREG_SET_ORDER );

	foreach ( $matches as $m ) {
		$tag = 'mwform_' . $m[1];
		if ( in_array( $tag, $exclude, true ) ) continue;
		$required = strpos( $m[0], 'required="true"' ) !== false || strpos( $m[0], "required='true'" ) !== false;
		$result[] = [
			'type'     => $m[1],
			'name'     => $m[2],
			'required' => $required,
		];
	}

	return $result;
}


// ---- WP Mail SMTP ----
function wpais_wpsmtp() {
	$options = null;
	foreach ( [ 'wpmailsmtp_options', 'wp_mail_smtp', 'wp_mail_smtp_options' ] as $key ) {
		$val = get_option( $key );
		if ( ! empty( $val ) && is_array( $val ) ) { $options = $val; break; }
	}
	if ( $options === null ) return null;

	$mail   = $options['mail'] ?? [];
	$mailer = $mail['mailer'] ?? 'unknown';

	$result = [
		'mailer'      => $mailer,
		'from_email'  => $mail['from_email'] ?? null,
		'from_name'   => $mail['from_name'] ?? null,
		'do_not_send' => ! empty( $options['general']['do_not_send'] ),
	];

	if ( $mailer === 'smtp' ) {
		$smtp           = $options['smtp'] ?? [];
		$result['smtp'] = [
			'host'       => $smtp['host'] ?? null,
			'port'       => isset( $smtp['port'] ) ? (int) $smtp['port'] : null,
			'encryption' => $smtp['encryption'] ?? null,
			'auth'       => ! empty( $smtp['autentication'] ),
			'username'   => $smtp['username'] ?? null,
		];
	}

	return $result;
}


// ---- ウィジェット ----
function wpais_widgets() {
	global $wp_registered_sidebars;

	$sidebars_widgets = get_option( 'sidebars_widgets', [] );
	$result           = [];

	foreach ( $sidebars_widgets as $sidebar_id => $widget_ids ) {
		if ( in_array( $sidebar_id, [ 'wp_inactive_widgets', 'array_version' ], true ) ) continue;
		if ( empty( $widget_ids ) || ! is_array( $widget_ids ) ) continue;

		$label   = $wp_registered_sidebars[ $sidebar_id ]['name'] ?? $sidebar_id;
		$widgets = [];

		foreach ( $widget_ids as $widget_id ) {
			if ( ! preg_match( '/^(.+)-(\d+)$/', $widget_id, $m ) ) continue;
			$type      = $m[1];
			$number    = (int) $m[2];
			$instances = get_option( 'widget_' . $type, [] );
			$instance  = $instances[ $number ] ?? [];

			$widgets[] = [
				'id'    => $widget_id,
				'type'  => $type,
				'title' => $instance['title'] ?? null,
			];
		}

		if ( $widgets ) {
			$result[] = [
				'sidebar' => $sidebar_id,
				'label'   => $label,
				'widgets' => $widgets,
			];
		}
	}

	return empty( $result ) ? null : $result;
}


// ---- ブロックパターン ----
function wpais_block_patterns() {
	if ( ! class_exists( 'WP_Block_Patterns_Registry' ) ) return null;

	$all    = WP_Block_Patterns_Registry::get_instance()->get_all_registered();
	$result = [];

	foreach ( $all as $pattern ) {
		if ( strpos( $pattern['name'], 'core/' ) === 0 ) continue;
		$result[] = [
			'name'        => $pattern['name'],
			'title'       => $pattern['title'],
			'categories'  => $pattern['categories'] ?? [],
			'description' => ! empty( $pattern['description'] ) ? $pattern['description'] : null,
		];
	}

	return empty( $result ) ? null : $result;
}


// ---- カスタムフィールドサンプル ----
function wpais_field_samples() {
	if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'get_field' ) ) return null;

	$groups = acf_get_field_groups();
	$result = [];

	foreach ( $groups as $group ) {
		$post_id = wpais_sample_post_for_group( $group );
		if ( ! $post_id ) continue;

		$fields = acf_get_fields( $group['key'] ) ?: [];
		if ( empty( $fields ) ) continue;

		$samples = [];
		foreach ( $fields as $field ) {
			$value = get_field( $field['key'], $post_id, false );
			if ( $value === null || $value === '' || $value === false ) continue;
			$samples[ $field['name'] ] = wpais_sample_value( $value );
		}

		if ( $samples ) {
			$result[] = [
				'group'   => $group['title'],
				'post_id' => $post_id,
				'samples' => $samples,
			];
		}
	}

	return empty( $result ) ? null : $result;
}

function wpais_sample_post_for_group( array $group ) {
	foreach ( ( $group['location'] ?? [] ) as $rule_group ) {
		foreach ( $rule_group as $rule ) {
			if ( $rule['operator'] !== '==' ) continue;

			if ( $rule['param'] === 'post_type' ) {
				$posts = get_posts( [ 'post_type' => $rule['value'], 'posts_per_page' => 1, 'post_status' => 'publish' ] );
				if ( $posts ) return $posts[0]->ID;
			}

			if ( $rule['param'] === 'post' ) {
				return (int) $rule['value'];
			}

			if ( $rule['param'] === 'page_type' && $rule['value'] === 'front_page' ) {
				$id = (int) get_option( 'page_on_front' );
				if ( $id ) return $id;
			}
		}
	}
	return null;
}

function wpais_sample_value( $value ) {
	if ( is_array( $value ) ) {
		if ( empty( $value ) ) return null;
		// リピーター / グループ
		if ( isset( $value[0] ) && is_array( $value[0] ) ) {
			return '[' . count( $value ) . ' items]';
		}
		return array_slice( $value, 0, 3 );
	}
	if ( is_object( $value ) ) {
		return isset( $value->ID ) ? '[post:' . $value->ID . ']' : (string) $value;
	}
	if ( is_string( $value ) && mb_strlen( $value ) > 100 ) {
		return mb_substr( $value, 0, 100 ) . '…';
	}
	return $value;
}


// ============================================================
// Markdown 変換
// ============================================================
function wpais_to_markdown( array $data ) {
	$lines = [];
	$site  = $data['site']['name'] ?? 'WordPress';

	$lines[] = "# WP AI Snapshot — {$site}";
	$lines[] = '';
	$lines[] = '生成日時: ' . ( $data['generated_at'] ?? '' );
	$lines[] = '';

	// ---- 環境情報 ----
	if ( isset( $data['environment'] ) ) {
		$env     = $data['environment'];
		$lines[] = '## 環境';
		$lines[] = '| 項目 | 値 |';
		$lines[] = '|---|---|';
		$lines[] = '| WordPress | ' . ( $env['wordpress_version'] ?? '' ) . ' |';
		$lines[] = '| PHP | ' . ( $env['php_version'] ?? '' ) . ' |';
		$lines[] = '';
	}

	// ---- サイト・テーマ ----
	if ( isset( $data['site'] ) || isset( $data['theme'] ) ) {
		$lines[] = '## サイト情報';
		$lines[] = '| 項目 | 値 |';
		$lines[] = '|---|---|';
		$lines[] = '| サイト名 | ' . ( $data['site']['name'] ?? '' ) . ' |';
		$lines[] = '| URL | ' . ( $data['site']['url'] ?? '' ) . ' |';
		if ( isset( $data['theme'] ) ) {
			$lines[] = '| テーマ | ' . ( $data['theme']['name'] ?? '' ) . ' v' . ( $data['theme']['version'] ?? '' ) . ' |';
		}
		$lines[] = '';
	}

	// ---- WordPress 設定 ----
	if ( isset( $data['settings'] ) ) {
		$s       = $data['settings'];
		$lines[] = '## WordPress 設定';
		$lines[] = '| 設定 | 値 |';
		$lines[] = '|---|---|';
		$lines[] = '| タイムゾーン | ' . ( $s['timezone'] ?? '' ) . ' |';
		$lines[] = '| 日付フォーマット | ' . ( $s['date_format'] ?? '' ) . ' |';
		$lines[] = '| パーマリンク | ' . ( $s['permalink'] ?? '' ) . ' |';
		$lines[] = '| 1ページの件数 | ' . ( $s['posts_per_page'] ?? '' ) . ' |';
		$lines[] = '| フロントページ | ' . ( $s['front_page_type'] === 'page' ? ( $s['front_page'] ?? '固定ページ' ) : '最新の投稿' ) . ' |';
		if ( ! empty( $s['posts_page'] ) ) {
			$lines[] = '| 投稿ページ | ' . $s['posts_page'] . ' |';
		}
		$lines[] = '';

		if ( ! empty( $s['image_sizes'] ) ) {
			$lines[] = '## 登録済み画像サイズ';
			$lines[] = '| サイズ名 | 幅 | 高さ | トリミング |';
			$lines[] = '|---|---|---|---|';
			foreach ( $s['image_sizes'] as $name => $size ) {
				$lines[] = '| ' . $name . ' | ' . $size['width'] . ' | ' . ( $size['height'] ?: '制限なし' ) . ' | ' . ( $size['crop'] ? 'あり' : 'なし' ) . ' |';
			}
			$lines[] = '';
		}
	}

	// ---- テンプレートファイル ----
	if ( ! empty( $data['template_files'] ) ) {
		$lines[] = '## テンプレートファイル';
		foreach ( $data['template_files'] as $dir => $files ) {
			$lines[] = '**' . ( $dir === 'root' ? 'テーマルート' : $dir . '/' ) . '**： ' . implode( ', ', $files );
		}
		$lines[] = '';
	}

	// ---- theme.json ----
	if ( ! empty( $data['theme_json'] ) ) {
		$lines[] = '## theme.json';
		$lines[] = '```json';
		$lines[] = json_encode( $data['theme_json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		$lines[] = '```';
		$lines[] = '';
	}

	// ---- プラグイン ----
	if ( isset( $data['plugins'] ) ) {
		$lines[] = '## 有効なプラグイン';
		foreach ( ( $data['plugins'] ?? [] ) as $plugin ) {
			$lines[] = '- **' . $plugin['name'] . '** v' . $plugin['version'];
		}
		$lines[] = '';
	}

	// ---- 固定ページ ----
	if ( isset( $data['pages'] ) ) {
		$lines[] = '## 固定ページ';
		$lines[] = '| ID | タイトル | スラッグ | テンプレート |';
		$lines[] = '|---|---|---|---|';
		foreach ( ( $data['pages'] ?? [] ) as $page ) {
			$lines[] = '| ' . $page['id'] . ' | ' . ( $page['parent'] ? '　└ ' : '' ) . $page['title'] . ' | ' . $page['slug'] . ' | ' . $page['template'] . ' |';
		}
		$lines[] = '';
	}

	// ---- カスタム投稿タイプ ----
	if ( isset( $data['post_types'] ) ) {
		$lines[] = '## カスタム投稿タイプ';
		foreach ( ( $data['post_types'] ?? [] ) as $pt ) {
			$lines[] = '### ' . $pt['label'] . ' (`' . $pt['name'] . '`) — ' . $pt['count'] . '件';
			$lines[] = '- supports: ' . implode( ', ', $pt['supports'] ?? [] );
			$lines[] = '- taxonomies: ' . implode( ', ', $pt['taxonomies'] ?? [] );
			$lines[] = '- has_archive: ' . ( $pt['has_archive'] ? 'true' : 'false' );
			$lines[] = '';
		}
	}

	// ---- タクソノミー ----
	if ( isset( $data['taxonomies'] ) ) {
		$lines[] = '## タクソノミー';
		foreach ( ( $data['taxonomies'] ?? [] ) as $tax ) {
			$lines[] = '### ' . $tax['label'] . ' (`' . $tax['name'] . '`)';
			$lines[] = '- 対象投稿タイプ: ' . implode( ', ', (array) ( $tax['object_type'] ?? [] ) );
			$lines[] = '- 階層: ' . ( $tax['hierarchical'] ? 'あり' : 'なし' );
			if ( ! empty( $tax['terms'] ) ) {
				$lines[] = '';
				$lines[] = '| term_id | 名前 | スラッグ | parent |';
				$lines[] = '|---|---|---|---|';
				foreach ( $tax['terms'] as $term ) {
					$lines[] = '| ' . $term['term_id'] . ' | ' . $term['name'] . ' | ' . $term['slug'] . ' | ' . $term['parent'] . ' |';
				}
			}
			$lines[] = '';
		}
	}

	// ---- ナビゲーションメニュー ----
	if ( isset( $data['menus'] ) ) {
		$lines[] = '## ナビゲーションメニュー';
		foreach ( ( $data['menus'] ?? [] ) as $menu ) {
			$lines[] = '### ' . ( $menu['menu_name'] ?? '未割り当て' ) . ' (`' . $menu['location'] . '`)';
			$lines[] = $menu['description'];
			if ( ! empty( $menu['items'] ) ) {
				$lines[] = '';
				$lines = array_merge( $lines, wpais_md_menu_items( $menu['items'] ) );
			}
			$lines[] = '';
		}
	}

	// ---- ACF / Secure Custom Fields フィールドグループ ----
	if ( ! empty( $data['field_groups'] ) ) {
		$lines[] = '## ACF / Secure Custom Fields フィールドグループ';
		foreach ( $data['field_groups'] as $group ) {
			$lines[] = '### ' . $group['title'];

			$loc_summaries = [];
			foreach ( ( $group['location'] ?? [] ) as $rule_group ) {
				foreach ( $rule_group as $rule ) {
					$loc_summaries[] = $rule['param'] . ' ' . $rule['operator'] . ' ' . $rule['value'];
				}
			}
			$lines[] = '適用条件: ' . implode( ' / ', $loc_summaries );
			$lines[] = '';
			$lines[] = '| フィールド名 | ラベル | タイプ | 備考 |';
			$lines[] = '|---|---|---|---|';

			foreach ( ( $group['fields'] ?? [] ) as $field ) {
				$note = '';
				if ( ! empty( $field['return_format'] ) ) $note .= 'return: ' . $field['return_format'];
				if ( ! empty( $field['choices'] ) )       $note .= ( $note ? ' / ' : '' ) . 'choices: ' . implode( ', ', array_keys( $field['choices'] ) );
				$lines[] = '| ' . $field['name'] . ' | ' . $field['label'] . ' | ' . $field['type'] . ' | ' . $note . ' |';

				if ( ! empty( $field['sub_fields'] ) ) {
					foreach ( $field['sub_fields'] as $sub ) {
						$sub_note = '';
						if ( ! empty( $sub['return_format'] ) ) $sub_note .= 'return: ' . $sub['return_format'];
						if ( ! empty( $sub['choices'] ) )       $sub_note .= ( $sub_note ? ' / ' : '' ) . 'choices: ' . implode( ', ', array_keys( $sub['choices'] ) );
						$lines[] = '| 　└ ' . $sub['name'] . ' | ' . $sub['label'] . ' | ' . $sub['type'] . ' | ' . $sub_note . ' |';
					}
				}
			}
			$lines[] = '';
		}
	}

	// ---- Smart Custom Fields フィールドグループ ----
	if ( ! empty( $data['smcf'] ) ) {
		$lines[] = '## Smart Custom Fields フィールドグループ';
		foreach ( $data['smcf'] as $group ) {
			$lines[] = '### ' . $group['title'];
			$lines[] = '適用条件: ' . wpais_smcf_condition_summary( $group['condition'] );
			$lines[] = '';

			foreach ( ( $group['groups'] ?? [] ) as $sg ) {
				$repeat  = $sg['repeat'] ? '（繰り返し）' : '';
				$lines[] = '**グループキー: ' . $sg['group_name'] . '**' . $repeat;
				if ( ! empty( $sg['fields'] ) ) {
					$lines[] = '| フィールド名 | ラベル | タイプ | 備考 |';
					$lines[] = '|---|---|---|---|';
					foreach ( $sg['fields'] as $field ) {
						$lines[] = '| ' . $field['name'] . ' | ' . $field['label'] . ' | ' . $field['type'] . ' | ' . ( $field['notes'] ?? '' ) . ' |';
					}
				}
				$lines[] = '';
			}
		}
	}

	// ---- Lazy Blocks ----
	if ( ! empty( $data['blocks'] ) ) {
		$lines[] = '## Lazy Blocks';
		foreach ( $data['blocks'] as $block ) {
			$lines[] = '### ' . $block['title'] . ' (`' . $block['slug'] . '`)';
			if ( ! empty( $block['controls'] ) ) {
				$lines[] = '| コントロール名 | ラベル | タイプ |';
				$lines[] = '|---|---|---|';
				foreach ( $block['controls'] as $ctrl ) {
					$lines[] = '| ' . ( $ctrl['name'] ?? '' ) . ' | ' . ( $ctrl['label'] ?? '' ) . ' | ' . ( $ctrl['type'] ?? '' ) . ' |';
				}
			}
			$lines[] = '';
		}
	}

	// ---- Snow Monkey Blocks ----
	if ( ! empty( $data['smb'] ) ) {
		$smb     = $data['smb'];
		$lines[] = '## Snow Monkey Blocks' . ( ! empty( $smb['version'] ) ? ' v' . $smb['version'] : '' );
		foreach ( ( $smb['blocks'] ?? [] ) as $block ) {
			$lines[] = '- ' . $block['title'] . ' (`' . $block['name'] . '`)';
		}
		$lines[] = '';
	}

	// ---- Yoast SEO ----
	if ( ! empty( $data['yoast'] ) ) {
		$y       = $data['yoast'];
		$lines[] = '## Yoast SEO';
		$lines[] = '| 設定 | 値 |';
		$lines[] = '|---|---|';
		$lines[] = '| バージョン | ' . ( $y['version'] ?? '' ) . ' |';
		$lines[] = '| パンくずリスト | ' . ( $y['breadcrumbs_enabled'] ? '有効' : '無効' ) . ' |';
		$lines[] = '| パンくず区切り文字 | ' . ( $y['breadcrumb_separator'] ?? '' ) . ' |';
		$lines[] = '| タイトル区切り文字 | ' . ( $y['separator'] ?? '' ) . ' |';
		$lines[] = '| OGP | ' . ( $y['og_enabled'] ? '有効' : '無効' ) . ' |';
		$lines[] = '| Twitter Card | ' . ( $y['twitter_enabled'] ? '有効' : '無効' ) . ' |';
		if ( ! empty( $y['noindex_post_types'] ) ) {
			$lines[] = '| noindex 投稿タイプ | ' . implode( ', ', $y['noindex_post_types'] ) . ' |';
		}
		$lines[] = '';
	}

	// ---- All in One SEO ----
	if ( ! empty( $data['aioseo'] ) ) {
		$a       = $data['aioseo'];
		$lines[] = '## All in One SEO';
		$lines[] = '| 設定 | 値 |';
		$lines[] = '|---|---|';
		$lines[] = '| バージョン | ' . ( $a['version'] ?? '' ) . ' |';
		$lines[] = '| パンくずリスト | ' . ( $a['breadcrumbs_enabled'] ? '有効' : '無効' ) . ' |';
		if ( ! empty( $a['breadcrumb_separator'] ) ) {
			$lines[] = '| パンくず区切り文字 | ' . $a['breadcrumb_separator'] . ' |';
		}
		if ( ! empty( $a['separator'] ) ) {
			$lines[] = '| タイトル区切り文字 | ' . $a['separator'] . ' |';
		}
		$lines[] = '| OGP | ' . ( $a['og_enabled'] ? '有効' : '無効' ) . ' |';
		$lines[] = '| Twitter Card | ' . ( $a['twitter_enabled'] ? '有効' : '無効' ) . ' |';
		if ( ! empty( $a['noindex_post_types'] ) ) {
			$lines[] = '| noindex 投稿タイプ | ' . implode( ', ', $a['noindex_post_types'] ) . ' |';
		}
		$lines[] = '';
	}

	// ---- Contact Form 7 ----
	if ( ! empty( $data['cf7'] ) ) {
		$lines[] = '## Contact Form 7';
		foreach ( $data['cf7'] as $form ) {
			$lines[] = '### ' . $form['title'] . ' (ID: ' . $form['id'] . ')';
			if ( ! empty( $form['fields'] ) ) {
				$lines[] = '| フィールド名 | タイプ | 必須 |';
				$lines[] = '|---|---|---|';
				foreach ( $form['fields'] as $field ) {
					$lines[] = '| ' . $field['name'] . ' | ' . $field['type'] . ' | ' . ( $field['required'] ? '✓' : '' ) . ' |';
				}
			}
			$lines[] = '';
		}
	}

	// ---- MW WP Form ----
	if ( ! empty( $data['mwform'] ) ) {
		$lines[] = '## MW WP Form';
		foreach ( $data['mwform'] as $form ) {
			$lines[] = '### ' . $form['title'] . ' (ID: ' . $form['id'] . ')';
			if ( ! empty( $form['fields'] ) ) {
				$lines[] = '| フィールド名 | タイプ | 必須 |';
				$lines[] = '|---|---|---|';
				foreach ( $form['fields'] as $field ) {
					$lines[] = '| ' . $field['name'] . ' | ' . $field['type'] . ' | ' . ( $field['required'] ? '✓' : '' ) . ' |';
				}
			}
			$lines[] = '';
		}
	}

	// ---- WP Mail SMTP ----
	if ( ! empty( $data['wpsmtp'] ) ) {
		$s       = $data['wpsmtp'];
		$lines[] = '## WP Mail SMTP';
		$lines[] = '| 設定 | 値 |';
		$lines[] = '|---|---|';
		$lines[] = '| 送信方法 | ' . ( $s['mailer'] ?? '' ) . ' |';
		$lines[] = '| From メール | ' . ( $s['from_email'] ?? '' ) . ' |';
		$lines[] = '| From 名前 | ' . ( $s['from_name'] ?? '' ) . ' |';
		$lines[] = '| 送信停止 | ' . ( $s['do_not_send'] ? 'はい（テストモード）' : 'いいえ' ) . ' |';
		if ( ! empty( $s['smtp'] ) ) {
			$smtp    = $s['smtp'];
			$lines[] = '| SMTP ホスト | ' . ( $smtp['host'] ?? '' ) . ' |';
			$lines[] = '| SMTP ポート | ' . ( $smtp['port'] ?? '' ) . ' |';
			$lines[] = '| 暗号化 | ' . ( $smtp['encryption'] ?? '' ) . ' |';
			$lines[] = '| 認証 | ' . ( $smtp['auth'] ? '有効' : '無効' ) . ' |';
			if ( ! empty( $smtp['username'] ) ) {
				$lines[] = '| ユーザー名 | ' . $smtp['username'] . ' |';
			}
		}
		$lines[] = '';
	}

	// ---- ウィジェット ----
	if ( ! empty( $data['widgets'] ) ) {
		$lines[] = '## ウィジェット';
		foreach ( $data['widgets'] as $sidebar ) {
			$lines[] = '### ' . $sidebar['label'] . ' (`' . $sidebar['sidebar'] . '`)';
			foreach ( $sidebar['widgets'] as $w ) {
				$label   = $w['title'] ? $w['type'] . '（' . $w['title'] . '）' : $w['type'];
				$lines[] = '- ' . $label;
			}
			$lines[] = '';
		}
	}

	// ---- ブロックパターン ----
	if ( ! empty( $data['block_patterns'] ) ) {
		$lines[] = '## ブロックパターン';
		$lines[] = '| 名前 | タイトル | カテゴリ |';
		$lines[] = '|---|---|---|';
		foreach ( $data['block_patterns'] as $pattern ) {
			$cats    = ! empty( $pattern['categories'] ) ? implode( ', ', (array) $pattern['categories'] ) : '';
			$lines[] = '| `' . $pattern['name'] . '` | ' . $pattern['title'] . ' | ' . $cats . ' |';
		}
		$lines[] = '';
	}

	// ---- カスタムフィールドサンプル ----
	if ( ! empty( $data['field_samples'] ) ) {
		$lines[] = '## カスタムフィールドサンプル';
		foreach ( $data['field_samples'] as $item ) {
			$lines[] = '### ' . $item['group'] . '（投稿ID: ' . $item['post_id'] . '）';
			$lines[] = '| フィールド名 | サンプル値 |';
			$lines[] = '|---|---|';
			foreach ( $item['samples'] as $name => $value ) {
				$display = is_array( $value ) ? implode( ', ', array_map( 'strval', $value ) ) : (string) $value;
				$lines[] = '| ' . $name . ' | ' . str_replace( "\n", ' ', $display ) . ' |';
			}
			$lines[] = '';
		}
	}

	return implode( "\n", $lines );
}

function wpais_md_menu_items( array $items, $depth = 0 ) {
	$lines  = [];
	$indent = str_repeat( '  ', $depth );

	foreach ( $items as $item ) {
		$lines[] = $indent . '- ' . $item['title'] . ' → ' . $item['url'];
		if ( ! empty( $item['children'] ) ) {
			$lines = array_merge( $lines, wpais_md_menu_items( $item['children'], $depth + 1 ) );
		}
	}

	return $lines;
}
