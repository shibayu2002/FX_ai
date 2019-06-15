<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

class FXRateLoader {
  const XBRL_DIR="/data/xbrl/";

  public function loadNow($code) {

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
    
    foreach ( $htmlfile as $text ) {
      if ( strpos($text, 'USDJPY_detail_bid') ){
        $res['closing_price'] = preg_replace('/[^0-9\.]/', '', $text);
      }
      if ( strpos($text, 'USDJPY_detail_open') ){
        $res['opening_price'] = preg_replace('/[^0-9\.]/', '', $text);
      }
      if ( strpos($text, 'USDJPY_detail_high') ){
        $res['high_price'] = preg_replace('/[^0-9\.]/', '', $text);
      }
      if ( strpos($text, 'USDJPY_detail_low') ){
        $res['low_price'] = preg_replace('/[^0-9\.]/', '', $text);
      }
    }
    
    print var_dump($res);
    
    return $res;
  }

  public function loadTermAll($code, $baseDate, $termMonth) {
    $curMonth = new DateTime(substr($baseDate, 0, 4) . "-" .  substr($baseDate, 4, 2) . "-01");
    $rows = array();
    for ($i = 1; $i <= $termMonth; $i++) {
      echo $curMonth->format('Ymd') . "\n";

      $rows = array_merge($rows, $this -> loadByYahoo($code, $curMonth->format('Y'), $curMonth->format('m'), "21",
                                 $curMonth->format('Y'), $curMonth->format('m'), "31"));
      $rows = array_merge($rows, $this -> loadByYahoo($code, $curMonth->format('Y'), $curMonth->format('m'), "11",
                                 $curMonth->format('Y'), $curMonth->format('m'), "20"));      
      $rows = array_merge($rows, $this -> loadByYahoo($code, $curMonth->format('Y'), $curMonth->format('m'), "01",
                                 $curMonth->format('Y'), $curMonth->format('m'), "10"));
      $curMonth->modify('-1 months');
    }
    print var_dump($rows);
  }

  private function loadByYahoo($code, $yearS, $monthS, $dayS, $yearE, $monthE, $dayE) {
    $URL = sprintf(
            "https://info.finance.yahoo.co.jp/history/?code=%s&sy=%s&sm=%s&sd=%s&ey=%s&em=%s&ed=%s&tm=d", 
             $code, $yearS, $monthS, $dayS, $yearE, $monthE, $dayE);
    print $URL;
    // search company xbrl in edinet
    $htmlfile = file_get_contents("$URL", false, $this -> makeHttpHeaderContext());
    $htmlfile = mb_convert_encoding($htmlfile, "utf8", "auto");
    $htmlfile = explode("\n", $htmlfile);

    $rows = array();

    foreach ( $htmlfile as $text ) {
      if ( strpos($text, '</td><td>') ){
        $list = explode("</td>", $text);
        $cnt = 1;

        foreach ( $list as $str ) {
          $str = preg_replace('/<td>/', '', $str);
          $str = preg_replace('/<\/tr><tr>/', '', $str);
          if ( strpos($str, '年') ){
            $str = preg_replace('/年/', '/', $str);
            $str = preg_replace('/月/', '/', $str);
            $str = preg_replace('/日/', '', $str);
            $d = strtotime($str);
            $str = date("Ymd", $d);
            $cnt = 1;

            $cols = array();
            $cols["code"] = mb_strtoupper($code);
            $cols["volume"] = null;
            $cols["day_before_diff"] = null;
            $cols["day_before_ratio"] = null;
          }
        
          if ($cnt == 1) {
            $cols["base_date"] = $str;
          } else if ($cnt == 2) {
            $cols["opening_price"] = $str;
          } else if ($cnt == 3) {
            $cols["high_price"] = $str;
          } else if ($cnt == 4) {
            $cols["low_price"] = $str;
          } else if ($cnt == 5) {
            $cols["closing_price"] = $str;
            $rows[] = $cols;
          }
          $cnt = $cnt + 1;
        }
      }
    }
    return $rows;
  }
  
  private function makeHttpHeaderContext() {
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
    return $context;
  }
}

// テスト用
// $loader = new FXRateLoader();
// $loader -> loadTermAll("USDJPY", date("Ymd"), 3);

?>
