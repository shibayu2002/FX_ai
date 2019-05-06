<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

class Target {
  private $targets;
  
  public function getEDINETCodeList () {
    if ( $this -> targets != null) {
      return $this -> targets;
    }
  
    global $argc, $argv;

    print "load target EDINET code." . "\n";
    
    $con = DBCon::getInstance();
    if ( $argc > 1 ) {
      if ($argv[1] == "erroronly") {
        $sql = "select EDINETCODE from company_info where SECURITIES_CODE <> '' ";
        $sql = $sql . "and EDINETCODE IN (select code from batch_status where BATCH_ID = 'IR_info' and status = 'E');";
      } else {
        $sql = "select EDINETCODE from company_info where EDINETCODE = '" . $argv[1] . "' and SECURITIES_CODE <> '';";
      }
    } else {
      $sql = "select EDINETCODE from company_info where SECURITIES_CODE <> '';";
    }
    print $sql;

    $array = array();
    foreach ($con -> query($sql) as $row) {
      $array[] = $row['EDINETCODE'];
    }
    if ( count($array) == 0) {
      throw new Exception("not found EDINET code!!");
    }
    var_dump($array);
    $this -> targets = $array;
    return $this -> targets;
  }
}
?>