/**
 * 完了条件の自動検証。
 *
 *   使い方: npm run verify
 *
 * 検証する内容:
 *   1. 全ページが 200 で表示される
 *   2. 390 / 768 / 1440px で横スクロールが発生しない
 *   3. 写真に重ねた白文字のコントラスト比が 4.5:1 以上（合成後の画素を実測）
 *   4. 画像に srcset と loading が付いている（ヒーローだけ eager）
 *   5. リクエスト数・転送量・読み込み時間
 *   6. Contact Form 7 の送信が Mailpit に届く
 *   7. タップ領域が 24×24px 以上（WCAG 2.5.8 AA）
 *   8. すべての img に代替テキストがある
 *
 * 実ブラウザ（Chrome）で描画した結果を測る。
 * CSS を読んだだけでは「実際にどう見えるか」は分からないため。
 */

import puppeteer from 'puppeteer-core';
import { PNG } from 'pngjs';
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const BASE = process.env.WP_URL || 'http://localhost:8090';
const MAILPIT = process.env.MAILPIT_URL || 'http://localhost:8035';
const CHROME =
	process.env.CHROME_PATH ||
	'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const OUT_DIR = path.join(ROOT, 'docs', 'verify');

const PAGES = [
	['トップ', '/'],
	['事業内容', '/services/'],
	['施工実績 一覧', '/works/'],
	['施工実績 絞り込み', '/work-category/renovation/'],
	['施工実績 詳細', '/works/himeji-yoyu-house/'],
	['会社概要', '/company/'],
	['採用情報', '/recruit/'],
	['お知らせ 一覧', '/news/'],
	['お知らせ 詳細', '/news/summer-holiday-2025/'],
	['お問い合わせ', '/contact/'],
	['プライバシーポリシー', '/privacy/'],
	['404', '/no-such-page-here/'],
];

const VIEWPORTS = [
	{ label: '390', width: 390, height: 844 },
	{ label: '768', width: 768, height: 1024 },
	{ label: '1440', width: 1440, height: 900 },
];

/** WCAG の相対輝度。 */
function luminance(r, g, b) {
	const f = (v) => {
		const c = v / 255;
		return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
	};
	return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b);
}

/** 白文字に対するコントラスト比。 */
function contrastWithWhite(l) {
	return 1.05 / (l + 0.05);
}

/** Mailpit の API を叩く（Node 16 に fetch が無いため http モジュールを使う）。 */
function getJson(url) {
	return new Promise((resolve, reject) => {
		http
			.get(url, (res) => {
				let body = '';
				res.on('data', (c) => (body += c));
				res.on('end', () => {
					try {
						resolve(JSON.parse(body));
					} catch (e) {
						reject(e);
					}
				});
			})
			.on('error', reject);
	});
}

function del(url) {
	return new Promise((resolve, reject) => {
		const u = new URL(url);
		const req = http.request(
			{ hostname: u.hostname, port: u.port, path: u.pathname, method: 'DELETE' },
			(res) => {
				res.resume();
				res.on('end', resolve);
			}
		);
		req.on('error', reject);
		req.end();
	});
}

/**
 * 写真に重ねた文字のコントラストを実測する。
 *
 * 文字そのものを一時的に非表示にしてから撮影する。
 * 表示したまま撮ると、白い文字の画素を「背景」として拾ってしまい
 * コントラスト比が 1:1 になって正しく測れない。
 *
 * 白文字にとっての最悪値は「最も明るい背景画素」なので、
 * 文字の外接矩形を格子状にサンプリングして最大輝度を採る。
 */
