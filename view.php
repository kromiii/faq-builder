<?php

class View
{
    function __construct()
    {
    }

    function generate_faq_section($faq_items)
    {
        $html = "";
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

    function show_config($api_key, $completed_text)
    {
?>
        <div class="wrap">
            <h1>設定</h1>
            <p> OpenAI APIキーを入力してください。</p>

            <?php // 設定完了時のメッセージ 
            ?>
            <?php if ($completed_text) : ?>
                <div class="updated">
                    <p><?= $completed_text ?></p>
                </div>
            <?php endif; ?>

            <form action="" method='post' id="my-submenu-form">
                <?php // nonce の設定 
                ?>
                <?php wp_nonce_field(FAQBuilder::CREDENTIAL_ACTION, FAQBuilder::CREDENTIAL_NAME) ?>

                <p>
                    <label for="api_key">APIキー：</label>
                    <input type="text" name="api_key" value="<?= $api_key ?>" />
                </p>

                <p><input type='submit' value='保存' class='button button-primary button-large'></p>
            </form>
        </div>
    <?php
    }

    function show_about_plugin()
    {
    ?>
        <style>
            input[type="file"],
            input[type="submit"] {
                display: block;
                margin-bottom: 10px;
                /* 必要に応じてマージンを調整 */
            }
        </style>
        <div class="wrap">
            <h1>FAQ Builder</h1>
            <p>PDFからFAQを生成します<br />FAQの抽出には３〜５分ほどかかるので気長にお待ちください。</p>
            <form action="" method="post" enctype="multipart/form-data">
                <?php // nonce の設定 
                ?>
                <?php wp_nonce_field(FAQBuilder::CREDENTIAL_ACTION, FAQBuilder::CREDENTIAL_NAME) ?>
                <p>
                    <label for="pdf_file">PDFファイル：</label>
                    <input type="file" name="pdf_file">
                </p>
                <input type="submit" value="送信" class="buttton button-primary button-large">
            </form>
        </div>
<?php
    }
}
