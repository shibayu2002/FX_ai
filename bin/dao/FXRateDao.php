<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

class FXRateDao {
  public function reflesh ($code, $baseDate, $termMonth) {
    $curMonth = new DateTime(substr($baseDate, 0, 4) . "-" .  substr($baseDate, 4, 2) . "-01");
    $rows = array();
    for ($i = 1; $i <= $termMonth; $i++) {
      $this -> delete($code, $curMonth->format('Y') . $curMonth->format('m') . "01",
                             $curMonth->format('Y') . $curMonth->format('m') . "31");
      $curMonth->modify('-1 months');
    }
  }

  private function delete($code, $monthS, $monthE) {
    $con = DBCon::getInstance();
    $sql = sprintf("delete from FXRATE where code='%s' and base_date >= '%s' and base_date <= '%s'", 
             $code, $monthS, $monthE);
    $con -> query($sql);
  }
  
  public function save ($datas) {
    $con = DBCon::getInstance();
    $sql = sprintf("delete from FXRATE where base_date = '%s' and code = '%s'",
                  $datas['base_date'], $datas['code']);
    $con->query($sql);

    $sql = sprintf("insert into  FXRATE (base_date, code, closing_price, opening_price, high_price, low_price, volume) values
                  ('%s', '%s', %.4f, %.4f, %.4f, %.4f, %.4f)",
                  $datas['base_date'], $datas['code'], 
                  $this->nullToStr($datas['closing_price']), 
                  $this->nullToStr($datas['opening_price']), 
                  $this->nullToStr($datas['high_price']),
                  $this->nullToStr($datas['low_price']), 
                  $this->nullToStr($datas['volume']));
    $con->query($sql);
  }

  public function select ($base_date, $code, $limit) {
    $con = DBCon::getInstance();
    
    $sql = sprintf("select * from FXRATE where base_date <= '%s' and code = '%s' order by base_date desc limit %s",
             $base_date, $code, $limit);
    $cur = $con->query($sql);
    
    $rows = array();
    while($data = $cur->fetch(PDO::FETCH_ASSOC)) {
      $cols = array();

      $cols["base_date"] = $data['base_date'];
      $cols["code"] = $data['code'];
      $cols['closing_price'] = $data['closing_price'];
      $cols['opening_price'] = $data['opening_price'];
      $cols['high_price'] = $data['high_price'];
      $cols['low_price'] = $data['low_price'];
      $cols["volume"] = $data['volume'];
      
      $rows[] = $cols;
    }
    return $rows;
  }

  public function selectMonth ($base_date, $code, $limit) {
    $con = DBCon::getInstance();
    
    $sql = sprintf("select base_month, code, avg(closing_price) as closing_price, max(high_price) as high_price, min(low_price) as low_price from " .
              "  (select base_date, substring(base_date, 1, 6) as base_month, code, closing_price, high_price, low_price " .
              "    from FXRATE where base_date <= '%s') A  " .
              " where A.code = '%s'  " .
              " group by A.base_month, A.code  " .
              " order by A.base_month desc limit %s",
             $base_date, $code, $limit);
    $cur = $con->query($sql);
    
    $rows = array();
    while($data = $cur->fetch(PDO::FETCH_ASSOC)) {
      $cols = array();

      $cols["base_date"] = $data['base_date'];
      $cols["base_month"] = $data['base_month'];
      $cols["code"] = $data['code'];
      $cols['closing_price'] = $data['closing_price'];
      $cols['high_price'] = $data['high_price'];
      $cols['low_price'] = $data['low_price'];
      
      $rows[] = $cols;
    }
    return $rows;
  }
  
  public function selectMostNear ($keys) {
    $con = DBCon::getInstance();
    
    $sql = sprintf("select * from FXRATE where base_date = (select max(base_date) from FXRATE where base_date < '%s' and code = '%s') and code = '%s'",
           $keys['base_date'], $keys['code'], $keys['code']);
    $cur = $con->query($sql);
    
    $res = array();
    while($data = $cur->fetch(PDO::FETCH_ASSOC)) {
      $res["base_date"] = $data['base_date'];
      $res["code"] = $data['code'];

      $res['closing_price'] = $data['closing_price'];
      $res['opening_price'] = $data['opening_price'];
      $res['high_price'] = $data['high_price'];
      $res['low_price'] = $data['low_price'];
        
      $res["volume"] = $data['volume'];
    }
    return $res;
  }
  
  
  private function nullToStr($value) {
    if($value == null){
      return 'null';
    }
    return $value;
  }
}

?>
