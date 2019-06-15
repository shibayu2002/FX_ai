<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

class FXIndexDao {
  public function save ($datas) {
    $con = DBCon::getInstance();
    $sql = sprintf("delete from FXINDEX where base_date = '%s' and cuntry = '%s' and title = '%s'",
                  $datas['base_date'], $datas['cuntry'], $datas['title']);
    $con->query($sql);

    $sql = sprintf("insert into  FXINDEX (base_date, base_month, cuntry, title, value) values
                  ('%s', '%s', '%s', '%s', %.4f)",
                  $datas['base_date'], $datas['base_month'], $datas['cuntry'], $datas['title'],
                  $this->nullToStr($datas['value']));
    $con->query($sql);
  }
  
  public function selectMostNear ($baseDate) {
    $con = DBCon::getInstance();
    
    $sql = sprintf("select cast(max(M.title_no) as unsigned) as title_no, ifnull(max(C.value), 0) as value " .
                   " from (select title_no, title from FXINDEX group by title_no) M  " .
                   " left outer join (select A.title_no, A.title, A.value from FXINDEX A  " .
                   "   inner join (select max(base_date) as base_date, cuntry, title from FXINDEX  " .
                   "   where base_date <= '%s' group by cuntry, title) B   " .
                   "   on A.base_date = B.base_date and A.cuntry = B.cuntry and A.title = B.title) C  " .
                   " on M.title_no = C.title_no  " .
                   " group by M.title  " .
                   " order by title_no asc",
           $baseDate);
    $cur = $con->query($sql);
    
    $rows = array();
    while($data = $cur->fetch(PDO::FETCH_ASSOC)) {
      $cols = array();
      $cols["title_no"] = $data['title_no'];
      $cols['value'] = $data['value'];
      
      $rows[] = $cols;
    }
    return $rows;
  }
  
  public function updateTitleNo () {
    $con = DBCon::getInstance();
    
    $rows = $this->selectAllTitle();
    $cnt = 1;
    foreach ($rows as $target) {
      $sql = sprintf("update FXINDEX set title_no = '%s' " .
             " where cuntry = '%s' and title = '%s' ",
           $cnt, $target['cuntry'], $target['title']);
      $cur = $con->query($sql);
      $cnt = $cnt + 1;
    }
  }
  
  public function selectAllTitle() {
    $con = DBCon::getInstance();
    
    $sql = "select distinct cuntry, title from FXINDEX group by title order by cuntry asc, title asc";
    $cur = $con->query($sql);
    
    $rows = array();
    while($data = $cur->fetch(PDO::FETCH_ASSOC)) {
      $cols = array();
      $cols['cuntry'] = $data['cuntry'];
      $cols['title'] = $data['title'];
      
      $rows[] = $cols;
    }
    return $rows;
  }

  public function selectAllTitleNo() {
    $con = DBCon::getInstance();
    
    $sql = "select distinct cast(title_no as unsigned) as title_no from FXINDEX order by title_no asc";
    $cur = $con->query($sql);
    
    $rows = array();
    while($data = $cur->fetch(PDO::FETCH_ASSOC)) {
      $cols = array();
      $cols['title_no'] = $data['title_no'];
      
      $rows[] = $cols;
    }
    return $rows;
  }
  
  private function nullToStr($value) {
    if($value == null){
      return 'null';
    }
    return $value;
  }
}

?>
