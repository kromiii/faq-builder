<?php
/*
  Plugin Name: FAQ Builder
  Plugin URI:
  Description: PDF から FAQ を作成するプラグイン
  Version: 0.0.3
  Author: Hiroyuki KUROMIYA
  Author URI: https://github.com/kromiii
  License: GPLv2
 */

?>

<?php
add_action('init', 'CustomIndexBanner::init');

class CustomIndexBanner
{
    static function init()
    {
        return new self();
    }

    function __construct()
    {
        if (is_admin() && is_user_logged_in()) {
            // メニュー追加
            add_action('admin_menu', [$this, 'set_plugin_menu']);
            add_action('admin_menu', [$this, 'set_plugin_sub_menu']);

        }
    }

    function set_plugin_menu()
    {
        add_menu_page(
            'カスタムバナー',           /* ページタイトル*/
            'カスタムバナー',           /* メニュータイトル */
            'manage_options',         /* 権限 */
            'custom-index-banner',    /* ページを開いたときのURL */
            [$this, 'show_about_plugin'],       /* メニューに紐づく画面を描画するcallback関数 */
            'dashicons-format-gallery', /* アイコン see: https://developer.wordpress.org/resource/dashicons/#awards */
            99                          /* 表示位置のオフセット */
        );
    }
    function set_plugin_sub_menu() {

        add_submenu_page(
            'custom-index-banner',  /* 親メニューのslug */
            '設定',
            '設定',
            'manage_options',
            'custom-index-banner-config',
            [$this, 'show_config_form']);
    }

    function show_about_plugin() {
      $html = "<h1>カスタムバナー</h1>";
      $html .= "<p>トップページに表示するバナーを指定できます</p>";

      echo $html;
    }

    function show_config_form() {
?>
        <h1>カスタムバナーの設定</h1>
<?php
    }

} // end of class

?>
