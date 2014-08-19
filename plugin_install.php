<?php
/*
 * プラグインインストールコマンド
 * Copyright (C) 2013 Nobuhiko Kimoto
 * info@nob-log.info
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

$path = realpath('../html/require.php');
require_once($path);
require_once CLASS_REALDIR . 'pages/admin/ownersstore/LC_Page_Admin_OwnersStore.php';

// -f[ファイル名] 指定したファイルをインストールする
// -e インストール後、有効にする
$options = getopt("f:e");

if (isset($options['f'])) {
    print_r(installPlugin($options['f'], isset($options['e'])));
} else {
    $d = dir(constant("PLUGIN_UPLOAD_REALDIR"));
    $enable_option = isset($options['e']) ? '-e' : '';
    while (false !== ($filename = $d->read())) {
        if ($filename != '.' && $filename != '..'){
            system("php plugin_install.php -f$filename $enable_option");
        }
    }
}
exit;

function installPlugin($key, $enable = 0) {

    $objQuery =& SC_Query_Ex::getSingletonInstance();
    $objQuery->begin();
    $plugin_dir_path = PLUGIN_UPLOAD_REALDIR . $key . '/';

    $objOwner = new LC_Page_Admin_OwnersStore();
    // plugin_infoを読み込み.
    $arrErr = $objOwner->requirePluginFile($plugin_dir_path . 'plugin_info.php', $key);
    if ($objOwner->isError($arrErr) === true) {
        return $arrErr;
    }

    // リフレクションオブジェクトを生成.
    $objReflection = new ReflectionClass('plugin_info');
    $arrPluginInfo = $objOwner->getPluginInfo($objReflection);
    // プラグインクラスに必須となるパラメータが正常に定義されているかチェックします.
    $arrErr = $objOwner->checkPluginConstants($objReflection, $plugin_dir_path);
    if ($objOwner->isError($arrErr) === true) {
        return $arrErr;
    }

    // 既にインストールされていないかを判定.
    if ($objOwner->isInstalledPlugin($arrPluginInfo['PLUGIN_CODE']) === true) {
        return '※ ' . $arrPluginInfo['PLUGIN_NAME'] . 'は既にインストールされています。' . "\n";
    }

    // プラグイン情報をDB登録
    if ($objOwner->registerData($arrPluginInfo) === false) {
        return '※ DB登録に失敗しました。' . "\n";
    }

    // プラグイン情報を取得
    $plugin = SC_Plugin_Util_Ex::getPluginByPluginCode($arrPluginInfo['PLUGIN_CODE']);

    // クラスファイルを読み込み.
    $plugin_class_file_path = $objOwner->getPluginFilePath($plugin['plugin_code'], $plugin['class_name']);
    $arrErr = $objOwner->requirePluginFile($plugin_class_file_path, $key);
    if ($objOwner->isError($arrErr) === true) {
        return $arrErr;
    }
    // プラグインhtmlディレクトリ作成
    $plugin_html_dir_path = $objOwner->getHtmlPluginDir($plugin['plugin_code']);
    $objOwner->makeDir($plugin_html_dir_path);

    $arrErr = $objOwner->execPlugin($plugin, $plugin['class_name'], 'install');
    if ($objOwner->isError($arrErr) === true) {
        return $arrErr;
    }

    if ($enable == true) {
        $arrErr = $objOwner->enablePlugin($plugin);
        if ($objOwner->isError($arrErr) === false) {
            // TODO 全プラグインのインスタンスを保持したまま後続処理が実行されるので、全てのインスタンスを解放する。
            unset($GLOBALS['_SC_Helper_Plugin_instance']);
            // コンパイルファイルのクリア処理
            SC_Utils_Ex::clearCompliedTemplate();
            //$this->tpl_onload = "alert('" . $plugin['plugin_name'] . "を有効にしました。');";
        } else {
            return $arrErr;
        }
    }

    $objQuery->commit();
    return $arrPluginInfo['PLUGIN_NAME'] . 'のインストールに成功しました。' . "\n";
}
