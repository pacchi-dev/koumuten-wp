<?php
/**
 * セキュリティ・不要出力の抑制。
 *
 * WordPress は初期状態で「攻撃者に有用な情報」を出力し、
 * 使われていない機能を有効にしたまま公開される。
 * 制作案件では納品前に整理する定番項目をまとめている。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

/**
 * wp_head から不要なタグを削除する。
 *
 * remove_action() は WordPress 本体が登録済みの action を外す仕組み。
 * 「本体の挙動を止める」のは hooks の代表的な使い方で、
 * 既存サイトの改修案件でも頻繁に使う。
 */
add_action( 'init', 'sk_clean_wp_head' );
function sk_clean_wp_head(): void {

	/*
	 * <meta name="generator" content="WordPress 7.1" />
	 * バージョンが露出すると、既知の脆弱性を狙った探索の対象になりやすい。
	 */
	remove_action( 'wp_head', 'wp_generator' );

	// Really Simple Discovery / Windows Live Writer。現在は利用されていない。
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );

	// 隣接記事への rel リンク。
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
	remove_action( 'wp_head', 'start_post_rel_link', 10 );
}

/**
 * XML-RPC を無効化する。
 *
 * 外部クライアントからの投稿用 API だが、現在はほぼ使われず、
 * system.multicall による総当たり攻撃の増幅に悪用される。
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * ログイン失敗時のメッセージを共通化する。
 *
 * 初期状態では「ユーザー名が違う」「パスワードが違う」を区別して返すため、
 * ユーザー名が実在するかどうかを判定できてしまう。
 */
add_filter( 'login_errors', 'sk_generic_login_error' );
function sk_generic_login_error(): string {
	return __( 'ログイン情報が正しくありません。', 'soranowa-koumuten' );
}

/**
 * REST API 経由のユーザー一覧取得を、ログインユーザーに限定する。
 *
 * /wp-json/wp/v2/users は初期状態で未認証でも閲覧でき、
 * 投稿者のログイン名が漏れる。ユーザー列挙の代表的な経路。
 *
 * REST API 自体は無効化しない。Contact Form 7 が送信に使っており、
 * ブロックエディタも依存しているため。
 *
 * @param WP_Error|null|true $result 認証結果。
 * @return WP_Error|null|true
 */
add_filter( 'rest_authentication_errors', 'sk_restrict_rest_user_endpoint' );
function sk_restrict_rest_user_endpoint( $result ) {
	// 既に他のフィルタがエラーを返している場合はそれを尊重する。
	if ( ! empty( $result ) ) {
		return $result;
	}

	$route = isset( $GLOBALS['wp']->query_vars['rest_route'] )
		? (string) $GLOBALS['wp']->query_vars['rest_route']
		: '';

	if ( str_starts_with( $route, '/wp/v2/users' ) && ! is_user_logged_in() ) {
		return new WP_Error(
			'rest_forbidden',
			__( 'このエンドポイントの利用には認証が必要です。', 'soranowa-koumuten' ),
			array( 'status' => 401 )
		);
	}

	return $result;
}

/**
 * 著者アーカイブ（/?author=1）を無効化する。
 *
 * ID を指定すると投稿者のログイン名を含む URL へリダイレクトされるため、
 * ユーザー列挙の経路になる。コーポレートサイトでは使わない機能。
 */
add_action( 'template_redirect', 'sk_disable_author_archive' );
function sk_disable_author_archive(): void {
	if ( is_author() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
