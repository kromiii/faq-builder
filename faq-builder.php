<?php
/*
    Plugin Name: FAQ Builder
    Plugin URI: https://github.com/kromiii/faq-builder-wp
    Description: PDF から FAQ を作成するプラグイン
    Version: 0.1.0
    Author: Hiroyuki KUROMIYA
    Author URI: https://github.com/kromiii
    License: MIT
*/

require_once('openai.php');
require_once('db.php');
require_once('view.php');

add_action('init', 'FAQBuilder::init');
register_activation_hook(__FILE__, 'FAQBuilder::activate');
register_deactivation_hook(__FILE__, 'FAQBuilder::deactivate');

// ショートコードの登録
add_shortcode('faq-builder', 'FAQBuilder::show_faq');

class FAQBuilder
{
    const PLUGIN_ID         = 'faq-builder';
    const CREDENTIAL_ACTION = self::PLUGIN_ID . '-nonce-action';
    const CREDENTIAL_NAME   = self::PLUGIN_ID . '-nonce-key';
    const PLUGIN_DB_PREFIX  = self::PLUGIN_ID . '_';
    const COMPLETE_CONFIG   = self::PLUGIN_ID . '-complete-config';

    // 設定画面のslug
    const CONFIG_MENU_SLUG  = self::PLUGIN_ID . '-config';

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

            // コールバック関数定義
            add_action('admin_init', [$this, 'save_config']);
        }
    }

    public static function activate()
    {
        // 必要なデータベーステーブルを作成
        $db = new DB();
        $db->create_table();
    }

    public static function deactivate()
    {
        // データベーステーブルを削除
        $db = new DB();
        $db->delete_table();
    }

    public static function show_faq()
    {
        $db = new DB();
        $faq_items = $db->get_faq_items();
        $view = new View();
        return $view->generate_faq_section($faq_items);
    }

    function set_plugin_menu()
    {
        add_menu_page(
            'FAQ Builder',           /* ページタイトル*/
            'FAQ Builder',           /* メニュータイトル */
            'manage_options',         /* 権限 */
            self::PLUGIN_ID,    /* ページを開いたときのURL */
            [$this, 'show_about_plugin'],       /* メニューに紐づく画面を描画するcallback関数 */
            'dashicons-format-gallery', /* アイコン see: https://developer.wordpress.org/resource/dashicons/#awards */
            99                          /* 表示位置のオフセット */
        );
    }

    function set_plugin_sub_menu()
    {
        add_submenu_page(
            self::PLUGIN_ID,  /* 親メニューのslug */
            '設定',
            '設定',
            'manage_options',
            self::PLUGIN_ID . self::CONFIG_MENU_SLUG,
            [$this, 'show_config_form']
        );
    }

    function show_about_plugin()
    {
        $view = new View();
        return $view->show_about_plugin();
    }

    function show_config_form()
    {
        $api_key = get_option(self::PLUGIN_DB_PREFIX . "api_key");
        $completed_text = get_transient(self::COMPLETE_CONFIG);
        $view = new View();
        return $view->show_config($api_key, $completed_text);
    }

    /** コールバック関数 */
    function save_config()
    {
        // nonce のチェック
        if (!isset($_POST[self::CREDENTIAL_NAME])) {
            return;
        }

        // nonce のチェック
        if (!wp_verify_nonce($_POST[self::CREDENTIAL_NAME], self::CREDENTIAL_ACTION)) {
            return;
        }

        // API キーの保存
        if (isset($_POST['api_key'])) {
            $api_key = $_POST['api_key'];
            update_option(self::PLUGIN_DB_PREFIX . "api_key", $api_key);
            $completed_text = "APIキーの保存が完了しました。";
            set_transient(self::COMPLETE_CONFIG, $completed_text, 5);
            wp_safe_redirect(menu_page_url(self::CONFIG_MENU_SLUG), 301);
        }

        if (isset($_FILES['pdf_file'])) {
            // PDFファイルのアップロード
            $pdf_file = $_FILES['pdf_file'];
            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['path'];
            $upload_file = $upload_path . "/" . $pdf_file['name'];
            if (move_uploaded_file($pdf_file['tmp_name'], $upload_file)) {
                // FAQの抽出
                $api_key = get_option(self::PLUGIN_DB_PREFIX . "api_key");
                $openai = new OpenAI($api_key);
                $faq_items = $openai->generate_faq($upload_file);
                // DBに保存
                $db = new DB();
                $db->save_faq_items($faq_items);
                wp_safe_redirect(menu_page_url(''), 301);
                echo '<script type="text/javascript">alert("FAQの抽出が完了しました。");</script>';
            }
        }
    }
}
