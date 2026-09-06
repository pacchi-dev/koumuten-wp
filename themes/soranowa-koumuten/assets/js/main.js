/**
 * フロント側の最小限のスクリプト。
 *
 * 依存ライブラリを入れない。jQuery は WordPress に同梱されているが、
 * この規模では素の DOM API で十分で、読み込むだけ転送量が増える。
 * 「写真が主役なので帯域は写真に使う」という方針に沿う。
 */
(function () {
	'use strict';

	var toggle = document.querySelector('[data-nav-toggle]');
	var nav = document.getElementById('global-nav');

	if (!toggle || !nav) {
		return;
	}

	function setOpen(open) {
		// aria-expanded は「開閉状態」を支援技術へ伝える。
		// 見た目のクラスだけ切り替えると、閉じたメニューが読み上げられる。
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		nav.classList.toggle('is-open', open);
	}

	toggle.addEventListener('click', function () {
		setOpen(toggle.getAttribute('aria-expanded') !== 'true');
	});

	// メニュー内のリンクを踏んだら閉じる（同一ページ内リンク対策）。
	nav.addEventListener('click', function (event) {
		if (event.target.closest('a')) {
			setOpen(false);
		}
	});

	// Esc で閉じ、フォーカスをボタンへ戻す。
	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
			setOpen(false);
			toggle.focus();
		}
	});

	// 画面幅が広がってドロワーが不要になったら状態を戻す。
	var mq = window.matchMedia('(min-width: 900px)');

	function handleChange(event) {
		if (event.matches) {
			setOpen(false);
		}
	}

	if (typeof mq.addEventListener === 'function') {
		mq.addEventListener('change', handleChange);
	}
})();
