<?php
/**
 * Plugin Name: WP AI Snapshot
 * Description: 投稿タイプ・タクソノミー・SCFフィールド・Lazy Blocks等のWordPress構成情報をJSON / Markdownとして一括出力します。AIへ渡す際にお使いください。
 * Version:     1.1.0
 * Author:      den-web
 */

if ( ! defined( 'ABSPATH' ) ) exit;


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
	?>
	<div class="wrap">
		<h1>AI Snapshot</h1>
		<p>WordPressの構成情報（投稿タイプ・タクソノミー・SCFフィールド・Lazy Blocks・固定ページ・プラグイン・設定等）を出力します。</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
			<?php wp_nonce_field( 'wpais_download' ); ?>
			<input type="hidden" name="action" value="wpais_download_json">
			<button type="submit" class="button button-primary">JSON をダウンロード</button>
		</form>
		&nbsp;
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
			<?php wp_nonce_field( 'wpais_download' ); ?>
			<input type="hidden" name="action" value="wpais_download_md">
			<button type="submit" class="button button-secondary">Markdown をダウンロード</button>
		</form>
	</div>
	<?php
}


// ============================================================
// ダウンロード処理 — JSON
// ============================================================
add_action( 'admin_post_wpais_download_json', function () {
	check_admin_referer( 'wpais_download' );
	if ( ! current_user_can( 'manage_options' ) ) wp_die( '権限がありません。' );

	try {
		$data     = wpais_generate();
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
		$data     = wpais_generate();
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
// データ収集
// ============================================================
function wpais_generate() {
	return [
		'generated_at'    => current_time( 'c' ),
		'environment'     => wpais_environment(),
		'site'            => wpais_site(),
		'theme'           => wpais_theme(),
		'theme_json'      => wpais_theme_json(),
		'template_files'  => wpais_template_files(),
		'settings'        => wpais_settings(),
		'plugins'         => wpais_plugins(),
		'pages'           => wpais_pages(),
		'post_types'      => wpais_post_types(),
		'taxonomies'      => wpais_taxonomies(),
		'menus'           => wpais_menus(),
		'field_groups'    => wpais_scf(),
		'blocks'          => wpais_lazy_blocks(),
		'yoast'           => wpais_yoast(),
	];
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

	// テーマルートの .php ファイル
	$root_files = glob( $theme_dir . '/*.php' );
	sort( $root_files );
	foreach ( $root_files as $file ) {
		$result['root'][] = basename( $file );
	}

	// サブディレクトリ（inc/ など）の .php ファイル
	$subdirs = array_filter( glob( $theme_dir . '/*' ), 'is_dir' );
	foreach ( $subdirs as $dir ) {
		$dirname = basename( $dir );
		// システム用・データ用ディレクトリは除外
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
	$front_type    = get_option( 'show_on_front' ); // 'posts' or 'page'
	$front_page_id = get_option( 'page_on_front' );
	$posts_page_id = get_option( 'page_for_posts' );

	// 登録済み画像サイズ（デフォルト0のもの・WPコアが自動追加するレスポンシブサイズは除外）
	$raw_sizes          = wp_get_registered_image_subsizes();
	$wp_auto_sizes      = [ 'medium_large', '1536x1536', '2048x2048' ];
	$image_sizes        = [];
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
		'timezone'          => get_option( 'timezone_string' ) ?: get_option( 'gmt_offset' ),
		'date_format'       => get_option( 'date_format' ),
		'permalink'         => get_option( 'permalink_structure' ),
		'posts_per_page'    => (int) get_option( 'posts_per_page' ),
		'front_page_type'   => $front_type,
		'front_page'        => ( $front_type === 'page' && $front_page_id )
			? get_the_title( $front_page_id ) : null,
		'posts_page'        => ( $posts_page_id )
			? get_the_title( $posts_page_id ) : null,
		'image_sizes'       => $image_sizes,
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

	// ID をキーにしたマップを作り、親→子の順に並べ直す
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

	// 再帰的に親→子の順で $result に追加
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

	// 除外する投稿タイプ（SCF・Lazy Blocks の内部用）
	$exclude = [ 'acf-taxonomy', 'acf-post-type', 'acf-ui-options-page', 'acf-field-group', 'lazyblocks', 'lazyblocks_templates' ];

	foreach ( $post_types as $pt ) {
		if ( in_array( $pt->name, $exclude, true ) ) continue;
		if ( ! $pt->public && ! $pt->show_ui ) continue;

		$result[] = [
			'name'         => $pt->name,
			'label'        => $pt->label,
			'singular'     => $pt->labels->singular_name ?? '',
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


// ---- ナビゲーションメニュー ----
function wpais_menus() {
	$locations = get_registered_nav_menus();
	$assigned  = get_nav_menu_locations();
	$result    = [];

	foreach ( $locations as $location => $description ) {
		$menu_id  = $assigned[ $location ] ?? null;
		$menu     = $menu_id ? wp_get_nav_menu_object( $menu_id ) : null;
		$result[] = [
			'location'    => $location,
			'description' => $description,
			'menu_name'   => $menu ? $menu->name : null,
		];
	}

	return $result;
}


// ---- SCF / ACF フィールドグループ ----
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

		if ( ! empty( $field['choices'] ) ) {
			$item['choices'] = $field['choices'];
		}
		if ( isset( $field['return_format'] ) ) {
			$item['return_format'] = $field['return_format'];
		}
		if ( ! empty( $field['sub_fields'] ) ) {
			$item['sub_fields'] = wpais_scf_fields( $field['sub_fields'] );
		}

		$result[] = $item;
	}

	return $result;
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


// ---- Yoast SEO ----
function wpais_yoast() {
	if ( ! defined( 'WPSEO_VERSION' ) ) return null; // Yoast SEO 未導入

	$titles   = get_option( 'wpseo_titles', [] );
	$social   = get_option( 'wpseo_social', [] );

	// 投稿タイプ別 noindex 設定
	$post_types = get_post_types( [ '_builtin' => false, 'public' => true ], 'names' );
	$noindex    = [];
	foreach ( $post_types as $pt ) {
		$key = 'noindex-' . $pt;
		if ( ! empty( $titles[ $key ] ) ) {
			$noindex[] = $pt;
		}
	}

	// Yoast 内部コードを実際の文字に変換
	$sep_map = [
		'sc-dash'   => '–',
		'sc-ndash'  => '–',
		'sc-mdash'  => '—',
		'sc-pipe'   => '|',
		'sc-tilde'  => '~',
		'sc-arrow'  => '›',
		'sc-laquo'  => '«',
		'sc-raquo'  => '»',
		'sc-lt'     => '<',
		'sc-gt'     => '>',
		'sc-bullet' => '·',
	];
	$sep_code = $titles['separator'] ?? 'sc-dash';

	return [
		'version'             => WPSEO_VERSION,
		'breadcrumbs_enabled' => ! empty( $titles['breadcrumbs-enable'] ),
		'breadcrumb_separator'=> $titles['breadcrumbs-sep'] ?? '»',
		'separator'           => $sep_map[ $sep_code ] ?? $sep_code,
		'noindex_post_types'  => $noindex,
		'og_enabled'          => ! empty( $social['opengraph'] ),
		'twitter_enabled'     => ! empty( $social['twitter'] ),
	];
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
	$env = $data['environment'] ?? [];
	$lines[] = '## 環境';
	$lines[] = '| 項目 | 値 |';
	$lines[] = '|---|---|';
	$lines[] = '| WordPress | ' . ( $env['wordpress_version'] ?? '' ) . ' |';
	$lines[] = '| PHP | ' . ( $env['php_version'] ?? '' ) . ' |';
	$lines[] = '';

	// ---- サイト・テーマ ----
	$lines[] = '## サイト情報';
	$lines[] = '| 項目 | 値 |';
	$lines[] = '|---|---|';
	$lines[] = '| サイト名 | ' . ( $data['site']['name'] ?? '' ) . ' |';
	$lines[] = '| URL | ' . ( $data['site']['url'] ?? '' ) . ' |';
	$lines[] = '| テーマ | ' . ( $data['theme']['name'] ?? '' ) . ' v' . ( $data['theme']['version'] ?? '' ) . ' |';
	$lines[] = '';

	// ---- WordPress 設定 ----
	$lines[] = '## WordPress 設定';
	$lines[] = '| 設定 | 値 |';
	$lines[] = '|---|---|';
	$s = $data['settings'] ?? [];
	$lines[] = '| タイムゾーン | ' . ( $s['timezone'] ?? '' ) . ' |';
	$lines[] = '| 日付フォーマット | ' . ( $s['date_format'] ?? '' ) . ' |';
	$lines[] = '| パーマリンク | ' . ( $s['permalink'] ?? '' ) . ' |';
	$lines[] = '| 1ページの件数 | ' . ( $s['posts_per_page'] ?? '' ) . ' |';
	$lines[] = '| フロントページ | ' . ( $s['front_page_type'] === 'page' ? ( $s['front_page'] ?? '固定ページ' ) : '最新の投稿' ) . ' |';
	if ( ! empty( $s['posts_page'] ) ) {
		$lines[] = '| 投稿ページ | ' . $s['posts_page'] . ' |';
	}
	$lines[] = '';

	// ---- 画像サイズ ----
	if ( ! empty( $s['image_sizes'] ) ) {
		$lines[] = '## 登録済み画像サイズ';
		$lines[] = '| サイズ名 | 幅 | 高さ | トリミング |';
		$lines[] = '|---|---|---|---|';
		foreach ( $s['image_sizes'] as $name => $size ) {
			$crop    = $size['crop'] ? 'あり' : 'なし';
			$height  = $size['height'] ?: '制限なし';
			$lines[] = '| ' . $name . ' | ' . $size['width'] . ' | ' . $height . ' | ' . $crop . ' |';
		}
		$lines[] = '';
	}

	// ---- テンプレートファイル ----
	if ( ! empty( $data['template_files'] ) ) {
		$lines[] = '## テンプレートファイル';
		foreach ( $data['template_files'] as $dir => $files ) {
			$label   = $dir === 'root' ? 'テーマルート' : $dir . '/';
			$lines[] = '**' . $label . '**： ' . implode( ', ', $files );
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
	$lines[] = '## 有効なプラグイン';
	foreach ( ( $data['plugins'] ?? [] ) as $plugin ) {
		$lines[] = '- **' . $plugin['name'] . '** v' . $plugin['version'];
	}
	$lines[] = '';

	// ---- 固定ページ ----
	$lines[] = '## 固定ページ';
	$lines[] = '| ID | タイトル | スラッグ | テンプレート |';
	$lines[] = '|---|---|---|---|';
	foreach ( ( $data['pages'] ?? [] ) as $page ) {
		$indent   = $page['parent'] ? '　└ ' : '';
		$lines[] = '| ' . $page['id'] . ' | ' . $indent . $page['title'] . ' | ' . $page['slug'] . ' | ' . $page['template'] . ' |';
	}
	$lines[] = '';

	// ---- カスタム投稿タイプ ----
	$lines[] = '## カスタム投稿タイプ';
	foreach ( ( $data['post_types'] ?? [] ) as $pt ) {
		$lines[] = '### ' . $pt['label'] . ' (`' . $pt['name'] . '`)';
		$lines[] = '- supports: ' . implode( ', ', $pt['supports'] ?? [] );
		$lines[] = '- taxonomies: ' . implode( ', ', $pt['taxonomies'] ?? [] );
		$lines[] = '- has_archive: ' . ( $pt['has_archive'] ? 'true' : 'false' );
		$lines[] = '';
	}

	// ---- タクソノミー ----
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

	// ---- ナビゲーションメニュー ----
	$lines[] = '## ナビゲーションメニュー';
	$lines[] = '| ロケーション | 説明 | 割り当てメニュー |';
	$lines[] = '|---|---|---|';
	foreach ( ( $data['menus'] ?? [] ) as $menu ) {
		$lines[] = '| ' . $menu['location'] . ' | ' . $menu['description'] . ' | ' . ( $menu['menu_name'] ?? '未割り当て' ) . ' |';
	}
	$lines[] = '';

	// ---- SCF フィールドグループ ----
	if ( ! empty( $data['field_groups'] ) ) {
		$lines[] = '## SCF フィールドグループ';
		foreach ( $data['field_groups'] as $group ) {
			$lines[] = '### ' . $group['title'];

			// location サマリー
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

				// サブフィールド（リピーター等）
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

	return implode( "\n", $lines );
}
