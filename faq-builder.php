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

require_once('viewer.php');
require_once('openai.php');

add_action('init', 'FAQBuilder::init');
register_activation_hook(__FILE__, 'FAQBuilder::activate');
register_deactivation_hook(__FILE__, 'FAQBuilder::deactivate');

// ショートコードの登録
add_shortcode('faq-builder', 'show_faq');

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

    function activate() {
        // 必要なデータベーステーブルを作成
        global $wpdb;
        $table_name = $wpdb->prefix . 'faq_builder_items';
        $charset_collate = $wpdb->get_charset_collate();
    
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            question text NOT NULL,
            answer text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    function deactivate() {
        // データベーステーブルを削除
        global $wpdb;
        $table_name = $wpdb->prefix . 'faq_builder_items';
        $sql = "DROP TABLE IF EXISTS $table_name;";
        $wpdb->query($sql);
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
            [$this, 'show_config_form']);
    }

    function show_about_plugin() {
        // 画面に表示するHTML
?>
        <style>
            input[type="file"],
            input[type="submit"] {
                display: block;
                margin-bottom: 10px; /* 必要に応じてマージンを調整 */
            }
        </style>
        <div class="wrap">
            <h1>FAQ Builder</h1>
            <?php // 設定完了時のメッセージ ?>
            <?php if ($completed_text = get_transient(self::COMPLETE_CONFIG)) : ?>
                <div class="updated">
                    <p><?= $completed_text ?></p>
                </div>
            <?php endif; ?>
            <p>PDFからFAQを生成します</p>
            <form action="" method="post" enctype="multipart/form-data">
                <?php // nonce の設定 ?>
                <?php wp_nonce_field(self::CREDENTIAL_ACTION, self::CREDENTIAL_NAME) ?>
                <p>
                    <label for="pdf_file">PDFファイル：</label>
                    <input type="file" name="pdf_file">
                </p>
                <input type="submit" value="送信" class="buttton button-primary button-large">
            </form>
        </div>
<?php
    }

    function show_config_form() {
      $api_key = get_option(self::PLUGIN_DB_PREFIX . "api_key");
?>
      <div class="wrap">
        <h1>OpenAI API Key の設定</h1>

        <?php // 設定完了時のメッセージ ?>
        <?php if ($completed_text = get_transient(self::COMPLETE_CONFIG)) : ?>
            <div class="updated">
                <p><?= $completed_text ?></p>
            </div>
        <?php endif; ?>

        <form action="" method='post' id="my-submenu-form">
            <?php // nonce の設定 ?>
            <?php wp_nonce_field(self::CREDENTIAL_ACTION, self::CREDENTIAL_NAME) ?>

            <p>
              <label for="api_key">APIキー：</label>
              <input type="text" name="api_key" value="<?= $api_key ?>"/>
            </p>

            <p><input type='submit' value='保存' class='button button-primary button-large'></p>
        </form>
      </div>
<?php
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

        // PDF ファイルの処理
        if (isset($_FILES['pdf_file'])) {
            $pdf_file = $_FILES['pdf_file'];
            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['path'];
            $upload_file = $upload_path . "/" . $pdf_file['name'];
            if (move_uploaded_file($pdf_file['tmp_name'], $upload_file)) {
                $api_key = get_option(self::PLUGIN_DB_PREFIX . "api_key");
                $openai = new OpenAI($api_key);
                $file_id = $openai->upload_file($upload_file);
                $completed_text = "PDFファイルのアップロードが完了しました。";
                set_transient(self::COMPLETE_CONFIG, $completed_text, 5);
                wp_safe_redirect(menu_page_url(''), 301);
            }
        }
    }
} 

?>
