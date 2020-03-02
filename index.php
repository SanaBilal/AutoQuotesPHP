<?php
  include "./app/AutoQuotes.php";
  $config = file_get_contents('./config/config.json');
  $output_directory_path = getcwd().'/data';
  $quotes = new AutoQuotes($config,$output_directory_path);
  $quotes->initliazer();
  $search_term = $quotes->get_manufacturer_input();
  $quotes->login();
  $quotes->search_products($search_term);
?>