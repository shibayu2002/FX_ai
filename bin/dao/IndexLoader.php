<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

class IndexLoader {
  public function loadTermAll($baseDate, $termDay) {
    $curDate = new DateTime(substr($baseDate, 0, 4) . "-" .  substr($baseDate, 4, 2) . "-" .  substr($baseDate, 6, 2));
    $rows = array();
    for ($i = 1; $i <= $termDay; $i++) {
      echo $curDate->format('Ymd') . "\n";

      $rows = array_merge($rows, $this -> loadByYJFX($curDate->format('Ymd')));
      $curDate->modify('-1 week');
    }
    print var_dump($rows);
    return $rows;
  }

  private function loadByYJFX($baseDate) {
    $URL = sprintf(
            "https://www.yjfx.jp/gaikaex/mark/calendar/%s", 
             $baseDate);
    print $URL . "\n";
    // search company xbrl in edinet
    $htmlfile = file_get_contents("$URL", false, $this -> makeHttpHeaderContext());
    $htmlfile = mb_convert_encoding($htmlfile, "utf8", "auto");
    $htmlfile = explode("\n", $htmlfile);

    $rows = array();

    foreach ( $htmlfile as $text ) {
      if ( strpos($text, sprintf('<tr id="%s', $baseDate)) ){
        $list = explode("<tr id=", $text);

        foreach ( $list as $str ) {
          if ( strpos($str, "*") ) {
            continue;
          }
        
          $cols = array();

          $targetDate = $this->regMatch($str, '/^"([0-9]{8})/', 1);
          if ($targetDate != null) {

            $cols["base_date"] = $targetDate;
            $cols["base_month"] = substr($targetDate, 0, 6);
            $cols["cuntry"] = trim($this->regMatch($str, '/title="(.*?)"/', 1));
            $cols["title"] = $this->regMatch($str, '/<td class="title_td.*?">(.*?)<\/td>/', 1);
            $cols["title"] = preg_replace("/[0-9]+月/", "", $cols["title"]);
            $cols["title"] = preg_replace("/[0-9]+\-期/", "", $cols["title"]);
            $cols["title"] = trim(preg_replace("/[0-9]+年/", "", $cols["title"]));
            $cols["title"] = trim(preg_replace("/[0-9]+\-/", "", $cols["title"]));
            $cols["title"] = trim(preg_replace("/[0-9]+.*?四半期/", "四半期", $cols["title"]));
            
            $cols["value"] = trim($this->regMatch($str, '/★.*<td>.*?<\/td><td>(.*?)<\/td>/', 1));
            $cols["value"] = $this->regMatch($cols["value"], '/[+-]?[0-9]+[.]?[0-9]/');

            $rows[] = $cols;
          }
        }
      }
    }
    return $rows;
  }

  private function regMatch($text, $pattern, $group = 0) {
    preg_match($pattern, $text, $ret);
    if (count($ret) > 0) {
      return $ret[$group];
    } else {
      return null;
    }
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
