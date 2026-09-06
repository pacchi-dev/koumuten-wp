<?php
/**
 * テンプレートから呼ぶ共通の出力関数。
 *
 * テンプレートファイルに同じ HTML を繰り返し書かないためのもの。
 * WordPress では「テンプレートタグ」と呼ばれ、the_title() 等と同じ役割を持つ。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

/**
 * 添付ファイル ID から img を出力する。
 *
 * wp_get_attachment_image() を使うのは、WordPress が
 * srcset / sizes / loading / decoding を自動で組み立ててくれるため。
 * <img src="..."> を手書きすると、この自動付与がすべて効かない。
 *
 * @param int    $attachment_id 添付ファイル ID。
 * @param string $size          登録済みの画像サイズ名。
 * @param array{sizes?: string, class?: string, alt?: string, eager?: bool} $args 追加指定。
 */
function sk_image( int $attachment_id, string $size, array $args = array() ): void {

	if ( $attachment_id <= 0 ) {
		return;
	}

	$attr = array(
		'class' => $args['class'] ?? '',
	);

	if ( ! empty( $args['sizes'] ) ) {
		// sizes はブラウザが srcset から 1 枚選ぶための判断材料。
		// 既定値（100vw）のままだと、実際は 1/3 幅のカードにも大きい画像が選ばれる。
		$attr['sizes'] = $args['sizes'];
	}

	/*
	 * alt は原則としてテンプレートで指定しない。
	 * 指定しなければ wp_get_attachment_image() がメディアライブラリの
	 * 代替テキストを使うため、運営者が管理画面から編集できる。
	 * テンプレートに書くとコードを触らないと直せなくなる。
	 */
	if ( isset( $args['alt'] ) ) {
		$attr['alt'] = $args['alt'];
	}

	if ( ! empty( $args['eager'] ) ) {
		/*
		 * ヒーローだけは遅延させない。
		 * LCP（最大要素の描画）になる画像を lazy にすると、
		 * 表示が遅くなり体感速度が落ちる。
		 */
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
	} else {
		/*
		 * それ以外は明示的に lazy にする。
		 *
		 * WordPress 6.x は「最初の数枚は折り返しより上にあるはず」と推測して
		 * loading 属性を付けない場合がある（LCP を守るための既定の挙動）。
		 * 本テーマではヒーロー以外はすべて折り返しより下にあることが
		 * レイアウト上わかっているため、推測に任せず指定する。
		 */
		$attr['loading'] = 'lazy';
	}

	echo wp_get_attachment_image( $attachment_id, $size, false, $attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() がエスケープ済みの img を返す。
}

/**
 * 投稿のアイキャッチを出力する。
 *
 * @param int    $post_id 投稿 ID。
 * @param string $size    画像サイズ名。
 * @param array{sizes?: string, class?: string, eager?: bool} $args 追加指定。
 */
function sk_post_image( int $post_id, string $size, array $args = array() ): void {
	$attachment_id = (int) get_post_thumbnail_id( $post_id );

	if ( $attachment_id <= 0 ) {
		printf( '<span class="c-thumb__empty" aria-hidden="true"></span>' );
		return;
	}

	/*
	 * 代替テキストはメディアライブラリの値を優先する。
	 * そちらが空のときだけ投稿タイトルで補う。
	 * 何も入らないより投稿タイトルのほうがましだが、
	 * 画像そのものの説明にはならないため、あくまで保険とする。
	 */
	if ( ! isset( $args['alt'] ) ) {
		$media_alt = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

		if ( '' === trim( $media_alt ) ) {
			$args['alt'] = get_the_title( $post_id );
		}
	}

	sk_image( $attachment_id, $size, $args );
}

/**
 * 施工実績の物件概要を「項目名 => 値」で返す。
 *
 * 値が空の項目は表に出さない。物件によって分かる情報が違うため、
 * 空欄の行が並ぶより落とすほうが読みやすい。
 *
 * @param int $post_id 投稿 ID。
 * @return array<string, string>
 */
function sk_work_spec_rows( int $post_id ): array {

	$map = array(
		'location'         => __( '所在地', 'soranowa-koumuten' ),
		'completed_at'     => __( '竣工年月', 'soranowa-koumuten' ),
		'structure'        => __( '構造・規模', 'soranowa-koumuten' ),
		'site_area'        => __( '敷地面積', 'soranowa-koumuten' ),
		'floor_area'       => __( '延床面積', 'soranowa-koumuten' ),
		'floor_plan_label' => __( '間取り', 'soranowa-koumuten' ),
		'family'           => __( '家族構成', 'soranowa-koumuten' ),
		'duration'         => __( '工期', 'soranowa-koumuten' ),
	);

	$rows = array();

	foreach ( $map as $name => $label ) {
		$value = function_exists( 'get_field' ) ? (string) get_field( $name, $post_id ) : '';

		if ( '' !== trim( $value ) ) {
			$rows[ $label ] = $value;
		}
	}

	return $rows;
}

/**
 * ターム名を 1 件だけ返す（カードのラベル用）。
 *
 * @param int    $post_id  投稿 ID。
 * @param string $taxonomy タクソノミー名。
 * @return string 無ければ空文字。
 */
function sk_first_term_name( int $post_id, string $taxonomy ): string {
	$terms = get_the_terms( $post_id, $taxonomy );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}

	return (string) $terms[0]->name;
}