async function measureContrast(page, containerSelector, textSelectors, name) {
	const box = await page.evaluate((sels) => {
		const rects = sels
			.map((s) => document.querySelector(s))
			.filter(Boolean)
			.map((el) => el.getBoundingClientRect())
			.filter((r) => r.width > 0 && r.height > 0);

		if (!rects.length) return null;

		/*
		 * getBoundingClientRect() はビューポート基準、
		 * page.screenshot() の clip はドキュメント基準。
		 * スクロール量を足さないと、スクロール後に別の場所を撮ってしまう。
		 */
		const left = Math.min(...rects.map((r) => r.left)) + window.scrollX;
		const top = Math.min(...rects.map((r) => r.top)) + window.scrollY;
		const right = Math.max(...rects.map((r) => r.right)) + window.scrollX;
		const bottom = Math.max(...rects.map((r) => r.bottom)) + window.scrollY;

		return { x: left, y: top, width: right - left, height: bottom - top };
	}, textSelectors);

	if (!box) return null;

	// 文字を隠して背景だけにする。
	await page.evaluate((sel) => {
		const root = document.querySelector(sel);
		if (root) root.style.visibility = 'hidden';
	}, containerSelector);

	const buf = await page.screenshot({
		clip: {
			x: Math.max(0, Math.floor(box.x)),
			y: Math.max(0, Math.floor(box.y)),
			width: Math.max(1, Math.floor(box.width)),
			height: Math.max(1, Math.floor(box.height)),
		},
	});

	await page.evaluate((sel) => {
		const root = document.querySelector(sel);
		if (root) root.style.visibility = '';
	}, containerSelector);

	const png = PNG.sync.read(Buffer.from(buf));

	// 格子状に 5 x 8 = 40 点をサンプリングする（要件は 20 点以上）。
	const rows = 5;
	const cols = 8;
	let worst = { l: -1, rgb: null, at: null };
	let samples = 0;

	for (let i = 0; i < rows; i++) {
		for (let j = 0; j < cols; j++) {
			const y = Math.min(png.height - 1, Math.round(((i + 0.5) / rows) * png.height));
			const x = Math.min(png.width - 1, Math.round(((j + 0.5) / cols) * png.width));
			const idx = (png.width * y + x) << 2;
			const r = png.data[idx];
			const g = png.data[idx + 1];
			const b = png.data[idx + 2];
			const l = luminance(r, g, b);
			samples++;

			if (l > worst.l) {
				worst = { l, rgb: [r, g, b], at: [x, y] };
			}
		}
	}

	return {
		name,
		samples,
		worstPixel: worst.rgb,
		worstLuminance: Number(worst.l.toFixed(4)),
		contrast: Number(contrastWithWhite(worst.l).toFixed(2)),
		pass: contrastWithWhite(worst.l) >= 4.5,
	};
}

