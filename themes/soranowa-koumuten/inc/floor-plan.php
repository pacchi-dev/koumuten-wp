<?php
/**
 * 間取り図のインライン SVG 生成。
 *
 * なぜラスタ画像ではなく SVG か:
 *   - リクエストを増やさない（画像が主役のサイトなので転送量が効く）
 *   - currentColor でテーマの配色に追従する
 *   - 拡大しても劣化しない
 *
 * なぜテンプレートに直書きせずデータから描くか:
 *   物件ごとに SVG を書くと、施工実績を 1 件追加するたびに
 *   テーマファイルを編集することになる。
 *   「運営者が自分で更新できる範囲を最大化する」という方針に反する。
 *
 * 入力は ACF の textarea。1 行 1 部屋で「室名,X,Y,幅,高さ」。
 * 入力ミスで画面が壊れないよう、不正な行は黙って捨てる。
 *
 * @package SoranowaKoumuten
 */

defined( 'ABSPATH' ) || exit;

/**
 * 1 マスの大きさ（SVG 座標系）。
 */
const SK_PLAN_UNIT = 36;

/**
 * 図の余白（SVG 座標系）。上は方位記号のぶんを広くとる。
 */
const SK_PLAN_PAD    = 18;
const SK_PLAN_PAD_TOP = 44;

/**
 * 1 図あたりの最大部屋数。異常な入力で巨大な DOM を作らないための上限。
 */
const SK_PLAN_MAX_ROOMS = 30;

/**
 * 座標の上限（マス）。
 */
const SK_PLAN_MAX_GRID = 40;

/**
 * 間取り図データをパースする。
 *
 * @param string $spec ACF の textarea の生の値。
 * @return array<int, array{name: string, x: float, y: float, w: float, h: float}>
 */
function sk_parse_floor_plan( string $spec ): array {

	/*
	 * 改行の分割に \R は使わない。
	 * \R は u 修飾子が無いとバイト 0x85（NEL）1 文字にもマッチする。
	 * UTF-8 の日本語には 0x85 を含む文字が多く（者 = E8 80 85、
	 * 入 = E5 85 A5、内 = E5 86 85）、文字の途中で行が割れて文字化けする。
	 * \r\n / \r / \n を明示すれば、UTF-8 は自己同期符号なので安全に分割できる。
	 */
	$rooms = array();

	foreach ( preg_split( '/\r\n|\r|\n/', $spec ) ?: array() as $line ) {
		$line = trim( $line );

		if ( '' === $line || str_starts_with( $line, '#' ) ) {
			continue;
		}

		$parts = array_map( 'trim', explode( ',', $line ) );

		if ( count( $parts ) < 5 ) {
			continue;
		}

		// 室名にカンマは使えない仕様。先頭を室名、続く 4 つを座標として読む。
		[ $name, $x, $y, $w, $h ] = $parts;

		if ( '' === $name ) {
			continue;
		}

		// 数値以外は捨てる。is_numeric() で全角数字や単位付きの入力を弾く。
		if ( ! is_numeric( $x ) || ! is_numeric( $y ) || ! is_numeric( $w ) || ! is_numeric( $h ) ) {
			continue;
		}

		$x = (float) $x;
		$y = (float) $y;
		$w = (float) $w;
		$h = (float) $h;

		if ( $w <= 0 || $h <= 0 || $x < 0 || $y < 0 ) {
			continue;
		}

		if ( $x + $w > SK_PLAN_MAX_GRID || $y + $h > SK_PLAN_MAX_GRID ) {
			continue;
		}

		$rooms[] = array(
			'name' => $name,
			'x'    => $x,
			'y'    => $y,
			'w'    => $w,
			'h'    => $h,
		);

		if ( count( $rooms ) >= SK_PLAN_MAX_ROOMS ) {
			break;
		}
	}

	return $rooms;
}

/**
 * 室名の文字サイズを決める。
 *
 * 小さい部屋にそのままの文字を入れるとはみ出すため、
 * 部屋の幅と文字数から縮める。
 *
 * @param string $name 室名。
 * @param float  $w    部屋の幅（マス）。
 * @param float  $h    部屋の高さ（マス）。
 * @return float
 */
function sk_plan_font_size( string $name, float $w, float $h ): float {
	$chars = max( 1, mb_strlen( $name ) );

	// 幅に収まる文字サイズ（全角は約 1 文字ぶんの幅を使う想定で 1.15 の係数）。
	$by_width  = ( $w * SK_PLAN_UNIT * 0.82 ) / ( $chars * 1.15 );
	$by_height = $h * SK_PLAN_UNIT * 0.4;

	return max( 8.0, min( 14.0, $by_width, $by_height ) );
}

