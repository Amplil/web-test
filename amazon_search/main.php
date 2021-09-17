<?php
require_once 'class.PAAPISearch.php'; //PAAPI検索クラス
require_once 'class.AwsV4.php'; //AwsV4クラス
require_once 'paapi-payload-searchitems.php'; //PAAPI検索ペイロード

function get_amazon_search_html($kw = '') {
    $search = new PAAPISearch;
    $search->kw = $kw; //検索キーワードをセット
    $search->get_response(); // レスポンスを取得
    $html = '';

    //レスポンスが取得できていた場合
    if(!empty($search->response)) {
        $paapi_response = json_decode($search->response); // オブジェクト形式にパース
        foreach ($paapi_response->SearchResult->Items as $item) {
            $html = '<dl>';
            $html .= '<dt>商品名</dt><dd><a href="'.$item->DetailPageURL.'">'.@$item->ItemInfo->Title->DisplayValue.'</a></dd>';
            $html .= '<dt>ASIN</dt><dd>'.@$item->ASIN.'</dd>';
            $html .= '<dt>JANコード</dt><dd>'.@$item->ItemInfo->ExternalIds->EANs->DisplayValues[0].'</dd>';
            $html .= '<dt>ISBNコード</dt><dd>'.@$item->ItemInfo->ExternalIds->ISBNs->DisplayValues[0].'</dd>';
            $html .= '<dt>価格</dt><dd>'.@$item->Offers->Listings[0]->Price->DisplayAmount.'</dd>';
            $html .= '</dl>';
        }
    }
    if(!empty($search->message)) { //メッセージが格納されていれば表示
        $html .= '<p>'.$search->message.'</p>';
    }
    return $html;
}

// 出力用のHTMLは適当に整形してください。
?>
<html>
<body>
<?php

echo get_amazon_search_html('USBケーブル'); //引数にキーワードを渡すとAmazonでの検索結果が返ります。

?>
</body>
</html>