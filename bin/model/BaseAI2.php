<?php
// Copyright(c)2017-2017 Syuu All Rights Reserved.

abstract class BaseAI2 {
  private $model;
  private $workspace;
  
  public function __construct($model) { 
    $this -> model = $model;
  } 

  public function analyze ($code, $batchDate, $testCnt, $XCnt, $prevDate) {
    echo "[satart]analyze " . $this -> model . ", " . $code . ", " . $batchDate . "\n";

    $this -> workspace = "/home/apl/batch/FX_ai/ai/workspace/" . $this -> model;
    echo $this -> workspace;

    $this -> initWorkspace();
    $this -> createDataset($code, $batchDate, $testCnt, $XCnt);
    $this -> learn($prevDate);
    $this -> createParamFile($batchDate);
    $this -> inference();
    $this -> showLearnResult($code, $batchDate);
    
    echo "[end]analyze " . $this -> model . ", " . $code . ", " . $batchDate . "\n";
  }

  private function initWorkspace() {
    system("rm -rf " . $this -> workspace);
    mkdir($this -> workspace);
    mkdir($this -> workspace . "/dataset");
    mkdir($this -> workspace . "/dataset/subdataset");
  }

  protected function getWorkspace() {
    return $this -> workspace;
  }

  protected function normalization($expectDeff) {
    $expectDeff = $expectDeff / 4 + 0.5;
    if ($expectDeff <= 0) {
      return 0;
    } else if ($expectDeff >= 1) {
      return 1;
    } else {
      return $expectDeff;
    }
  }

  abstract protected function createDataset($code, $batchDate, $testCnt, $XCnt);
  
  private function learn($prevDate) {
    $cmd = sprintf("python %s train -c %s -o %s",
            "/usr/local/python/lib/python3.6/site-packages/nnabla/utils/cli/cli.py",
            "/home/apl/batch/FX_ai/ai/". $this -> model . "/net.nntxt",
            $this -> workspace);
    if ($this -> isTransferLearning()) {
      $cmd = sprintf($cmd . " -p %s", 
            "/home/apl/batch/FX_ai/ai/". $this -> model . "/initial_parameters." . $prevDate .".h5");
    }
    echo $cmd . "\n";
    system($cmd);
  }

  abstract protected function isTransferLearning();
  
  private function createParamFile($batchDate) {
    if ($this -> isTransferLearning()) {
      system("mkdir " . $this -> workspace . "/initial_parameters");
      $cmd = sprintf("python %s decode_param -p %s -o %s",
              "/usr/local/python/lib/python3.6/site-packages/nnabla/utils/cli/cli.py",
              $this -> workspace . "/results.nnp",
              $this -> workspace . "/initial_parameters");
      echo $cmd . "\n";
      system($cmd);

      $cmd = sprintf("python %s encode_param -i %s -p %s",
              "/usr/local/python/lib/python3.6/site-packages/nnabla/utils/cli/cli.py",
              $this -> workspace . "/initial_parameters",
              $this -> workspace . "/initial_parameters.h5");
      echo $cmd . "\n";
      system($cmd);
      
      $cmd = sprintf("cp -p %s %s",
              $this -> workspace . "/initial_parameters.h5",
              "/home/apl/batch/FX_ai/ai/". $this -> model . "/initial_parameters." . $batchDate .".h5");
      echo $cmd . "\n";
      system($cmd);
    }
  }
  
  private function inference() {
    $cmd = sprintf("python %s forward -c %s -d %s -o %s",
            "/usr/local/python/lib/python3.6/site-packages/nnabla/utils/cli/cli.py",
            $this -> workspace . "/results.nnp",
            $this -> workspace . "/dataset/exec.csv",
            $this -> workspace);
    system($cmd);
  }

  private function showLearnResult($code, $batchDate) {
    $result[] = "Exec time:" . date("Y/m/d H:i:s") ."\n\n";
    $result[] = "BaseDate:" . $batchDate ."\n\n";
    
    $cost = "";
    $train_error = "";
    $valid_error = "";

    $fp = fopen($this -> workspace . "/monitoring_report.yml", "r");
    if($fp == false){
      return;
    }
    while (!feof($fp)) {
      $txt = fgets($fp);

      if ( strpos($txt, 'cost:') ){
        $cost = trim ($txt);
      }
      if ( strpos($txt, 'train_error:') ){
        $train_error = trim ($txt);
      }
      if ( strpos($txt, 'valid_error:') ){
        $valid_error = trim ($txt);
      }
    }
    fclose($fp);

    $result[] = $cost ."\n";
    $result[] = $train_error ."\n";
    $result[] = $valid_error ."\n";

    $cnt = 1;
    $fp = fopen($this -> workspace . "/output_result.csv", "r");  
    while (!feof($fp)) {
      $txt = fgets($fp);

      if ( $cnt == 2 ){
        $values = explode(",", $txt);
        $expectPrice = sprintf("%.2f", trim($values[3]));
        break;
      }
      
      $cnt = $cnt + 1;
    }
    fclose($fp);
    
    $result[] = "Expect Price Diff: " . $expectPrice . "\n\n";

    $probability = doubleval(preg_replace('/valid_error: /', '', $valid_error));
    $result[] = "Valid error: " . $probability ."\n";
    if ($probability < 0.1) {
      $result[] = "Probability: Good\n\n";
    } else {
      $result[] = "Probability: Not good\n\n";
    }
    
    $price = doubleval($expectPrice);
    if ($price <= -0.15) {
      $result[] = "Trend: Down (price <= -0.15)\n";
    } else if ($price >= 0.15) {
      $result[] = "Trend: Up (0.15 <= price)\n";
    } else {
      $result[] = "Trend: Square (-0.15 < price < 0.15)\n";
    }
    $result[] = "Expect Price Diff: " . $expectPrice ."\n";
    
    file_put_contents("/var/www/html/FX_ai/result.txt", $result);
    
    
    $train_error = doubleval(preg_replace('/.*train_error: /', '', $train_error));
    $resultDatas = array();
    $resultDatas["model"] = $this->model;
    $resultDatas["base_date"] = $batchDate;
    $resultDatas["code"] = $code;
    $resultDatas["tran_error"] = $train_error;
    $resultDatas["expected"] = $expectPrice;
    $resultDatas["actual"] = null;
    $resultDatas["hit"] = null;
    $resultDatas["memo"] = null;
    $dao = new FXResultDao();
    $dao -> save($resultDatas);
  }
}

?>
