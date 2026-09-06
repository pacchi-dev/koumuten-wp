<?php
/**
 * 汎用の固定ページ（プライバシーポリシー等）。
 *
 * page-{slug}.php が無いページはすべてこのテンプレートで表示される。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	sk_page_header( get_the_title() );
	sk_breadcrumb();
	?>

	<div class="l-section">
		<div class="l-container">
			<div class="c-prose"><?php the_content(); ?></div>
		</div>
	</div>

	<?php
endwhile;

get_footer();
