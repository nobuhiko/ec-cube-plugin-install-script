# ec-cube-plugin-install-script #
EC-CUBEのプラグインは開発時にファイルの共有がツライので、ファイルはgitなどでそのまま管理し、installをコマンドラインから実行するだけのものです。


### 使い方 ###
* htmlディレクトリと同じ階層にディレクトリごと設置する
* コマンドラインからplugin_install.phpを実行する

```
php plugin_install.php
```


### 引数 ###
-f[plugin_code] 指定したpluginのみインストールする
```
php plugin_install.php -f[plugin_code]
```

-e インストール後、プラグインを有効にする
```
php plugin_install.php -e
```