/**
 * 間取り図を出力する。
 *
 * @param string $spec  ACF の textarea の生の値。
 * @param string $note  図の下に出す注記。
 * @param string $label 図のラベル（例: 3LDK+WIC）。アクセシビリティ用の説明に使う。
 */
function sk_render_floor_plan( string $spec, string $note = '', string $label = '' ): void {

	$rooms = sk_parse_floor_plan( $spec );

	if ( empty( $rooms ) ) {
		return;
	}

	// 図全体の大きさをデータから決める。固定値にすると物件ごとに余白がずれる。
	$cols = 0.0;
	$rows = 0.0;

	foreach ( $rooms as $room ) {
		$cols = max( $cols, $room['x'] + $room['w'] );
		$rows = max( $rows, $room['y'] + $room['h'] );
	}

	$width  = $cols * SK_PLAN_UNIT + SK_PLAN_PAD * 2;
	$height = $rows * SK_PLAN_UNIT + SK_PLAN_PAD + SK_PLAN_PAD_TOP;

	$title_id = 'sk-plan-title-' . wp_unique_id();
	$desc     = '' !== $label
		/* translators: %s: 間取り（例 3LDK） */
		? sprintf( __( '間取り図（%s）', 'soranowa-koumuten' ), $label )
		: __( '間取り図', 'soranowa-koumuten' );
	?>
	<figure class="c-floor-plan">
		<svg class="c-floor-plan__svg"
			viewBox="0 0 <?php echo esc_attr( (string) round( $width, 2 ) ); ?> <?php echo esc_attr( (string) round( $height, 2 ) ); ?>"
			role="img"
			aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
			xmlns="http://www.w3.org/2000/svg">
			<title id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( $desc ); ?></title>

			<?php
			// 方位記号（北）。右上に固定で置く。
			$nx = $width - SK_PLAN_PAD - 12;
			$ny = 22;
			?>
			<g class="c-floor-plan__compass" transform="translate(<?php echo esc_attr( (string) round( $nx, 2 ) ); ?>,<?php echo esc_attr( (string) $ny ); ?>)">
				<circle r="13" fill="none" stroke="currentColor" stroke-width="1" opacity="0.45" />
				<path d="M0,-9 L4.5,6 L0,3 L-4.5,6 Z" fill="currentColor" />
				<text x="0" y="-15" text-anchor="middle" font-size="9" fill="currentColor" opacity="0.7">N</text>
			</g>

			<g transform="translate(<?php echo esc_attr( (string) SK_PLAN_PAD ); ?>,<?php echo esc_attr( (string) SK_PLAN_PAD_TOP ); ?>)">
				<?php foreach ( $rooms as $room ) : ?>
					<?php
					$rx = $room['x'] * SK_PLAN_UNIT;
					$ry = $room['y'] * SK_PLAN_UNIT;
					$rw = $room['w'] * SK_PLAN_UNIT;
					$rh = $room['h'] * SK_PLAN_UNIT;
					$fs = sk_plan_font_size( $room['name'], $room['w'], $room['h'] );
					?>
					<rect
						x="<?php echo esc_attr( (string) round( $rx, 2 ) ); ?>"
						y="<?php echo esc_attr( (string) round( $ry, 2 ) ); ?>"
						width="<?php echo esc_attr( (string) round( $rw, 2 ) ); ?>"
						height="<?php echo esc_attr( (string) round( $rh, 2 ) ); ?>"
						fill="currentColor"
						fill-opacity="0.05"
						stroke="currentColor"
						stroke-width="1.4"
						stroke-opacity="0.75" />
					<text
						x="<?php echo esc_attr( (string) round( $rx + $rw / 2, 2 ) ); ?>"
						y="<?php echo esc_attr( (string) round( $ry + $rh / 2, 2 ) ); ?>"
						text-anchor="middle"
						dominant-baseline="central"
						font-size="<?php echo esc_attr( (string) round( $fs, 2 ) ); ?>"
						fill="currentColor"><?php echo esc_html( $room['name'] ); ?></text>
				<?php endforeach; ?>
			</g>
		</svg>

		<?php if ( '' !== $note ) : ?>
			<figcaption class="c-floor-plan__note"><?php echo esc_html( $note ); ?></figcaption>
		<?php endif; ?>
	</figure>
	<?php
}
