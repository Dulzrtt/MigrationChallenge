<?php
/*
  Biblioteca de Funções.
    Você pode separar funções muito utilizadas nesta biblioteca, evitando replicação de código.
*/

function dateNow(){
  date_default_timezone_set('America/Sao_Paulo');
  return date('d-m-Y \à\s H:i:s');
}
//funçao para abrir o arquivo csv
function open_csv($csv_path){
  $file = fopen($csv_path, "r");
  return $file;
}
//funçao para pegar as linhas do csv e colocar em um array 
function csv_rows($file){
  $csv_rows = [];
  $line_number = 0;
  while(!feof($file)){
    $line = fgetcsv($file);
    //if para resolver o problema de linha em branco do arquivo.csv e ignorar a primeira linha
    if($line !== false && !empty(array_filter($line)) && $line_number != 0){
      //troca o / para - para ficar no formato padrao de date no sql e não gerar warning
      $line[0] = str_replace("/", "-" ,$line[0]);
      $csv_rows[] = explode(";", $line[0]);
    }
    $line_number++;
  }
  fclose($file);
  return $csv_rows;

}
//funçao para deixar a date em formato americano padrao sql
function format_date(&$datas, $index){
      foreach($datas as &$data){
        $date = DateTime::createFromFormat('d-m-Y', $data[$index]);
        $data[$index] = $date->format('Y-m-d');
      }
  }


//preparar os columns name e values do insert
function add_values($datas, $columns_name = [], $default_value = 0){
  $values_complete = " values";
  $columns_complete = "";  
  
  foreach($datas as $data){
      $new_value = "'" . implode("','", $data) . "'";
      $new_value = "("  . ($default_value !== 0 ? "DEFAULT," : "") . "{$new_value}),"; 
      $values_complete = "{$values_complete}{$new_value} \n";
    }

  if(!empty($columns_name)){
    foreach($columns_name as $name){ 
      $new_columns = "'" . $name . "'" . ",";
      $columns_complete = "{$columns_complete}{$new_columns}";
    }
    $columns_complete = "(" . $columns_complete . ") \n";
    $columns_complete = replace_last($columns_complete, ",)" , ")" );
  }  
  $values_complete = replace_last($values_complete, ",", "");
  $insert_complete = $columns_complete . $values_complete;
  return $insert_complete;

}

//colocar um valor geral para uma coluna
function set_general_value($array , $value, $pos){
  foreach($array as &$subarray){
      array_splice($subarray, $pos, 0, $value);
    }
  return $array;  
}
//colocar um valor especifico em uma coluna especifica
function set_spefic_value($array, $posX, $value, $pos){
  array_splice($array[$posX], $pos, 0, $value);
  return $array;
}

//funçao para remover o ultimo carectere caso necessario de uma string 
function replace_last($str, $char, $replace){
  $pos = strrpos($str, $char);
  $str = substr_replace($str, $replace, $pos);
  return $str;
}
//----------------funcoes que utilizam o banco de dados-------------------------

//preparar a string para insert sql
function prepare_sql_to_insert($sql_values, $table_name){
  $sql = "INSERT IGNORE INTO " . $table_name . $sql_values;
  return $sql;
}
//pegar o nome das colunas de uma tabela
function get_columns_name($conn, $table_name){
  $sql = "SELECT * FROM {$table_name} LIMIT 1";
  $select_array = select_into_db($conn, $sql);
  $columns_name = [];
  while($row = $select_array->fetch_field()){
    $columns_name [] = $row->name;
  }
  return $columns_name;
}

//executar o insert
function insert_into_db($conn, $sql){
  mysqli_query($conn, $sql); 
}
//executar o select
function select_into_db($conn, $sql){
  return mysqli_query($conn, $sql)->fetch_all();
}