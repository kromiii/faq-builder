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

add_action('init', 'FAQBuilder::init');
register_activation_hook(__FILE__, 'your_plugin_activate');
register_deactivation_hook(__FILE__, 'your_plugin_deactivate');

function your_plugin_activate() {
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

function your_plugin_deactivate() {
    // データベーステーブルを削除
    global $wpdb;
    $table_name = $wpdb->prefix . 'faq_builder_items';
    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);
}

function faq_boostar_shortcode() {
    global $wpdb;
    $api_key = get_option("faq-builder_api_key");
    $html = "";
    $faq_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}faq_builder_items");
    $html .= "<div class='faq-boostar'>";
    $html .= "<div class='faq-boostar__inner'>";
    foreach ($faq_items as $faq_item) {
        $html .= "<h3>";
        $html .= $faq_item->question;
        $html .= "</h3>";
        $html .= "<div class='faq-boostar__answer'>";
        $html .= "<div class='faq-boostar__answer__text'>";
        $html .= $faq_item->answer;
        $html .= "</div>";
        $html .= "</div>";
    }
    $html .= "</div>";
    $html .= "</div>";
    return $html;
}
add_shortcode('faq-boostar', 'faq_boostar_shortcode');


class FAQBuilder
{
    const PLUGIN_ID         = 'faq-builder';
    const CREDENTIAL_ACTION = self::PLUGIN_ID . '-nonce-action';
    const CREDENTIAL_NAME   = self::PLUGIN_ID . '-nonce-key';
    const PLUGIN_DB_PREFIX  = self::PLUGIN_ID . '_';
    const COMPLETE_CONFIG   = self::PLUGIN_ID . '-complete-config';

     	// config画面のslug
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



    function set_plugin_menu()
    {
        add_menu_page(
            'FAQ Builder',           /* ページタイトル*/
            'FAQ Builder',           /* メニュータイトル */
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
        // 画面に表示するHTML
?>
        <div class="wrap">
            <h1>FAQ Builder</h1>
            <?php // ③：設定完了時のメッセージ ?>
            <?php if ($completed_text = get_transient(self::COMPLETE_CONFIG)) : ?>
                <div class="updated">
                    <p><?= $completed_text ?></p>
                </div>
            <?php endif; ?>
            <p>PDFからFAQを生成します</p>
            <form action="" method="post" enctype="multipart/form-data">
                <input type="file" name="pdf_file">
                <input type="submit" value="Upload PDF">
            </form>
        </div>
<?php
        // $html = "<h1>FAQ Builder</h1>";
        // $html .= "<p>PDFからFAQを生成します</p>";
        // // ファイルアップロードボタンの追加
        // $html .= '<form action="" method="post" enctype="multipart/form-data">';
        // $html .= '<input type="file" name="pdf_file">';
        // $html .= '<input type="submit" value="Upload PDF">';
        // $html .= '</form>';
        // echo $html;
    }

    function show_config_form() {
      // ① wp_optionsのデータをひっぱってくる
      $api_key = get_option(self::PLUGIN_DB_PREFIX . "api_key");
?>
      <div class="wrap">
        <h1>OPENAI API KEY の設定</h1>

        <?php // ③：設定完了時のメッセージ ?>
        <?php if ($completed_text = get_transient(self::COMPLETE_CONFIG)) : ?>
            <div class="updated">
                <p><?= $completed_text ?></p>
            </div>
        <?php endif; ?>

        <form action="" method='post' id="my-submenu-form">
            <?php // ②：nonceの設定 ?>
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

    /** 設定画面の項目データベースに保存する */
    function save_config()
    {
        // nonceで設定したcredentialのチェック 
        if (isset($_POST[self::CREDENTIAL_NAME]) && $_POST[self::CREDENTIAL_NAME]) {
            if (check_admin_referer(self::CREDENTIAL_ACTION, self::CREDENTIAL_NAME)) {
            
                // 保存処理
                $key   = 'api_key';
                $value = $_POST['api_key'] ? $_POST['api_key'] : "";
                                        
                update_option(self::PLUGIN_DB_PREFIX . $key, $value);
                $completed_text = "設定の保存が完了しました。管理画面にログインした状態で、トップページにアクセスし変更が正しく反映されたか確認してください。";
                
                // 保存が完了したら、wordpressの機構を使って、一度だけメッセージを表示する
                set_transient(self::COMPLETE_CONFIG, $completed_text, 5);
                
                // 設定画面にリダイレクト
                wp_safe_redirect(menu_page_url(self::CONFIG_MENU_SLUG), 301);
            }
        }

        // PDF ファイルがアップロードされた場合
        if (isset($_FILES['pdf_file'])) {
            set_transient(self::COMPLETE_CONFIG, "PDFファイルのアップロードが完了しました。", 5);
            wp_safe_redirect(menu_page_url(''), 301);
        }
    }

} // end of class

?>
