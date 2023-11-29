<?php

include_once('custom_function.php');

class OpenAI
{
  private $api_key;

  function __construct($api_key)
  {
    if (!$api_key) throw new Exception("APIキーが設定されていません");
    $this->api_key = $api_key;
  }

  function upload_file($file_path)
  {
    // cURL セッションを初期化
    $ch = curl_init();

    // cURL オプションを設定
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/files');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer " . $this->api_key
    ));

    // ファイルと目的を POST フィールドとして追加
    $postFields = array(
        'purpose' => 'assistants',
        'file' => new CURLFile($file_path)
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

    // cURL リクエストを実行
    $result = curl_exec($ch);

    // エラーが発生した場合はエラー内容を表示
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }

    // cURL セッションを閉じる
    curl_close($ch);

    // ファイルIDを返す
    return $result->id;
  }

  function create_assistant()
  {
    $ch = curl_init();

    // cURLオプションを設定
    curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/assistants");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $this->api_key,
        "OpenAI-Beta: assistants=v1"
    ));

    $data = json_encode(array(
      "instructions" => "PDFのマニュアルを受け取って FAQ サイトを生成してください。FAQは日本語で生成してください。",
      "name" => "FAQ Builder",
      "tools" => array(
        array("type" => "code_interpreter"),
        array("type" => "function", "function" => $custom_function),
      ),
      "model" => "gpt-4"
    ));
  
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    // cURLリクエストを実行
    $response = curl_exec($ch);
    
    // エラーチェック
    if (curl_errno($ch)) {
        echo 'cURLエラー: ' . curl_error($ch);
    }
    
    // cURLセッションを閉じる
    curl_close($ch);

    return $response->id;
  }
}