<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

class DBCon {
  const MYSQL_USER="xxxxxxx";
  const MYSQL_PASS="xxxxxxx";
  const MYSQL_PDO_SOCK = "mysql:host=localhost;dbname=FXAI;charset=utf8";

  private static $instance = null;
  private $con = null;
  
  public static function getInstance() {
    if (self::$instance == null) {
      self::$instance = new DBCon;
    }
    return self::$instance;
  }
  
  public function __construct() {
    self::open();
  }

  public function open() {
    if ($this -> con == null) {
      $this -> con = new PDO(self::MYSQL_PDO_SOCK, self::MYSQL_USER, self::MYSQL_PASS);
      $this -> con -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
  }
  
  public function query($sql) {
    return $this -> con -> query($sql);
  }

  public function close() {
    if ($this -> con != null) {
      $this -> con = null;
    }
  }
}
?>