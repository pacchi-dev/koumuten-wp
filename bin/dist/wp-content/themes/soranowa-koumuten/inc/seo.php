<?php
/**
 * SEO 関連の出力（description / OGP / 構造化データ）。
 *
 * SEO プラグイン（Yoast / All in One SEO）を入れない方針のため自前で出す。
 * 工務店サイトで必要なのは、検索結果での見え方と地図検索への露出であり、
 * そのために必要なのは description・OGP・LocalBusiness の 3 つに絞れる。
 * プラグインを 1 つ減らせば、その更新と互換性の面倒も減る。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

/**
 * ページの説明文を組み立てる。
 *
 * @return string
 */
function sk_meta_description(): string {

	$description = '';

	if ( is_front_page() ) {
		$description = function_exists( 'get_field' )
			? (string) get_field( 'hero_text', (int) get_option( 'page_on_front' ) )
			: '';

		if ( '' === $description ) {
			$description = (string) get_bloginfo( 'description' );
		}
	} elseif ( is_singular( 'work' ) ) {
		$parts = array_filter(
			array(
				sk_first_term_name( get_the_ID(), 'work_area' ),
				sk_first_term_name( get_the_ID(), 'work_category' ),
				(string) get_the_excerpt(),
			)
		);

		$description = implode( ' ', $parts );
	} elseif ( is_singular() ) {
		$description = (string) get_the_excerpt();
	} elseif ( is_post_type_archive( 'work' ) ) {
		$description = __( '姫路を中心に手がけた注文住宅・リノベーションの施工実績です。種別ごとに絞り込んでご覧いただけます。', 'soranowa-koumuten' );
	} elseif ( is_tax() || is_category() ) {
		$description = (string) term_description();
	}

	$description = wp_strip_all_tags( $description );

	if ( '' === trim( $description ) ) {
		$description = (string) get_bloginfo( 'description' );
	}

	return mb_strimwidth( trim( $description ), 0, 140, '…' );
}

/**
 * OGP 用の画像 URL を返す。
 *
 * @return string
 */
function sk_ogp_image(): string {

	if ( is_singular() && has_post_thumbnail() ) {
		$url = get_the_post_thumbnail_url( get_the_ID(), 'sk-hero-md' );

		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	$front_id   = (int) get_option( 'page_on_front' );
	$hero_image = ( $front_id > 0 && function_exists( 'get_field' ) ) ? (int) get_field( 'hero_image', $front_id ) : 0;

	if ( $hero_image > 0 ) {
		$url = wp_get_attachment_image_url( $hero_image, 'sk-hero-md' );

		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	return '';
}

/**
 * description と OGP を head に出力する。
 *
 * wp_head は </head> の直前で走る action hook。
 * テンプレートに直接書かず hook 経由にしておくと、
 * どのテンプレートでも同じ出力が保証される。
 */
add_action( 'wp_head', 'sk_output_meta_tags', 5 );
function sk_output_meta_tags(): void {

	$description = sk_meta_description();
	$title       = wp_get_document_title();
	$image       = sk_ogp_image();

	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );

	printf( '<meta property="og:type" content="%s">' . "\n", is_singular() && ! is_front_page() ? 'article' : 'website' );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( (string) get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:locale" content="%s">' . "\n", 'ja_JP' );

	$url = is_singular() ? (string) get_permalink() : home_url( add_query_arg( array() ) );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );

	if ( '' !== $image ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
		printf( '<meta name="twitter:card" content="%s">' . "\n", 'summary_large_image' );
	} else {
		printf( '<meta name="twitter:card" content="%s">' . "\n", 'summary' );
	}

	// 正規 URL。ページ送りやクエリ付き URL の重複を避ける。
	if ( is_singular() ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( (string) get_permalink() ) );
	}
}

/**
 * 構造化データ（JSON-LD）を出力する。
 *
 * LocalBusiness  … 地図検索・ナレッジパネル向け。工務店では最も効く
 * BreadcrumbList … 検索結果のパンくず表示
 * Article        … お知らせ記事
 *
 * wp_json_encode() を使うのは、エスケープと文字コードの扱いを
 * WordPress 側に任せるため（json_encode の生呼び出しは避ける）。
 */
add_action( 'wp_head', 'sk_output_structured_data', 6 );
function sk_output_structured_data(): void {

	$graph = array();

	// --- LocalBusiness -----------------------------------------------------
	$business = array(
		'@type'       => 'GeneralContractor',
		'@id'         => home_url( '/#organization' ),
		'name'        => sk_company( 'name', (string) get_bloginfo( 'name' ) ),
		'url'         => home_url( '/' ),
		'telephone'   => sk_company( 'tel', '' ),
		'address'     => array(
			'@type'           => 'PostalAddress',
			'addressCountry'  => 'JP',
			'addressRegion'   => '兵庫県',
			'postalCode'      => sk_company( 'zip', '' ),
			'streetAddress'   => sk_company( 'address', '' ),
		),
		'areaServed'  => sk_company( 'area', '' ),
		'openingHours' => sk_company( 'hours', '' ),
	);

	$image = sk_ogp_image();

	if ( '' !== $image ) {
		$business['image'] = $image;
	}

	$graph[] = $business;

	// --- BreadcrumbList ----------------------------------------------------
	$items = sk_breadcrumb_items();

	if ( count( $items ) > 1 ) {
		$elements = array();

		foreach ( $items as $i => $item ) {
			$element = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $item['label'],
			);

			if ( '' !== $item['url'] ) {
				$element['item'] = $item['url'];
			}

			$elements[] = $element;
		}

		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $elements,
		);
	}

	// --- Article -----------------------------------------------------------
	if ( is_singular( 'post' ) ) {
		$graph[] = array(
			'@type'            => 'Article',
			'headline'         => get_the_title(),
			'datePublished'    => get_the_date( 'c' ),
			'dateModified'     => get_the_modified_date( 'c' ),
			'mainEntityOfPage' => (string) get_permalink(),
			'publisher'        => array( '@id' => home_url( '/#organization' ) ),
		);
	}

	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}
