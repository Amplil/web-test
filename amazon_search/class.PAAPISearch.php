<?php
if ( !class_exists( 'PAAPISearch' ) ):
class PAAPISearch
{
    public $kw = null; //検索キーワード
    public $response = null; //APIから取得したJSONをここに格納
    public $message = null; //API取得時のエラーメッセージなどを格納

    private $access_key_id=""; // Amazonアクセスキー
    private $secret_access_key=""; //Amazonシークレットキー
    private $associate_tracking_id=""; //Amazonアソシエイトタグ

    protected $err_msg = array(
 //表示用のメッセージを格納
        'notfound' => '見つかりませんでした。',
    );

    function __construct(){
        $json = file_get_contents("/home/private_data/shop_data.json"); // jsonファイルの読み込み（非ハードコード化）
        $shop_data = json_decode($json,true); // 連想配列にする。
    
        $this->access_key_id=$shop_data["amazon"]["AccessKey"]; // Amazonアクセスキー
        $this->secret_access_key=$shop_data["amazon"]["SecretKey"]; //Amazonシークレットキー
        $this->associate_tracking_id=$shop_data["amazon"]["TrackingId"]; //Amazonアソシエイトタグ
    }



    // API取得メソッド
    public function get_response() {
        $this->get_new_response_from_amazon();
        return $this->response;
    }


    // Amazon API(PAAPI5) 新規レスポンス取得
    private function get_new_response_from_amazon() {
        $serviceName = "ProductAdvertisingAPI";
        $region = "us-west-2";
        $accessKey = $this->access_key_id;
        $secretKey = $this->secret_access_key;
        $associate_tracking_id = $this->associate_tracking_id;

        $this->message = null; //メッセージを空に

        // キーワード検索用Payload取得
        $payload = get_paapi_payload_searchitems($associate_tracking_id, $this->kw);
        $host = "webservices.amazon.co.jp";
        $uriPath="/paapi5/searchitems";

        $awsv4 = new AwsV4($accessKey, $secretKey);
        $awsv4->setRegionName($region);
        $awsv4->setServiceName($serviceName);
        $awsv4->setPath($uriPath);
        $awsv4->setPayload($payload);
        $awsv4->setRequestMethod("POST");
        $awsv4->addHeader('content-encoding', 'amz-1.0');
        $awsv4->addHeader('content-type', 'application/json; charset=utf-8');
        $awsv4->addHeader('host', $host);

        $awsv4->addHeader ('x-amz-target', 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.SearchItems');
        $headers = $awsv4->getHeaders();
        $headerString = "";
        foreach ($headers as $key => $value) {
            $headerString .= $key.':'.$value."\r\n";
        }

        $params = array(
            'http' => array(
                'header' => $headerString,
                'method' => 'POST',
                'content' => $payload,
            )
        );

        $stream = stream_context_create($params);

        $fp = @fopen('https://'.$host.$uriPath, 'rb', false, $stream);

        if (!$fp) { //ERROR：fopen
            echo 'URL: https://'.$host.$uriPath.'<br>';
            $this->error('fopenに失敗しました');
            return false;
        }

        // レスポンス取得
        $response = @stream_get_contents($fp);

        if($this->check_response_error($response)) { //エラーチェック
            // レスポンスをプロパティに格納して終了
            $this->response = $response;
            return true;
        }

        // 以下はエラー時のフロー。とりあえず表向きには見つかりませんでしたのエラーを返す。
        $this->response = null;
        $this->message = $this->err_msg['notfound'];
        return false;
    }


    // レスポンスのエラーチェック
    private function check_response_error($response = null) {
        switch ($this->service) {
            case 'amazon':
                return $this->check_response_error_for_amazon($response);
                break;
            default:
                return $this->check_response_error_for_amazon($response);
                break;
        }
    }


    // レスポンスのエラーチェック。エラーがあればerrorプロパティに格納
    private function check_response_error_for_amazon($response = null) {
        //ERROR：stream_get_contents()
        if ($response === false) {
            $this->error('stream_get_contents()に失敗');
            return false;
        }
        //ERROR：503
        if (includes_string($response, 'Website Temporarily Unavailable')) {
            $this->error('503エラー');
            return false;
        }
        //jsonにエラーがあるかを確認する
        $json = json_decode($response);
        if (property_exists($json, 'Errors')) { //json内にerrorが含まれる場合
            $json_error_code    = $json->{'Errors'}[0]->{'Code'};
            $json_error_message = $json->{'Errors'}[0]->{'Message'};
            $this->error('APIエラー：'.$json_error_code.' / '.$json_error_message);
            return false;
        }
        //ERROR：No SeaechResult
        if (!property_exists($json, 'SearchResult')) {
            $this->error('SearchResultが存在しません');
            return false;
        }
        return true;
    }

    private function error($msg = '') {
        // APIエラー発生時のメッセージが$msgで送られてくるので必要に応じてログ記録などの処理を行ってください。
        echo $msg;
    }
}
endif;
?>