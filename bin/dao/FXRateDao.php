<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

class FXRateDao {
  public function save ($datas) {
    $con = DBCon::getInstance();

    $sql = "delete from FXRATE
         where base_date = '" . $datas['base_date'] . "' and code = '" . $datas['code'] . "'";
    print $sql . "\n";
    $con->query($sql);

    $sql = "insert into FXRATE
    (base_date,
     code, 
     closing_price,
     opening_price, 
     high_price, 
     low_price, 
     volume,
     day_before_diff, 
     day_before_ratio
    )
    values('" . $datas['base_date'] . "', " .
          "'" . $datas['code'] . "', " . 
          "" . $this->nullToStr($datas['closing_price']) . ", " . 
          "" . $this->nullToStr($datas['opening_price']) . ", " . 
          "" . $this->nullToStr($datas['high_price']) . ", " . 
          "" . $this->nullToStr($datas['low_price']) . ", " . 
          "" . $this->nullToStr($datas['volume']) . ", " . 
          "" . $this->nullToStr($datas['day_before_diff']) . ", " . 
          "" . $this->nullToStr($datas['day_before_ratio']) . "" . 
    ")";

    print $sql . "\n";
    $con->query($sql);
  }
  public function select ($base_date, $code, $limit) {
    $con = DBCon::getInstance();
    
    $sql = "select * from FXRATE 
         where base_date <= '" . $base_date . "' and code = '" . $code . "' order by base_date desc limit " . $limit . "";
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
      $cols["day_before_diff"] = $data['day_before_diff'];
      $cols["day_before_ratio"] = $data['day_before_ratio'];
      
      $rows[] = $cols;
    }
    
//    print var_dump($rows);
    
    $con->query($sql);

    return $rows;
  }
  
  public function selectMostNear ($keys) {
    $con = DBCon::getInstance();
    
    $sql = "select * from FXRATE 
         where base_date = (
             select max(base_date) from FXRATE 
             where base_date < '" . $keys['base_date'] . "' and code = '" . $keys['code'] . "') 
           and code = '" . $keys['code'] . "'";
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
      $res["day_before_diff"] = $data['day_before_diff'];
      $res["day_before_ratio"] = $data['day_before_ratio'];
    }
    
//    print var_dump($res);
    
    $con->query($sql);

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
