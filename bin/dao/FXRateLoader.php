<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

class FXRateLoader {
  const XBRL_DIR="/data/xbrl/";

  public static function load($code) {

    $headers = array(
      'User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
    );
    $extra_headers = array();

    $options1 = array(
      'http' => array(
        'method' => 'GET',
        'header' => 'User-Agent:Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
      ),
      'ssl' => array(
      'verify_peer' => false,
      'verify_peer_name' => false,
      ),
    );
    $context = stream_context_create($options1);
    $URL = "https://info.finance.yahoo.co.jp/fx/detail/?code=" . $code;

    // search company xbrl in edinet
    $htmlfile = file_get_contents("$URL", false, $context);
    $htmlfile = mb_convert_encoding($htmlfile, "utf8", "auto");
    $htmlfile = explode("\n", $htmlfile);

    $res = array();
    $res["base_date"] = date("Ymd");
    $res["code"] = mb_strtoupper($code);
    $res["volume"] = null;
    $res["day_before_diff"] = null;
    $res["day_before_ratio"] = null;
    
    foreach ( $htmlfile as $htmlfile ) {
      if ( strpos($htmlfile, 'USDJPY_detail_bid') ){
        $res['closing_price'] = preg_replace('/[^0-9\.]/', '', $htmlfile);
      }
      if ( strpos($htmlfile, 'USDJPY_detail_open') ){
        $res['opening_price'] = preg_replace('/[^0-9\.]/', '', $htmlfile);
      }
      if ( strpos($htmlfile, 'USDJPY_detail_high') ){
        $res['high_price'] = preg_replace('/[^0-9\.]/', '', $htmlfile);
      }
      if ( strpos($htmlfile, 'USDJPY_detail_low') ){
        $res['low_price'] = preg_replace('/[^0-9\.]/', '', $htmlfile);
      }
    }
    
    print var_dump($res);
    
    return $res;
  }
}
?>