/**
 * パンくずの項目を返す。
 *
 * 画面表示と構造化データ（BreadcrumbList）の両方で使うため、
 * データと HTML を分けている。2 箇所で別々に組み立てるとずれる。
 *
 * @return array<int, array{label: string, url: string}>
 */
function sk_breadcrumb_items(): array {

	$items = array(
		array(
			'label' => __( 'ホーム', 'soranowa-koumuten' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( is_post_type_archive( 'work' ) ) {
		$items[] = array(
			'label' => __( '施工実績', 'soranowa-koumuten' ),
			'url'   => (string) get_post_type_archive_link( 'work' ),
		);

	} elseif ( is_tax( array( 'work_category', 'work_area' ) ) ) {
		$items[] = array(
			'label' => __( '施工実績', 'soranowa-koumuten' ),
			'url'   => (string) get_post_type_archive_link( 'work' ),
		);

		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$items[] = array(
				'label' => $term->name,
				'url'   => (string) get_term_link( $term ),
			);
		}

	} elseif ( is_singular( 'work' ) ) {
		$items[] = array(
			'label' => __( '施工実績', 'soranowa-koumuten' ),
			'url'   => (string) get_post_type_archive_link( 'work' ),
		);
		$items[] = array(
			'label' => get_the_title(),
			'url'   => (string) get_permalink(),
		);

	} elseif ( is_singular( 'post' ) || is_category() || is_home() ) {
		$posts_page_id = (int) get_option( 'page_for_posts' );

		if ( $posts_page_id > 0 ) {
			$items[] = array(
				'label' => get_the_title( $posts_page_id ),
				'url'   => (string) get_permalink( $posts_page_id ),
			);
		}

		if ( is_category() ) {
			$term = get_queried_object();

			if ( $term instanceof WP_Term ) {
				$items[] = array(
					'label' => $term->name,
					'url'   => (string) get_term_link( $term ),
				);
			}
		} elseif ( is_singular( 'post' ) ) {
			$items[] = array(
				'label' => get_the_title(),
				'url'   => (string) get_permalink(),
			);
		}
	} elseif ( is_page() ) {
		$items[] = array(
			'label' => get_the_title(),
			'url'   => (string) get_permalink(),
		);
	} elseif ( is_404() ) {
		$items[] = array(
			'label' => __( 'ページが見つかりません', 'soranowa-koumuten' ),
			'url'   => '',
		);
	}

	return $items;
}

/**
 * パンくずを出力する。
 */
function sk_breadcrumb(): void {

	if ( is_front_page() ) {
		return;
	}

	$items = sk_breadcrumb_items();

	if ( count( $items ) < 2 ) {
		return;
	}

	$last = count( $items ) - 1;
	?>
	<nav class="c-breadcrumb" aria-label="<?php esc_attr_e( 'パンくず', 'soranowa-koumuten' ); ?>">
		<div class="l-container">
			<ol class="c-breadcrumb__list">
				<?php foreach ( $items as $i => $item ) : ?>
					<li class="c-breadcrumb__item">
						<?php if ( $i === $last || '' === $item['url'] ) : ?>
							<span aria-current="page"><?php echo esc_html( $item['label'] ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</nav>
	<?php
}

/**
 * 施工実績の種別タブを出力する。
 *
 * 実サイト（はだしの家）の「All / モデルハウス / リフォーム / …」に相当する。
 * JavaScript で絞り込むのではなく、タクソノミーアーカイブへのリンクにする。
 * こうすると絞り込んだ状態に URL が付き、共有・ブックマーク・検索エンジンからの
 * 流入ができる。JS で隠すだけだと 1 ページ分の URL しか存在しない。
 */
function sk_work_category_tabs(): void {

	$terms = get_terms(
		array(
			'taxonomy'   => 'work_category',
			'hide_empty' => true,
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return;
	}

	$current = 0;

	if ( is_tax( 'work_category' ) ) {
		$queried = get_queried_object();
		$current = $queried instanceof WP_Term ? (int) $queried->term_id : 0;
	}
	$all_is_current = ( 0 === $current );
	?>
	<nav class="c-tabs" aria-label="<?php esc_attr_e( '種別で絞り込む', 'soranowa-koumuten' ); ?>">
		<ul class="c-tabs__list">
			<li>
				<a class="<?php echo esc_attr( 'c-tabs__item' . ( $all_is_current ? ' is-current' : '' ) ); ?>"
					href="<?php echo esc_url( (string) get_post_type_archive_link( 'work' ) ); ?>"
					<?php if ( $all_is_current ) : ?>aria-current="page"<?php endif; ?>>
					<?php esc_html_e( 'すべて', 'soranowa-koumuten' ); ?>
				</a>
			</li>
			<?php
			foreach ( $terms as $term ) :
				$is_current = ( $current === (int) $term->term_id );
				?>
				<li>
					<a class="<?php echo esc_attr( 'c-tabs__item' . ( $is_current ? ' is-current' : '' ) ); ?>"
						href="<?php echo esc_url( (string) get_term_link( $term ) ); ?>"
						<?php if ( $is_current ) : ?>aria-current="page"<?php endif; ?>>
						<?php echo esc_html( $term->name ); ?>
						<span class="c-tabs__count"><?php echo esc_html( (string) $term->count ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/**
 * ページ見出し（下層ページ共通のタイトル帯）を出力する。
 *
 * @param string $title    見出し。
 * @param string $subtitle 英字などの副題。
 */
function sk_page_header( string $title, string $subtitle = '' ): void {
	?>
	<header class="p-page-header">
		<div class="l-container">
			<?php if ( '' !== $subtitle ) : ?>
				<p class="p-page-header__sub"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
			<h1 class="p-page-header__title"><?php echo esc_html( $title ); ?></h1>
		</div>
	</header>
	<?php
}

/**
 * 「架空のサンプル」であることの表示。
 *
 * ストックフォトを施工実績として掲載するため、実在の施工写真だと
 * 誤認されないようにする。ACF やカスタマイザーで編集可能にすると
 * 運営者が消せてしまい明示の意味が無くなるため、意図的にハードコードしている。
 *
 * @param string $context 表示位置。
 */
function sk_sample_notice( string $context = 'default' ): void {
	$text = 'works' === $context
		? __( '掲載している写真はフリー素材です。架空の企業を想定した制作サンプルであり、実在の施工事例ではありません。', 'soranowa-koumuten' )
		: __( '本サイトは架空の企業を想定した制作サンプルです。', 'soranowa-koumuten' );
	?>
	<p class="c-sample-notice"><?php echo esc_html( $text ); ?></p>
	<?php
}

/**
 * メニュー未設定時の代替表示。
 *
 * wp_nav_menu() の fallback_cb に渡す。
 * 納品直後にメニューが未設定でもナビが消えないようにするための保険。
 */
function sk_nav_fallback(): void {
	$items = array(
		'/services/' => __( '事業内容', 'soranowa-koumuten' ),
		'/works/'    => __( '施工実績', 'soranowa-koumuten' ),
		'/company/'  => __( '会社概要', 'soranowa-koumuten' ),
		'/recruit/'  => __( '採用情報', 'soranowa-koumuten' ),
		'/news/'     => __( 'お知らせ', 'soranowa-koumuten' ),
	);

	echo '<ul class="l-nav__list">';

	foreach ( $items as $path => $label ) {
		printf(
			'<li class="menu-item"><a href="%s">%s</a></li>',
			esc_url( home_url( $path ) ),
			esc_html( $label )
		);
	}

	echo '</ul>';
}

/**
 * プラグインが無い環境でも落ちないように会社情報を取る（テーマ側のラッパ）。
 *
 * テーマ単体で有効化された場合に致命的エラーにしないための保険。
 *
 * @param string $key     項目キー。
 * @param string $default 既定値。
 * @return string
 */
function sk_company( string $key, string $default ): string {
	if ( ! function_exists( 'kc_company' ) ) {
		return $default;
	}

	$value = kc_company( $key );

	return '' !== $value ? $value : $default;
}
