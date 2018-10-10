<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta charset="UTF-8">

  <link rel="stylesheet" href="./css/bootstrap.min.css" />
  <link rel="stylesheet" href="./css/bootstrap-theme.min.css" />

  <script src="./js/jquery-3.2.1.min.js"></script> 
  <script src="./js/bootstrap.min.js"></script>

</head>
<body>
<div class="container">
<br><a class="btn btn-default" role="button" href='index.php'>VOLTAR</a><hr>
<?php
try {
  require_once('conn.php');
  
  $redirecionarAutomaticamente = true;

  $local      = $conn->real_escape_string($_POST['local']);
  $lat        = $conn->real_escape_string($_POST['lat']);
  $lng        = $conn->real_escape_string($_POST['lng']);
  $dataVisita = $conn->real_escape_string($_POST['data_visita']);
  $descricao  = utf8_decode($conn->real_escape_string($_POST['descricao_visita']));
                                                   
  // Verifica se campos obrigatorios estao preenchidos
  echo "<b>Validando campos obrigatorios</b><br>";
  if( empty($dataVisita) ) throw new Exception('Campo QUANDO é obrigatório');
  
  echo "OK<br><br>";

  // Verifica se já tem um registro no banco para a latitude e longitude recebida
  echo "<b>Validando duplicidade</b><br>";

  $select = "select descricao from historico where id_local = {$local} and data_visita = STR_TO_DATE('{$dataVisita}','%d/%m/%Y')";

  if(!( $result = $conn->query($select)) )
    throw new Exception('Falha ao validar duplicidade: ' . $conn->error);
    
  if($row = $result->fetch_object()) {
    $row->endereco = utf8_encode($row->endereco);
    $row->nome     = utf8_encode($row->nome);
    $result->free();
    throw new Exception("Duplicidade: Já existe um registro nesse dia com a descrição \"{$row->descricao}\"");
  }
  $result->free();    
  echo "OK<br><br>";

  // Atualiza latitude e longitude 
  echo "<b>Atualizando latitude e longitude</b><br>";

  $update = "update local set latitude = {$lat}, longitude = {$lng} where id_local = {$local}";
  if($conn->query($update) !== TRUE)
    throw new Exception('Falha ao atualizar o local: ' . $conn->error);

  echo "<p class='bg-success'>SUCESSO</p>";

  // Atualiza latitude e longitude 
  echo "<b>Inserindo histórico</b><br>";

  $insert = "insert into historico (id_local, data_visita, descricao, salvo_em) values ({$local}, STR_TO_DATE('{$dataVisita}','%d/%m/%Y'), '{$descricao}', now())";
  if($conn->query($insert) !== TRUE)
    throw new Exception('Falha ao gravar historico: ' . $conn->error);

  echo "<p class='bg-success'>SUCESSO</p>";
  
  if($redirecionarAutomaticamente)
    echo "<script>window.location = 'index.php';</script>";
  
} catch(Exception $e) {

  echo "<hr><h3 class='bg-danger'>ERROR</h3><p><b>{$e->getMessage()}</b></p><hr><pre>";

  print_r($_POST);

  echo "</pre>";

}
?>     
<hr><a class="btn btn-default" role="button" href='index.php'>VOLTAR</a><br><br>
</div>
</body>
</html>