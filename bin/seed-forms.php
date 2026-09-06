<?php
/**
 * Contact Form 7 のフォーム作成。
 *
 *   実行: docker exec koumuten_wp_cli wp --path=/var/www/html eval-file /scripts/seed-forms.php
 *
 * CF7 のフォームは wpcf7_contact_form という投稿タイプで保存されている。
 * 直接 wp_insert_post するとメタデータの構造を自前で組むことになるため、
 * CF7 が公開している WPCF7_ContactForm::get_template() → set_properties() → save()
 * を使う。将来 CF7 側の保存形式が変わっても追従できる。
 *
 * 作成したフォームの ID はオプションに保存し、seed-fields.php が
 * ショートコードとして各ページの ACF に流し込む。
 *
 * @package KoumutenSeed
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
	WP_CLI::warning( 'Contact Form 7 が有効ではありません。フォームの作成をスキップします。' );
	return;
}

WP_CLI::log( '==> お問い合わせフォームを作成します' );

$sk_admin_email = (string) get_option( 'admin_email' );

/**
 * フォームを作成または更新し、ショートコードを返す。
 *
 * @param string $option_key ID を保存するオプションキー。
 * @param string $title      フォーム名。
 * @param string $form       フォーム本文（CF7 のタグ記法）。
 * @param array<string, string> $mail 送信メールの設定。
 * @return string ショートコード。
 */
function sk_seed_form( string $option_key, string $title, string $form, array $mail ): string {

	$existing_id = (int) get_option( $option_key, 0 );
	$contact_form = $existing_id > 0 ? WPCF7_ContactForm::get_instance( $existing_id ) : null;

	if ( ! $contact_form ) {
		$contact_form = WPCF7_ContactForm::get_template( array( 'title' => $title ) );
	}

	$contact_form->set_title( $title );
	$contact_form->set_properties(
		array(
			'form'     => $form,
			'mail'     => $mail,
			'messages' => sk_seed_form_messages(),
		)
	);

	$id = $contact_form->save();

	if ( ! $id ) {
		WP_CLI::warning( "フォームの保存に失敗しました: {$title}" );
		return '';
	}

	update_option( $option_key, (int) $id );

	// ショートコードは CF7 自身に組み立てさせる。
	// CF7 のバージョンで書式（数値 ID / ハッシュ）が変わるため、手で書かない。
	$saved = WPCF7_ContactForm::get_instance( (int) $id );

	$shortcode = $saved ? $saved->shortcode() : '';

	WP_CLI::log( "    {$title} → {$shortcode}" );

	return (string) $shortcode;
}

/**
 * フォームの応答メッセージ（日本語）。
 *
 * CF7 の既定メッセージは翻訳ファイルに依存する。
 * WP-CLI から作成すると英語のまま保存されることがあるため、
 * 日本語を明示して保存する。運営者は管理画面から変更できる。
 *
 * @return array<string, string>
 */
function sk_seed_form_messages(): array {
	return array(
		'mail_sent_ok'                 => 'ありがとうございます。送信が完了しました。',
		'mail_sent_ng'                 => '送信に失敗しました。しばらく時間をおいて、もう一度お試しください。',
		'validation_error'             => '入力内容に誤りがあります。確認してもう一度送信してください。',
		'accept_terms'                 => '送信するには、同意が必要です。',
		'invalid_required'             => '必須項目です。',
		'invalid_too_long'             => '文字数が多すぎます。',
		'invalid_too_short'            => '文字数が少なすぎます。',
		'invalid_date'                 => '日付の形式が正しくありません。',
		'date_too_early'               => '指定できる日付より前の日付です。',
		'date_too_late'                => '指定できる日付より後の日付です。',
		'upload_failed'                => 'ファイルのアップロードに失敗しました。',
		'upload_file_type_invalid'     => 'このファイル形式は受け付けていません。',
		'upload_file_too_large'        => 'ファイルサイズが大きすぎます。',
		'upload_failed_php_error'      => 'ファイルのアップロード中にエラーが発生しました。',
		'invalid_number'               => '数値の形式が正しくありません。',
		'number_too_small'             => '指定できる数値より小さい値です。',
		'number_too_large'             => '指定できる数値より大きい値です。',
		'quiz_answer_not_correct'      => '答えが正しくありません。',
		'invalid_email'                => 'メールアドレスの形式が正しくありません。',
		'invalid_url'                  => 'URL の形式が正しくありません。',
		'invalid_tel'                  => '電話番号の形式が正しくありません。',
	);
}

// ---------------------------------------------------------------------------
// お問い合わせフォーム
// ---------------------------------------------------------------------------
$sk_contact_form = <<<'FORM'
<label>お名前<span class="p-form__required">必須</span>
[text* your-name autocomplete:name]</label>

<label>ふりがな
[text your-kana]</label>

<label>メールアドレス<span class="p-form__required">必須</span>
[email* your-email autocomplete:email]</label>

<label>電話番号
[tel your-tel autocomplete:tel]</label>

