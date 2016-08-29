=== wp-sacloud-webaccel ===
Contributors: yamamotofebc
Donate link:
Tags: SakuraCloud, アクセラレータ, さくらのクラウド, さくらインターネット, 一方通行, CDN
Requires at least: 4.5.3
Tested up to: 4.6.0
Stable tag: 0.0.1
License: GPLv3 or later.
License URI: http://www.gnu.org/licenses/gpl-3.0.html

WordPressとさくらのクラウド ウェブアクセラレータを連携させるためのプラグイン

== Description ==

[さくらのクラウド ウェブアクセラレータ](http://cloud.sakura.ad.jp/specification/option/#option-content05)との連携を行います。

ウェブアクセラレータを利用することにより、オリジンサーバーへの負荷を最小限にしつつ、アクセス急増時でも安定してサイトを表示することができます。

このプラグインを利用することで、ウェブアクセラレータとの連携に必要なレスポンスヘッダの出力や、データ更新時のキャッシュクリアなどが自動化されます。

[サブドメイン方式](http://cloud-news.sakura.ad.jp/webaccel/manual02/)での連携にも対応しており、
メディアファイルのURLのみをウェブアクセラレータのサブドメインURLに書き換えることができます。

= Features =

* 以下のページ/ファイルへのリクエストをウェブアクセラレータでキャッシュできるようにします。
  - 投稿
  - 固定ページ
  - アーカイブ
    - タグ
    - カテゴリ
    - カスタムタクソノミー
    - 日付(年/年月/年月日)
  -メディアファイル

  メディアファイル以外はフィード(RSS/Atom/RDF)のキャッシュも行います。

* WordPress管理画面でのデータ更新時、ウェブアクセラレータでのキャッシュをクリアします。

* サブドメインを利用するように設定した場合、メディアファイルの配信URLをウェブアクセラレータが提供するサブドメインに書き換えを行います。

* WordPress管理画面、またはWP-CLIからキャッシュの全削除を行えます。

= 使い方とサポート =

[GitHub](https://github.com/yamamoto-febc/wp-sacloud-webaccel/tree/master/docs)では、プラグインのインストール方法や設定方法などを掲載しています。

== Installation ==

ダウンロードしたプラグインのZipファイルを、/wp-content/plugins/ディレクトリにアップロードします。

ワードプレスのダッシュボード内の「プラグインメニュー」からプラグインを有効にします。

ダッシュボードの『プラグイン新規追加』からの追加も可能です。

== Frequently Asked Questions ==

お問い合わせはGitHubのIssueにてお願い致します。
https://github.com/yamamoto-febc/wp-sacloud-webaccel

== Screenshots ==
1. screenshot-1.png

== Changelog ==

0.0.1 : 初回リリース

== Upgrade Notice ==

== Arbitrary section 1 ==