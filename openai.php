<?php

class OpenAI
{
    private $api_key;
    private $custom_function;

    function __construct($api_key)
    {
        if (!$api_key) throw new Exception("APIキーが設定されていません");
        $this->api_key = $api_key;

        $this->custom_function = $this->get_custom_function();
    }

    function generate_faq($pdf_file_path)
    {
        // cURLセッションの初期化
        $ch = curl_init();

        // ファイルをアップロード
        $file_data = array('file' => new CURLFile($pdf_file_path), 'purpose' => 'assistants');
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/files");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $file_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . $this->api_key
        ));
        $file_response = json_decode(curl_exec($ch), true);
        $file_id = $file_response['id'];

        // アシスタントを作成
        $assistant_data = json_encode(array(
            "name" => "FAQ Builder",
            "instructions" => "PDFのマニュアルを受け取って FAQ サイトを生成してください。FAQは日本語で生成してください。",
            "tools" => array(
                array("type" => "retrieval"),
                array("type" => "function", "function" => $this->custom_function)
            ),
            "model" => "gpt-4-1106-preview",
            "file_ids" => array($file_id)
        ));
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/assistants");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $assistant_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . $this->api_key,
            "Content-Type: application/json",
            "OpenAI-Beta: assistants=v1"
        ));
        $assistant_response = json_decode(curl_exec($ch), true);
        $assistant_id = $assistant_response['id'];

        // スレッドを作成
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/threads");
        curl_setopt($ch, CURLOPT_POSTFIELDS, array());
        $thread_response = json_decode(curl_exec($ch), true);
        $thread_id = $thread_response['id'];

        // メッセージを送信
        $message_data = array(
            'role' => "user",
            'content' => "このマニュアルからFAQを生成してください。"
        );
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/threads/$thread_id/messages");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message_data));
        curl_exec($ch);

        // 実行を開始
        $run_data = array(
            'assistant_id' => $assistant_id,
        );
        curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/threads/$thread_id/runs");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($run_data));
        $run_response = json_decode(curl_exec($ch), true);
        $run_id = $run_response['id'];

        // 実行ステータスの確認
        do {
            curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/threads/$thread_id/runs/$run_id");
            curl_setopt($ch, CURLOPT_POST, false);
            $run_status_response = json_decode(curl_exec($ch), true);
            $run_status = $run_status_response['status'];
            sleep(5);
        } while ($run_status != "requires_action");

        // 結果を取得
        $result = $run_status_response['required_action']['submit_tool_outputs']['tool_calls'][0]['function']['arguments'];
        $result = json_decode($result, true);

        // cURLセッションを閉じる
        curl_close($ch);

        return $result["faq_items"];
    }

    private function get_custom_function()
    {
        return array(
            "name" => "generate_faq",
            "description" => "Generates a FAQ from a manual.",
            "parameters" => array(
                "type" => "object",
                "properties" => array(
                    "faq_items" => array(
                        "type" => "array",
                        "description" => "A list of FAQ items.",
                        "items" => array(
                            "type" => "object",
                            "properties" => array(
                                "question" => array(
                                    "type" => "string",
                                    "description" => "The question."
                                ),
                                "answer" => array(
                                    "type" => "string",
                                    "description" => "The answer."
                                )
                            ),
                            "required" => array("question", "answer")
                        )
                    )
                ),
                "required" => array("faq_items")
            )
        );
    }
}
