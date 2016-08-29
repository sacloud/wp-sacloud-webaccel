# WordPress plugin for SakuraCloud Web Accelerator

![eye-catch.jpg](docs/images/eye-catch.jpg)

[さくらのクラウド ウェブアクセラレータ](http://cloud.sakura.ad.jp/specification/option/#option-content05)との連携を行います。

ウェブアクセラレータを利用することにより、オリジンサーバーへの負荷を最小限にしつつ、アクセス急増時でも安定してサイトを表示することができます。

このプラグインを利用することで、ウェブアクセラレータとの連携に必要なレスポンスヘッダの出力や、データ更新時のキャッシュクリアなどが自動化されます。

[サブドメイン方式](http://cloud-news.sakura.ad.jp/webaccel/manual02/)での連携にも対応しており、
メディアファイルのURLのみをウェブアクセラレータのサブドメインURLに書き換えることができます。

## スクリーンショット

![screenshot-1.png](screenshot-1.png)


## インストール

WordPressの管理ページからインストール可能です。
詳細は[インストール/設定](docs/README.md)を参照してください。

## インストール(手動)

手動でインストールする場合は次のようにしてください。

1. 以下のコマンドを実行してください。
2. 管理画面の「プラグイン」メニューから有効化してください。

```bash

# Move into WordPress root
cd [WORDPRESS_ROOT]/wp-content/plugins

# Clone plugin repository
git clone https://github.com/yamamoto-febc/wp-sacloud-webaccel
cd wp-sacloud-webaccel

```

# License

GPLv3

# Copyright

Copyright 2016 Kazumichi Yamamoto
