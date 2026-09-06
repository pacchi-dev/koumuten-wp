移設手順（https://pacchi.dev/koumuten）

【重要】このダンプはテーブル接頭辞を km_ に変更してあります。
設置先の wp-config.php で必ず次のように設定してください。

    $table_prefix = 'km_';

これを忘れると、WordPress が自分のテーブルを見つけられず
インストール画面が表示されます。

前提: 設置先に WordPress がインストール済みであること。

1. プラグイン Advanced Custom Fields と Contact Form 7 を
   インストールして有効化する。
   DB ダンプ側で有効化済みの状態になっているため、
   ファイルが無いと管理画面でエラーになる。先に入れておく。

2. FTP で wp-content/ の中身をアップロードする。
     wp-content/themes/soranowa-koumuten/
     wp-content/plugins/koumuten-core/
     wp-content/uploads/

3. phpMyAdmin で database.sql をインポートする。

4. 管理画面にログインし、「設定 > パーマリンク」を開いて保存する。
   カスタム投稿タイプ /works/ のリライトルールを再生成するために必要。

5. 動作確認
     https://pacchi.dev/koumuten/           トップ
     https://pacchi.dev/koumuten/works/     施工実績の一覧
     https://pacchi.dev/koumuten/contact/   お問い合わせ
   404 が出る場合は 4 をやり直す。

6. お問い合わせフォームの送信を試す。

注意
- 管理者のパスワードはローカルと同じものが入っている。
  ログイン後すぐに変更すること。
- mu-plugins（ローカル SMTP）は含めていない。
