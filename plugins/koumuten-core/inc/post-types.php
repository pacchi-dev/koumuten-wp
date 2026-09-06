<?php
/**
 * カスタム投稿タイプとタクソノミー。
 *
 * WordPress では「投稿（post）」と「固定ページ（page）」以外のコンテンツ種別を
 * register_post_type() で追加する。Laravel でいえばモデルとルーティングを
 * まとめて宣言するイメージに近い。一覧・詳細のテンプレートは
 * ファイル名の規約（archive-work.php / single-work.php）で自動的に紐づく。
 *
 * @package KoumutenCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * 施工実績 CPT を登録する。
 */
add_action( 'init', 'kc_register_post_types' );
function kc_register_post_types(): void {

	register_post_type(
		'work',
		array(
			'labels'        => array(
				'name'               => __( '施工実績', 'koumuten-core' ),
				'singular_name'      => __( '施工実績', 'koumuten-core' ),
				'add_new'            => __( '新規追加', 'koumuten-core' ),
				'add_new_item'       => __( '施工実績を追加', 'koumuten-core' ),
				'edit_item'          => __( '施工実績を編集', 'koumuten-core' ),
				'all_items'          => __( '施工実績一覧', 'koumuten-core' ),
				'search_items'       => __( '施工実績を検索', 'koumuten-core' ),
				'not_found'          => __( '施工実績が見つかりませんでした', 'koumuten-core' ),
				'featured_image'     => __( 'メイン写真', 'koumuten-core' ),
				'set_featured_image' => __( 'メイン写真を設定', 'koumuten-core' ),
			),
			'public'        => true,
			// アーカイブ（/works/）を持たせる。false だと一覧ページが作られない。
			'has_archive'   => 'works',
			'rewrite'       => array(
				'slug'       => 'works',
				'with_front' => false,
			),
			'menu_position' => 5,
			'menu_icon'     => 'dashicons-admin-home',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			// ブロックエディタを使うには REST API への登録が必須。
			'show_in_rest'  => true,
		)
	);
}

/**
 * 施工実績のタクソノミーを登録する。
 *
 * 種別（work_category）とエリア（work_area）に分ける。
 * 1 つのタクソノミーにまとめると「注文住宅かつ姫路市」の絞り込みができない。
 * 実サイト（はだしの家）でも種別タブによる絞り込みが実装されていた。
 */
add_action( 'init', 'kc_register_taxonomies' );
function kc_register_taxonomies(): void {

	register_taxonomy(
		'work_category',
		'work',
		array(
			'labels'            => array(
				'name'          => __( '種別', 'koumuten-core' ),
				'singular_name' => __( '種別', 'koumuten-core' ),
				'add_new_item'  => __( '種別を追加', 'koumuten-core' ),
			),
			// 階層あり（カテゴリー型）。チェックボックスで選ばせたいため。
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'work-category',
				'with_front' => false,
			),
		)
	);

	register_taxonomy(
		'work_area',
		'work',
		array(
			'labels'            => array(
				'name'          => __( 'エリア', 'koumuten-core' ),
				'singular_name' => __( 'エリア', 'koumuten-core' ),
				'add_new_item'  => __( 'エリアを追加', 'koumuten-core' ),
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array(
				'slug'       => 'work-area',
				'with_front' => false,
			),
		)
	);
}

/**
 * 初期タームを投入する。
 *
 * 有効化時に一度だけ実行される。既にあれば作らない（冪等）。
 * 運営者が管理画面から自由に追加・削除できる。
 */
function kc_insert_default_terms(): void {

	/*
	 * スラッグを明示する。
	 * 日本語のターム名をそのまま入れると、スラッグがパーセントエンコードされ
	 * /work-category/%e6%b3%a8%e6%96%87%e4%bd%8f%e5%ae%85/ のような URL になる。
	 * 共有もリンクの見た目も悪くなるため、英字スラッグを与える。
	 */
	$defaults = array(
		'work_category' => array(
			'注文住宅'         => 'custom-house',
			'リノベーション'   => 'renovation',
			'増改築'           => 'extension',
			'外構'             => 'exterior',
		),
		'work_area'     => array(
			'姫路市'     => 'himeji',
			'たつの市'   => 'tatsuno',
			'加古川市'   => 'kakogawa',
			'高砂市'     => 'takasago',
		),
	);

	foreach ( $defaults as $taxonomy => $terms ) {
		foreach ( $terms as $name => $slug ) {
			$existing = term_exists( $name, $taxonomy );

			if ( ! $existing ) {
				wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
				continue;
			}

			// 既存タームのスラッグが日本語のままなら英字に直す。
			$term = get_term( (int) $existing['term_id'], $taxonomy );

			if ( $term instanceof WP_Term && $term->slug !== $slug ) {
				wp_update_term( $term->term_id, $taxonomy, array( 'slug' => $slug ) );
			}
		}
	}
}

/**
 * 施工実績アーカイブの表示件数を増やす。
 *
 * pre_get_posts はメインクエリを実行前に書き換える filter 相当の hook。
 * テンプレート側で WP_Query を新規に作ると、ページネーションが
 * メインクエリとずれるため、アーカイブの条件変更はここで行うのが定石。
 *
 * @param WP_Query $query クエリ。
 */
add_action( 'pre_get_posts', 'kc_adjust_work_archive_query' );
function kc_adjust_work_archive_query( WP_Query $query ): void {
	// 管理画面と、メインクエリ以外には触らない。
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_post_type_archive( 'work' ) || $query->is_tax( array( 'work_category', 'work_area' ) ) ) {
		$query->set( 'posts_per_page', 12 );
		$query->set( 'orderby', 'date' );
		$query->set( 'order', 'DESC' );
	}
}