async function main() {
	fs.mkdirSync(OUT_DIR, { recursive: true });

	const browser = await puppeteer.launch({
		executablePath: CHROME,
		headless: 'new',
		args: ['--no-sandbox', '--hide-scrollbars', '--force-device-scale-factor=1'],
	});

	const report = {
		base: BASE,
		checkedAt: new Date().toISOString(),
		pages: [],
		overflow: [],
		contrast: [],
		images: null,
		performance: null,
		form: null,
	};

	const page = await browser.newPage();

	// ---------------------------------------------------------------- 1 & 2
	console.log('== ページ表示と横スクロール ==');

	for (const [label, url] of PAGES) {
		const row = { label, url, status: null, overflow: {} };

		for (const vp of VIEWPORTS) {
			await page.setViewport({ width: vp.width, height: vp.height });
			const res = await page.goto(BASE + url, { waitUntil: 'networkidle2' });
			row.status = res ? res.status() : 0;

			const m = await page.evaluate(() => ({
				scrollWidth: document.documentElement.scrollWidth,
				clientWidth: document.documentElement.clientWidth,
			}));

			row.overflow[vp.label] = m.scrollWidth - m.clientWidth;

			/*
			 * タップ領域と代替テキストはモバイル幅でだけ調べる。
			 * 指で操作されるのはこの幅であり、レイアウトも最も詰まるため。
			 */
			if ('390' === vp.label) {
				const a11y = await page.evaluate(() => {
					const small = [];

					for (const el of document.querySelectorAll('a, button, input, select, textarea')) {
						const box = el.getBoundingClientRect();

						// 非表示の要素は対象外。
						if ((0 === box.width && 0 === box.height) || 'hidden' === el.type) {
							continue;
						}

						/*
						 * チェックボックスのように、ラベル全体が操作対象に
						 * なっている場合はラベルの大きさで判定する。
						 */
						const target = el.closest('label') || el;
						const outer = target.getBoundingClientRect();
						const width = Math.max(box.width, outer.width);
						const height = Math.max(box.height, outer.height);

						if (width < 24 || height < 24) {
							small.push({
								text: (el.textContent || el.name || '').trim().slice(0, 20),
								size: `${Math.round(box.width)}x${Math.round(box.height)}`,
							});
						}
					}

					const images = Array.from(document.images);

					return {
						smallTargets: small,
						imagesTotal: images.length,
						imagesWithoutAlt: images
							.filter((i) => !i.getAttribute('alt'))
							.map((i) => i.currentSrc.split('/').pop()),
					};
				});

				row.smallTargets = a11y.smallTargets;
				row.imagesTotal = a11y.imagesTotal;
				row.imagesWithoutAlt = a11y.imagesWithoutAlt;
			}
		}

		const bad = Object.entries(row.overflow).filter(([, v]) => v > 0);
		const expected = url === '/no-such-page-here/' ? 404 : 200;
		const ok =
			row.status === expected &&
			bad.length === 0 &&
			0 === (row.smallTargets || []).length &&
			0 === (row.imagesWithoutAlt || []).length;

		console.log(
			`  ${ok ? 'OK ' : 'NG '} ${label.padEnd(20, '　')} ${row.status}  ` +
				VIEWPORTS.map((v) => `${v.label}px:${row.overflow[v.label]}`).join('  ') +
				`  タップ領域NG:${(row.smallTargets || []).length}` +
				`  alt空:${(row.imagesWithoutAlt || []).length}/${row.imagesTotal || 0}`
		);

		for (const t of row.smallTargets || []) {
			console.log(`        24px未満: ${t.size} "${t.text}"`);
		}

		for (const src of row.imagesWithoutAlt || []) {
			console.log(`        alt が空: ${src}`);
		}

		report.pages.push(row);
		if (bad.length) report.overflow.push(row);
	}

	const totalSmall = report.pages.reduce((n, p) => n + (p.smallTargets || []).length, 0);
	const totalNoAlt = report.pages.reduce((n, p) => n + (p.imagesWithoutAlt || []).length, 0);
	const totalImages = report.pages.reduce((n, p) => n + (p.imagesTotal || 0), 0);

	console.log(
		`\n  合計: 24px 未満のタップ領域 ${totalSmall} 件 / ` +
			`alt が空の画像 ${totalNoAlt} 件（全 ${totalImages} 枚）`
	);

	// ---------------------------------------------------------------- 3
	console.log('\n== 写真上の白文字のコントラスト（合成後の画素を実測）==');

	for (const vp of [VIEWPORTS[0], VIEWPORTS[2]]) {
		await page.setViewport({ width: vp.width, height: vp.height });
		await page.goto(BASE + '/', { waitUntil: 'networkidle2' });

		const hero = await measureContrast(
			page,
			'.p-hero__body',
			['.p-hero__eyebrow', '.p-hero__title', '.p-hero__text'],
			`ヒーロー（${vp.label}px）`
		);

		if (hero) {
			report.contrast.push(hero);
			console.log(
				`  ${hero.pass ? 'OK ' : 'NG '} ${hero.name}  ${hero.samples}点  ` +
					`最明画素 rgb(${hero.worstPixel.join(',')})  輝度 ${hero.worstLuminance}  ` +
					`コントラスト ${hero.contrast}:1`
			);
		}

		await page.evaluate(() => {
			const el = document.querySelector('.p-recruit-banner');
			if (el) el.scrollIntoView();
		});

		/*
		 * 背景写真は遅延読み込みなので、読み込み完了を待ってから測る。
		 * 待たずに撮ると写真ではなく下地の色を測ることになり、
		 * 実際より良い数値が出てしまう。
		 */
		await page
			.waitForFunction(
				() => {
					const img = document.querySelector('.p-recruit-banner__media img');
					return !img || (img.complete && img.naturalWidth > 0);
				},
				{ timeout: 10000 }
			)
			.catch(() => {});
		await new Promise((r) => setTimeout(r, 400));

		const banner = await measureContrast(
			page,
			'.p-recruit-banner__body',
			['.p-recruit-banner__title', '.p-recruit-banner__text'],
			`採用バナー（${vp.label}px）`
		);

		if (banner) {
			report.contrast.push(banner);
			console.log(
				`  ${banner.pass ? 'OK ' : 'NG '} ${banner.name}  ${banner.samples}点  ` +
					`最明画素 rgb(${banner.worstPixel.join(',')})  輝度 ${banner.worstLuminance}  ` +
					`コントラスト ${banner.contrast}:1`
			);
		}
	}

	// ---------------------------------------------------------------- 4 & 5
	console.log('\n== 画像の最適化とリクエスト ==');

	await page.setViewport({ width: 1440, height: 900 });
	/*
	 * キャッシュを切ってから測る。
	 * 同一セッションで同じページを何度も開いているため、
	 * そのままだと transferSize が 0（キャッシュヒット）になり転送量を測れない。
	 */
	await page.setCacheEnabled(false);
	await page.goto(BASE + '/', { waitUntil: 'networkidle2' });

	const images = await page.evaluate(() => {
		const list = Array.from(document.images);
		return {
			total: list.length,
			withSrcset: list.filter((i) => i.hasAttribute('srcset')).length,
			withSizes: list.filter((i) => i.hasAttribute('sizes')).length,
			lazy: list.filter((i) => i.getAttribute('loading') === 'lazy').length,
			eager: list.filter((i) => i.getAttribute('loading') === 'eager').length,
			noLoading: list.filter((i) => !i.hasAttribute('loading')).length,
			highPriority: list.filter((i) => i.getAttribute('fetchpriority') === 'high').length,
			// 実際にブラウザが選んだ 1 枚（srcset が効いているかの確認）
			chosen: list.slice(0, 3).map((i) => ({
				current: i.currentSrc.split('/').pop(),
				displayed: `${i.width}x${i.height}`,
			})),
		};
	});

	report.images = images;
	console.log(
		`  img ${images.total} 枚 / srcset ${images.withSrcset} / sizes ${images.withSizes} / ` +
			`lazy ${images.lazy} / eager ${images.eager} / loading無し ${images.noLoading} / ` +
			`fetchpriority=high ${images.highPriority}`
	);

	const perf = await page.evaluate(() => {
		const nav = performance.getEntriesByType('navigation')[0];
		const res = performance.getEntriesByType('resource');
		const byType = {};
		let transfer = nav ? nav.transferSize : 0;

		for (const r of res) {
			const t = r.initiatorType || 'other';
			byType[t] = byType[t] || { count: 0, transfer: 0 };
			byType[t].count++;
			byType[t].transfer += r.transferSize || 0;
			transfer += r.transferSize || 0;
		}

		return {
			requests: res.length + 1,
			transferBytes: transfer,
			byType,
			domContentLoaded: nav ? Math.round(nav.domContentLoadedEventEnd) : null,
			load: nav ? Math.round(nav.loadEventEnd) : null,
		};
	});

	report.performance = perf;
	console.log(
		`  リクエスト ${perf.requests} 件 / 転送量 ${(perf.transferBytes / 1024).toFixed(0)} KB / ` +
			`DOMContentLoaded ${perf.domContentLoaded}ms / load ${perf.load}ms`
	);

	for (const [type, v] of Object.entries(perf.byType)) {
		console.log(`    ${type.padEnd(10)} ${String(v.count).padStart(3)} 件  ${(v.transfer / 1024).toFixed(0)} KB`);
	}

	// ---------------------------------------------------------------- 6
	console.log('\n== Contact Form 7 の送信 → Mailpit ==');

	try {
		await del(`${MAILPIT}/api/v1/messages`);

		await page.goto(BASE + '/contact/', { waitUntil: 'networkidle2' });
		await page.type('input[name="your-name"]', '検証 太郎');
		await page.type('input[name="your-email"]', 'verify@example.test');
		await page.type('textarea[name="your-message"]', '自動検証からの送信テストです。');

		const hasConsent = await page.$('input[name="your-consent"]');
		if (hasConsent) await page.click('input[name="your-consent"]');

		await page.click('.wpcf7-submit');
		await page.waitForSelector('.wpcf7 form.sent, .wpcf7 form.invalid, .wpcf7 form.failed', {
			timeout: 20000,
		});

		const state = await page.evaluate(() => {
			const form = document.querySelector('.wpcf7 form');
			const out = document.querySelector('.wpcf7-response-output');
			return { classes: form ? form.className : '', message: out ? out.textContent.trim() : '' };
		});

		await new Promise((r) => setTimeout(r, 1500));
		const box = await getJson(`${MAILPIT}/api/v1/messages`);
		const received = (box.messages || []).map((m) => ({ subject: m.Subject, to: (m.To || []).map((t) => t.Address) }));

		/*
		 * CF7 は送信成功後に form のクラスを sent → resetting と付け替える。
		 * sent だけを見ると取りこぼすため、失敗クラスが無いことと
		 * 実際にメールが届いたことの両方で判定する。
		 */
		const failedClass = /\b(invalid|failed|spam|aborted)\b/.test(state.classes);
		report.form = { state, received, pass: !failedClass && received.length > 0 };

		console.log(`  ${report.form.pass ? 'OK ' : 'NG '} 状態: ${state.classes.trim()}`);
		console.log(`      メッセージ: ${state.message}`);
		console.log(`      Mailpit 受信: ${received.length} 通 ${received.map((r) => r.subject).join(' / ')}`);
	} catch (e) {
		report.form = { error: e.message, pass: false };
		console.log(`  NG  フォーム検証に失敗: ${e.message}`);
	}

	// ---------------------------------------------------------------- 保存
	await browser.close();

	const outFile = path.join(OUT_DIR, 'report.json');
	fs.writeFileSync(outFile, JSON.stringify(report, null, 2) + '\n');
	console.log(`\n結果を書き出しました: ${path.relative(ROOT, outFile)}`);

	const failed =
		report.overflow.length > 0 ||
		report.contrast.some((c) => !c.pass) ||
		(report.form && !report.form.pass) ||
		report.pages.some((p) => p.status !== (p.url === '/no-such-page-here/' ? 404 : 200)) ||
		report.pages.some((p) => (p.smallTargets || []).length > 0) ||
		report.pages.some((p) => (p.imagesWithoutAlt || []).length > 0);

	process.exit(failed ? 1 : 0);
}

main().catch((e) => {
	console.error(e);
	process.exit(1);
});
