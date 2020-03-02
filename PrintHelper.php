<?php

class PrintHelper {
  public $app_name;
  function __construct($app_name){
    $this->app_name = $app_name;
  }
  public function print_app_name() {
    $count = 1;
    for ($i = 0; $i < 30 ; $i++) { 
      echo "-";
        if ($i == 29 && $count < 2) {
          echo "\n";
          echo " ".$this->app_name;
          echo "\n";
          $i = -1;
          $count++;
        }
    }
  }
  public function print_status_heading($heading) {
    echo "\n\n > ".$heading;
  }
  public function print_status($status) {
    echo "\nSTATUS: ".$status;
  }
  public function print_error($error) {
    echo "\nERROR: ".$error;
  }
}

?>