<label>ご相談の種類
[select your-subject "新築・注文住宅" "リフォーム・リノベーション" "外構・エクステリア" "その他"]</label>

<label>ご相談内容<span class="p-form__required">必須</span>
[textarea* your-message]</label>

<p class="p-form__consent-note"><a href="/privacy/">プライバシーポリシー</a>をご確認のうえ、同意して送信してください。</p>

[acceptance your-consent] プライバシーポリシーに同意します [/acceptance]

[submit "送信する"]
FORM;

$sk_contact_body = <<<'BODY'
サイトのお問い合わせフォームから送信がありました。

お名前　　: [your-name]（[your-kana]）
メール　　: [your-email]
電話番号　: [your-tel]
ご相談種類: [your-subject]

ご相談内容:
[your-message]

--
[_site_title]
送信元: [_url]
BODY;

$sk_contact_shortcode = sk_seed_form(
	'sk_form_contact',
	'お問い合わせ',
	$sk_contact_form,
	array(
		'active'             => true,
		'subject'            => '[_site_title] お問い合わせ（[your-subject]）',
		'sender'             => '[_site_title] <wordpress@localhost.test>',
		'recipient'          => $sk_admin_email,
		'body'               => $sk_contact_body,
		'additional_headers' => 'Reply-To: [your-email]',
		'attachments'        => '',
		'use_html'           => false,
		'exclude_blank'      => false,
	)
);

// ---------------------------------------------------------------------------
// 採用応募フォーム
// ---------------------------------------------------------------------------
$sk_recruit_form = <<<'FORM'
<label>お名前<span class="p-form__required">必須</span>
[text* your-name autocomplete:name]</label>

<label>ふりがな
[text your-kana]</label>

<label>メールアドレス<span class="p-form__required">必須</span>
[email* your-email autocomplete:email]</label>

<label>電話番号<span class="p-form__required">必須</span>
[tel* your-tel autocomplete:tel]</label>

<label>希望職種<span class="p-form__required">必須</span>
[select* your-position "設計" "施工管理" "大工職" "未定・相談したい"]</label>

<label>経験
[select your-experience "経験あり（3年以上）" "経験あり（3年未満）" "未経験"]</label>

<label>志望動機・自己PR<span class="p-form__required">必須</span>
[textarea* your-message]</label>

<p class="p-form__consent-note"><a href="/privacy/">プライバシーポリシー</a>をご確認のうえ、同意して送信してください。</p>

[acceptance your-consent] プライバシーポリシーに同意します [/acceptance]

[submit "応募する"]
FORM;

$sk_recruit_body = <<<'BODY'
採用応募フォームから送信がありました。

お名前　: [your-name]（[your-kana]）
メール　: [your-email]
電話番号: [your-tel]
希望職種: [your-position]
経験　　: [your-experience]

志望動機・自己PR:
[your-message]

--
[_site_title]
送信元: [_url]
BODY;

$sk_recruit_shortcode = sk_seed_form(
	'sk_form_recruit',
	'採用応募',
	$sk_recruit_form,
	array(
		'active'             => true,
		'subject'            => '[_site_title] 採用応募（[your-position]）',
		'sender'             => '[_site_title] <wordpress@localhost.test>',
		'recipient'          => $sk_admin_email,
		'body'               => $sk_recruit_body,
		'additional_headers' => 'Reply-To: [your-email]',
		'attachments'        => '',
		'use_html'           => false,
		'exclude_blank'      => false,
	)
);

update_option( 'sk_form_contact_shortcode', $sk_contact_shortcode );
update_option( 'sk_form_recruit_shortcode', $sk_recruit_shortcode );

// ---------------------------------------------------------------------------
// CF7 の既定フォームを削除する
//
//   CF7 は有効化時に「Contact form 1」という空のフォームを 1 つ作る。
//   使わないまま残すと、発注者が管理画面を開いたときに
//   どれが本番のフォームか分からなくなる。
//
//   既定フォームだけを名前で特定して消す。ID の一致だけで判定すると、
//   発注者があとから自分で追加したフォームまで消してしまう。
// ---------------------------------------------------------------------------
$sk_keep = array(
	(int) get_option( 'sk_form_contact', 0 ),
	(int) get_option( 'sk_form_recruit', 0 ),
);

// 英語ロケールと日本語ロケールの両方の既定名に対応する。
$sk_default_titles = array( 'Contact form 1', 'コンタクトフォーム 1' );

$sk_forms = get_posts(
	array(
		'post_type'      => 'wpcf7_contact_form',
		'posts_per_page' => -1,
		'post_status'    => 'any',
	)
);

foreach ( $sk_forms as $sk_form_post ) {
	if ( in_array( (int) $sk_form_post->ID, $sk_keep, true ) ) {
		continue;
	}

	if ( ! in_array( $sk_form_post->post_title, $sk_default_titles, true ) ) {
		continue;
	}

	wp_delete_post( (int) $sk_form_post->ID, true );
	WP_CLI::log( "    既定フォームを削除しました: {$sk_form_post->post_title}" );
}

WP_CLI::log( '    完了' );
