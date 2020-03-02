<?php
  include(dirname(__DIR__).'/helpers/PrintHelper.php');

  class AutoQuotes {
    private $app_version = '1.20.2050.1312';
    public $app_name = "AutoQuotes Automation PHP";
    public $config;
    public $output_directory_path;
    public $printhelper;
    public $login_url;
    public $session_id;
    public $search_url;
    public $config_url;
    function __construct($config,$output_directory_path) {
      $this->printhelper = new PrintHelper($this->app_name);
      $this->config = $config;
      $this->output_directory_path = $output_directory_path;
    }
    private function check_output_folder_exists() {
      if (is_dir($this->output_directory_path)) {
        $this->printhelper->print_status('Directory "data" exists');
      }else {
        mkdir($this->output_directory_path);
        $this->printhelper->print_status('Directory "data" created');
      }
    }
    private function retrieve_json_data() {
      $this->config = json_decode($this->config);
    }
    private function set_login_url($username,$password) {
      $this->login_url = 'https://nl.aq360.com/registration/Login?user='.$username.'&password='.$password.'&clientType=AQ8&clientId=e64d86c3-5c4e-4d34-94de-f135d71ebc18&clientVersion='.$this->app_version.'&clientCulture=en-US&clientDetails=OS%3DWin32NT%2COSVersion%3D6.2.9200.0%2CRuntimeVersion%3D4.0.30319.42000%2CNet45Version%3D4.6.2%20or%20later%2CClientVersion%3D'.$this->app_version;
    }
    private function set_search_url() {
      $this->search_url = 'https://nl.aq360.com/catalogNew/AzureCatSearch?sessionId='.$this->session_id;
    }
    private function set_config_url($product_id) {
      $this->config_url = 'https://nl.aq360.com/config/GetConfig?sessionId='.$this->session_id.'&prodPkey='.$product_id;
    }
    public function initliazer() {
      $this->printhelper->print_app_name();
      $this->printhelper->print_status_heading("INITIALIZATION");
      $this->printhelper->print_status('Checking "data" directory');
      $this->check_output_folder_exists();
      $this->printhelper->print_status('Reading credentials from configuration file');
      $this->retrieve_json_data();
      $this->set_login_url($this->config->credentials->email,$this->config->credentials->password);
      echo"\n";
    }
    public function get_manufacturer_input() {
      $manufacturer_name = '';
      $this->printhelper->print_status_heading("INPUT");
      $this->printhelper->print_status("Taking manufacturer input");
      $this->printhelper->print_status("Enter manufacturer name:");
      while ($manufacturer_name == '') {
        $manufacturer_name = fgets(STDIN);
      }
      return $manufacturer_name;
    }
    public function login() {
      $this->printhelper->print_status_heading("LOGIN");
      $this->printhelper->print_status("Performing login");
      $null_session = '00000000-0000-0000-0000-000000000000';
      $ch = curl_init();
      $headers = array(
      'Cache-Control: no-cache',
      'Pragma: no-cache',
      'Host: nl.aq360.com',
      );
      curl_setopt($ch, CURLOPT_URL, $this->login_url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_HEADER, 0);

      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $request = curl_exec($ch);
      $response = json_decode($request);
      if (!isset($response->SessionId)) {
        $this->printhelper->print_error('Login failed');
      } else {
        if ($response->SessionId == $null_session) {
          $this->printhelper->print_error('AutoQuotes app has been updated. Modifications are required.');
        } else {
          $this->session_id = $response->SessionId;
          $this->printhelper->print_status('Login successful');
        }
      }
    }
    public function search_products($search_term) {
      $this->printhelper->print_status_heading("PRODUCTS SEARCH");
      $form_data = array(
        'SearchString'=> $search_term,
        'Skip'=> 0,
        'Top'=> 1000000,
        'SID'=> 'f69c02fb-ba5b-453e-8c63-452e6f25526b',
        'Filters'=> [],
        'MySort'=> 0,
        'CatalogFilter'=> 0,
        'RevitOnly'=> False,
        'ThreeDimensionOnly'=> False,
        'CADOnly'=> False,
        'SuperCategoryLink'=> '00000000-0000-0000-0000-000000000000',
        'InvColorId'=> 0,
        'SearchFields'=> 2,
        'Pricing'=> '',
        'Warehouse'=> NULL,
        'VendorStatus'=> False,
        'UserDataOnly'=> False,
        'AQDataOnly'=> False,
        'DefaultWHOnly'=> False,
        'currentCatalogFunction'=> 4,
        'IsDefaultSearch'=> False
      );
      $this->printhelper->print_status("Searching products for ".$search_term);
      $headers = array(
        'Cache-Control: no-cache',
        'Pragma: no-cache',
        'Host: nl.aq360.com',
        'Accept: application/json, text/javascript, /; q=0.01',
        'Content-Type: application/json',
      );
      $this->set_search_url();
      $form_data_json = json_encode($form_data);
      $ch = curl_init($this->search_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLINFO_HEADER_OUT, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $form_data_json);
      curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
      $result = curl_exec($ch);
      $response = json_decode($result);
      $products = array();
      foreach($response->Rows as $response_row) {
        if($response_row->HasConfig == 'YES') {
          $products[] = $response_row;
        }
      }
      $this->printhelper->print_status(sizeof($products)." products found \n");
      if (sizeof($products) > 0) {
        foreach($products as $product) {
          $product_id = $product->ProdPkey;
          $product_model = $product->Model;
          $this->printhelper->print_status("Extracting config data for ".$product_model);
          $this->get_config($product_id,$search_term);
        }
      }
    }
    public function get_config($product_id,$search_term) {
      $this->set_config_url($product_id);
      $ch = curl_init();
      $headers = array(
        'Cache-Control: no-cache',
        'Pragma: no-cache',
        'Host: nl.aq360.com',
      );
      curl_setopt($ch, CURLOPT_URL, $this->config_url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_HEADER, 0);

      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $request = curl_exec($ch);
      $response = json_decode($request);
      $base_model = $response->BaseModel;
      $xml = str_replace('encoding="utf-16"?>','encoding="utf-8"?>',$response->ConfigXml);
      $parent_keys = [];
      $keys = [];
      $desc = [];
      $required = [];
      $models = [[]];
      $subs =[[]];
      $accessories = [];
      $count = 0;
      $parent_cles = new SimpleXMLElement($xml);
      foreach($parent_cles->ConfigLogic as $parent_cle) {
        $parent_key = $parent_cle->PKey;
        $parent_keys[] = $parent_key;
        if($parent_cle->xpath('//ConfigLogic[@xsi:type="ListConfigLogic"]') != NULL) {
          $cle = $parent_cle->xpath('//ConfigLogic[@xsi:type="ListConfigLogic"]');
          if($count == 0) {
            for ($i=0;$i<sizeof($cle);$i++) {
              $keys[] = $cle[$i]->PKey;
              $desc[] = $cle[$i]->Description;
              $required[] = $cle[$i]->ChoiceRequired != '' ?$cle[$i]->ChoiceRequired:'false';
              foreach($cle[$i]->Models->ModelLogic as $single_model_logic) {
                $models[$i][] = (string)$single_model_logic->Model;
              }
              if($cle[$i]->Models->ModelLogic->PossibleConfigurations->guid != '') {
                foreach($cle[$i]->Models->ModelLogic->PossibleConfigurations->guid as $single_guid) {
                  $subs[$i][] = (string)$single_guid;
                }
              } else {
                $subs[$i][]= array();
              }
            }
          }
          $count++;
        } else {
          $cle = $parent_cle;
        }
      }
      for($i=0;$i<sizeof($parent_keys);$i++) {
        $parent_key = (string)$parent_keys[$i];
        $name = (string)$desc[$i];
        $key = (string)$keys[$i];
        $req = (string)$required[$i];
        $temparr = array($parent_key=>array('Name'=>$name,'key'=>$key,'required'=>$req,'models'=>$models[$i],'subs'=>$subs[$i],'models_dict'=>array(),'sub_accessories'=>array()));
        array_push($accessories,$temparr);
      }
      $model_map = [];
      foreach ($response->Accessories as $accessory) {
        $temparr = array((string)$accessory->Model=>$accessory);
        array_push($model_map,$temparr);
      }
      foreach ($model_map as $single_model_map) {
        $single_model_key = key($single_model_map);
        foreach($accessories as $key => $accessory) {
          $accessory_key = key($accessory);
          $models_array = $accessory[$accessory_key]["models"];
          if(in_array($single_model_key,$models_array)) {
            array_push($accessories[$key][$accessory_key]["models_dict"],$single_model_map);
          }
        }
      }
      $sub_accessories_keys = [];
      foreach($accessories as $key=>$accessory ) {
        $accessory_key = key($accessory);
        $subs = $accessory[$accessory_key]["subs"];
        if(sizeof($subs) > 0) {
          foreach($subs as $sub) {
            foreach($accessories as $single_accessory) {
              $single_accessory_key = key($single_accessory);
              if($sub == $single_accessory_key) {
                array_push($sub_accessories_keys,$sub);
                array_push($accessories[$key][$accessory_key]["sub_accessories"],$single_accessory[$sub]);
              }
            }
          }
        }
      }
      foreach($sub_accessories_keys as $sub_accessories_key) {
        foreach($accessories as $key=>$accessory) {
          $accessory_key = key($accessory);
          if($accessory_key == $sub_accessories_key) {
            unset($accessories[$key]);
          }
        }
      }
      $this->create_csv($search_term,$base_model,$accessories);
    }
    private function clean_array($array_to_be_cleaned, $allowed_key) {
     $clean_array = array_filter((array)$array_to_be_cleaned, function($key)use($allowed_key){
        return in_array($key,$allowed_key);
      },ARRAY_FILTER_USE_KEY);
      return $clean_array;
    }
    private function ksort_arr (&$arr, $index_arr) {
      $arr_t=array();
      foreach($index_arr as $i=>$v) {
          foreach($arr as $k=>$b) {
              if ($k==$v) $arr_t[$k]=$b;
          }
      }
      $arr=$arr_t;
    }
    public function create_csv($search_term,$base_model,$accessories) {
      $indent = ['',''];
      $model_ident = ['','','',''];
      $sub_accessory_indent = ['','','','','','','',''];
      $sub_model_indent = ['','','','','','','','','',''];
      $allowed_base_keys = ['VendorName', 'Model', 'ListPrice', 'CallForPricing', 'Spec'];
      $allowed_accessory_keys = ['Name', 'required'];
      $allowed_model_keys = ['Model', 'ListPrice', 'CallForPricing', 'Spec'];
      $out_base_model = $this->clean_array($base_model,$allowed_base_keys);
      $this->ksort_arr($out_base_model,$allowed_base_keys);
      $olist = [array_keys($out_base_model),array_values($out_base_model),$indent,$indent];
      foreach($accessories as $key=>$accessory) {
        $accessory_key = key($accessory);
        $out_accessory = $this->clean_array($accessory[$accessory_key],$allowed_accessory_keys);
        $this->ksort_arr($out_accessory,$allowed_accessory_keys);
        array_push($olist,array_merge($indent,array_keys($out_accessory)));
        array_push($olist,array_merge($indent,array_values($out_accessory)));
        
        $models_dict = $accessory[$accessory_key]["models_dict"];
        foreach($models_dict as $mod_key=>$model_dict) {
          $model_dict_key = key($model_dict);
          $out_model = $this->clean_array($model_dict[$model_dict_key],$allowed_model_keys);
          $this->ksort_arr($out_model,$allowed_model_keys);
          array_push($olist,array_merge($model_ident,array_keys($out_model)));
          array_push($olist,array_merge($model_ident,array_values($out_model)));
        }

        $sub_accessories = $accessory[$accessory_key]["sub_accessories"];
        if (sizeof($sub_accessories) > 0) {
          array_push($olist,$indent);
          foreach($sub_accessories as $sub_accessory) {
            $out_sub = $this->clean_array($sub_accessory,$allowed_accessory_keys);
            $this->ksort_arr($out_sub,$allowed_accessory_keys);
            array_push($olist,array_merge($sub_accessory_indent,array_keys($out_sub)));
            array_push($olist,array_merge($sub_accessory_indent,array_values($out_sub)));
            
            $sub_accessory_model = $sub_accessory["models_dict"];
            foreach($sub_accessory_model as $mod_key=>$model_dict) {
              $model_dict_key = key($model_dict);
              $out_model = $this->clean_array($model_dict[$model_dict_key],$allowed_model_keys);
              $this->ksort_arr($out_model,$allowed_model_keys);
              array_push($olist,array_merge($sub_model_indent,array_keys($out_model)));
              array_push($olist,array_merge($sub_model_indent,array_values($out_model)));
            }
          }
        }
        array_push($olist,$indent);
      }
      $file_name = trim($search_term).'.csv';
      $file = fopen($this->output_directory_path.'/'.$file_name,"a");
      foreach ($olist as $line) {
        fputcsv($file, $line);
      }
      $this->printhelper->print_status("Extract data saved in ".$file_name."\n");
    }
  }
