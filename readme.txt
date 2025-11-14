=== wp-sacloud-webaccel ===
Contributors: yamamotofebc,sacloudusers
Donate link:
Tags: SakuraCloud, さくらのウェブアクセラレータ, ウェブアクセラレータ, さくらのクラウド, さくらインターネット, CDN
Requires at least: 4.5.3
Tested up to: 6.8.3
Stable tag: 0.0.16
License: GPLv3 or later.
License URI: http://www.gnu.org/licenses/gpl-3.0.html

WordPressとさくらのウェブアクセラレータを連携させるためのプラグイン

== Description ==

[さくらのウェブアクセラレータ](https://cloud.sakura.ad.jp/specification/web-accelerator/)との連携を行います。

さくらのウェブアクセラレータを利用することにより、オリジンサーバーへの負荷を最小限にしつつ、アクセス急増時でも安定してサイトを表示することができます。

このプラグインを利用することで、さくらのウェブアクセラレータとの連携に必要なレスポンスヘッダの出力や、データ更新時のキャッシュクリアなどが自動化されます。

[サブドメイン方式](https://manual.sakura.ad.jp/cloud/webaccel/manual/settings-subdomain.html)での連携にも対応しており、
メディアファイルのURLのみをさくらのウェブアクセラレータのサブドメインURLに書き換えることができます。

= Features =

* 以下のページ/ファイルへのリクエストをさくらのウェブアクセラレータでキャッシュできるようにします。
  - 投稿
  - 固定ページ
  - アーカイブ
    - タグ
    - カテゴリ
    - カスタムタクソノミー
    - 日付(年/年月/年月日)
  -メディアファイル

  メディアファイル以外はフィード(RSS/Atom/RDF)のキャッシュも行います。

なお、ログインユーザーによるリクエスト、ページング2ページ目以降、検索結果ページはキャッシュを行いません。

* WordPress管理画面でのデータ更新時、さくらのウェブアクセラレータでのキャッシュをクリアします。

* サブドメインを利用するように設定した場合、メディアファイルの配信URLをさくらのウェブアクセラレータが提供するサブドメインに書き換えを行います。

* WordPress管理画面、またはWP-CLIからキャッシュの全削除を行えます。

= 使い方とサポート =

[GitHub](https://github.com/sacloud/wp-sacloud-webaccel/blob/master/docs/README.md)では、プラグインのインストール方法や設定方法などを掲載しています。

== Installation ==

ダウンロードしたプラグインのZipファイルを、/wp-content/plugins/ディレクトリにアップロードします。

ワードプレスのダッシュボード内の「プラグインメニュー」からプラグインを有効にします。

ダッシュボードの『プラグイン新規追加』からの追加も可能です。

インストール後の設定については[GitHub上のドキュメント「インストール/設定」](https://github.com/sacloud/wp-sacloud-webaccel/blob/master/docs/README.md)を参照してください。

== Frequently Asked Questions ==

お問い合わせはGitHubのIssueにてお願い致します。
https://github.com/sacloud/wp-sacloud-webaccel

== Screenshots ==
1. screenshot-1.png

== Changelog ==

0.0.16: [さくらのウェブアクセラレータへ名称変更,Tested up toを6.8.3に更新](https://github.com/sacloud/wp-sacloud-webaccel/releases/tag/v0.0.16)

0.0.15: [Tested up toを6.8.2に更新](https://github.com/sacloud/wp-sacloud-webaccel/releases/tag/v0.0.15)

0.0.14: [アクセストークン入力欄をtype: passwordに](https://github.com/sacloud/wp-sacloud-webaccel/releases/tag/v0.0.14)

0.0.9 : [冗長なAPI呼び出しの抑制](https://github.com/yamamoto-febc/wp-sacloud-webaccel/releases/tag/v0.0.8)

0.0.8 : [サブドメイン利用時の画像URL書き換えを管理画面では行わない](https://github.com/yamamoto-febc/wp-sacloud-webaccel/releases/tag/v0.0.8)

0.0.7 : [サブドメイン利用時のwp_get_attachment_url利用によるURL書き換え対応,sacloud_nocacheカスタムフィールドでのキャッシュ無効化の制御](https://github.com/yamamoto-febc/wp-sacloud-webaccel/releases/tag/v0.0.7)

0.0.6 : [キャッシュ全削除APIを利用](https://github.com/yamamoto-febc/wp-sacloud-webaccel/releases/tag/v0.0.6)

0.0.4 : [サブドメイン型でのURL書き換えにてsrcset属性の書き換えに対応](https://github.com/yamamoto-febc/wp-sacloud-webaccel/releases/tag/v0.0.4)

0.0.3 : [WordPress管理画面での画像編集への対応など](https://github.com/yamamoto-febc/wp-sacloud-webaccel/releases/tag/v0.0.3)

0.0.2 : [初回リリース](https://github.com/yamamoto-febc/wp-sacloud-webaccel/releases/tag/v0.0.2)

== Upgrade Notice ==

== Arbitrary section 1 ==
