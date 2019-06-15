<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

class ClassificationDataLstmAI extends BaseAI2 {

  public function __construct() { 
    parent::__construct(get_class($this));
  }

  protected function createDataset($code, $batchDate, $testCnt, $XCnt) {
    $dao = new FXRateDao();
    $targets = $dao->select($batchDate, $code, $testCnt + 3);
    $nextClosePrice = null;
    $nextBaseDate = null;
    $tranCsv = array();
    $tranCsv[] = "d:date,x:accel,y__0,y__1,y__2,y__3,y__4\n";
    
    $bTest = true;

    foreach ($targets as $target) {
      $baseDate = $target['base_date'];
      echo $baseDate . "\n";
      if ($baseDate <= $batchDate) {
        $this -> makeSubDataset($code, $baseDate, $XCnt);
      }
    }

    foreach ($targets as $target) {
      $baseDate = $target['base_date'];
      $closingPrice = $target['closing_price'];
      if ($nextClosePrice != null) {
        if ($batchDate == $nextBaseDate) {
        } else {
          $expectDiff = sprintf("%.2f", $nextClosePrice - $closingPrice);
          print "baseDate=" . $nextBaseDate . ", nextClosePrice=" . $nextClosePrice . ", closingPrice=" . $closingPrice . ", expectDiff=" . $expectDiff . "\n";
          
          if ($bTest) {
            $testCsv[] = "d:date,x:accel,y__0,y__1,y__2,y__3,y__4\n";
            $testCsv[] = $nextBaseDate . ",./subdataset/" . $baseDate . ".csv," . $this -> classificationResult($expectDiff) . "\n";
            file_put_contents(parent::getWorkspace() . "/dataset/test.csv", $testCsv);
            $bTest = false;
          } else {
            $tranCsv[] = $nextBaseDate . ",./subdataset/" . $baseDate . ".csv," . $this -> classificationResult($expectDiff) . "\n";
          }
        }
      } else {
        $execCsv[] = "d:date,x:accel,y__0,y__1,y__2,y__3,y__4\n";
        $execCsv[] = $batchDate . ",./subdataset/" . $baseDate . ".csv,0,0,0,0,0,0,0\n";
        file_put_contents(parent::getWorkspace() . "/dataset/exec.csv", $execCsv);
      }
      $nextBaseDate = $baseDate;
      $nextClosePrice = $closingPrice;
    }

    file_put_contents(parent::getWorkspace() . "/dataset/tran.csv", $tranCsv);
  }

  protected function isTransferLearning() {
    return true;
  }
  
  private function makeSubDataset($code, $argBaseDate, $XCnt) {
    $dao = new FXRateDao();
    $targets = $dao->select($argBaseDate, $code, $XCnt + 1);
      $nextClosePrice = null;
      $nextHighPrice = null;
      $nextLowPrice = null;
      $subDatasetCsv = array();
      
      foreach ($targets as $target) {
        $closingPrice = $target['closing_price'];
        $highPrice = $target['high_price'];
        $lowPrice = $target['low_price'];
        if ($nextClosePrice != null) {
            $expectCloseDiff = sprintf("%.2f", $nextClosePrice - $closingPrice);
            $expectHighDiff = sprintf("%.2f", $nextHighPrice - $closingPrice);
            $expectLowDiff = sprintf("%.2f", $nextLowPrice - $closingPrice);
            $expectHLDiff = sprintf("%.2f", $nextHighPrice - $nextLowPrice);
            
            $dataLine = sprintf("%s,%s,%s,%s\n", 
              $this -> classification($expectCloseDiff), 
              $this -> classification($expectHighDiff), 
              $this -> classification($expectLowDiff),
              $this -> classification($expectHLDiff));
            array_unshift($subDatasetCsv, $dataLine);
        }
        $nextClosePrice = $closingPrice;
        $nextHighPrice = $highPrice;
        $nextLowPrice = $lowPrice;
      }
      file_put_contents(parent::getWorkspace() . "/dataset/subdataset/" . $argBaseDate . ".csv", $subDatasetCsv);
  }

  private function classification($priceDiff) {
    if ($priceDiff >= 0.5) {
      return 1;
    } else if ($priceDiff >= 0.2) {
      return 0.75;
    } else if ($priceDiff > -0.2) {
      return 0.5;
    } else if ($priceDiff > -5) {
      return 0.25;
    } else {
      return 0;
    }
  }

  private function classificationResult($priceDiff) {
    if ($priceDiff > 0.5) {
      return "0,0,0,0,1";
    } else if ($priceDiff > 0.2) {
      return "0,0,0,1,0";
    } else if ($priceDiff > -0.2) {
      return "0,0,1,0,0";
    } else if ($priceDiff > -0.5) {
      return "0,1,0,0,0";
    } else {
      return "1,0,0,0,0";
    }
  }
}

?>
