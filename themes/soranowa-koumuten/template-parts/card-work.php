<?php
/**
 * 施工実績カード。
 *
 * 実サイト調査で共通していた「写真 + 種別 + エリア + タイトル」の構成にする。
 * ループ内から get_template_part() で呼ぶ。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

$sk_id       = get_the_ID();
$sk_category = sk_first_term_name( $sk_id, 'work_category' );
$sk_area     = sk_first_term_name( $sk_id, 'work_area' );

/*
 * 日付は投稿日ではなく竣工年月を出す。
 * 工務店サイトで読み手が知りたいのは「いつ建ったか」であり、
 * 記事を登録した日ではない。
 */
$sk_completed = function_exists( 'get_field' ) ? (string) get_field( 'completed_at', $sk_id ) : '';
?>
<article class="c-card-wrap">
	<a class="c-card" href="<?php the_permalink(); ?>">
		<div class="c-card__media">
			<?php
			/*
			 * sizes を明示する。既定の 100vw のままだと、
			 * 実際には 1/3 幅で表示されるカードにも全幅ぶんの画像が選ばれ、
			 * srcset を用意した意味が無くなる。
			 */
			sk_post_image(
				$sk_id,
				'sk-card',
				array(
					'sizes' => '(min-width: 1180px) 366px, (min-width: 900px) 33vw, (min-width: 600px) 50vw, 100vw',
				)
			);
			?>
		</div>

		<div class="c-card__body">
			<p class="c-card__meta">
				<?php if ( '' !== $sk_category ) : ?>
					<span class="c-card__cat"><?php echo esc_html( $sk_category ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $sk_area ) : ?>
					<span class="c-card__area"><?php echo esc_html( $sk_area ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $sk_completed ) : ?>
					<span class="c-card__date"><?php echo esc_html( $sk_completed ); ?><?php esc_html_e( '竣工', 'soranowa-koumuten' ); ?></span>
				<?php endif; ?>
			</p>

			<h3 class="c-card__title"><?php the_title(); ?></h3>
		</div>
	</a>
</article>
