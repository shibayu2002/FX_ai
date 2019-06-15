<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

class FXResultDao {
  public function save ($datas) {
    $con = DBCon::getInstance();

    $sql = sprintf("delete from RESULT where model = '%s' and base_date = '%s' and code = '%s'",
                  $datas['model'], $datas['base_date'], $datas['code']);
    $con->query($sql);

    $sql = sprintf("insert into RESULT (model, base_date, code, tran_error, expected, actual, hit, memo) values
                  ('%s', '%s', '%s', %.5f, %.4f, %.4f, %s, %s)",
                  $datas['model'], $datas['base_date'], $datas['code'], 
                  $this->nullToStr($datas['tran_error']), 
                  $this->nullToStr($datas['expected']), 
                  $this->nullToStr($datas['actual']),
                  $this->nullToStr($datas['hit']), 
                  $this->nullToStr($datas['memo']));
    $con->query($sql);
  }

  public function select ($model, $code, $base_month) {
    $con = DBCon::getInstance();
    
    $sql = sprintf("select * from RESULT where model = '%s' and base_date like '%s%%' and code = '%s' order by base_date desc",
                 $model, $base_month, $code);
    $cur = $con->query($sql);
    
    $rows = array();
    while($data = $cur->fetch(PDO::FETCH_ASSOC)) {
      $cols = array();

      $cols["model"] = $data['model'];
      $cols["code"] = $data['code'];
      $cols["base_date"] = $data['base_date'];
      $cols['tran_error'] = $data['tran_error'];
      $cols['expected'] = $data['expected'];
      $cols['actual'] = $data['actual'];
      $cols['hit'] = $data['hit'];
      $cols["memo"] = $data['memo'];
      
      $rows[] = $cols;
    }
    return $rows;
  }

  public function update ($datas) {
    $con = DBCon::getInstance();
    
    $sql = sprintf("update RESULT set actual = %.4f where model = '%s' and base_date = '%s' and code = '%s'",
                 $datas["actual"], $datas["model"], $datas["base_date"], $datas["code"]);
    $cur = $con->query($sql);
  }
  
  private function nullToStr($value) {
    if($value == null){
      return 'null';
    }
    return $value;
  }
}

?>
