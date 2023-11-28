<?php
class OpenAI
{
  private $ch;

  function __construct($openaiApiKey)
  {
    $this->ch = curl_init();
    if ($openaiApiKey) {
      curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($this->ch, CURLOPT_POST, 1);
      curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
          'Authorization: Bearer ' . $openaiApiKey,
      ));
    } else {
      throw new Exception("APIキーが設定されていません");
    }
      
  }

  function upload_file($file_path)
  {
    curl_setopt($this->ch, CURLOPT_URL, 'https://api.openai.com/v1/files');


    // ファイルと目的を POST フィールドとして追加
    $postFields = array(
        'purpose' => 'assistants',
        'file' => new CURLFile($file_path)
    );
    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $postFields);

    // cURL リクエストを実行
    $result = curl_exec($this->ch);

    // エラーが発生した場合はエラー内容を表示
    if (curl_errno($this->ch)) {
        echo 'Error:' . curl_error($this->ch);
    }

    // cURL セッションを閉じる
    curl_close($this->ch);

    // ファイルIDを返す
    return $result->id;
  }
}