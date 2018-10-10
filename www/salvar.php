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

  function distancia($lat1, $lon1, $lat2, $lon2) {

    $lat1 = deg2rad($lat1);
    $lat2 = deg2rad($lat2);
    $lon1 = deg2rad($lon1);
    $lon2 = deg2rad($lon2);
    
    $dist = (6371 * acos( cos( $lat1 ) * cos( $lat2 ) * cos( $lon2 - $lon1 ) + sin( $lat1 ) * sin($lat2) ) );
    $dist = number_format($dist, 3, '.', '');
    return $dist;
  }
  
  function formataDistancia( $distancia ) {
    
    if( $distancia == 0 ) return "menos de 1 metro";
    
    if( $distancia < 10000 )
    {
      $plural = ($distancia == 1 ? "" : "s");
      return number_format((float)$distancia, 0, ',', '.') . " metro{$plural}";
    };
    
    $distancia = $distancia / 1000;
    return number_format((float)$distancia, 0, ',', '.') . " Km";
    
  }

  $latitude   = $conn->real_escape_string($_POST['latitude']);
  $longitude  = $conn->real_escape_string($_POST['longitude']);
  $endereco   = utf8_decode($conn->real_escape_string($_POST['endereco']));
  $bairro     = utf8_decode($conn->real_escape_string($_POST['bairro']));
  $cidade     = utf8_decode($conn->real_escape_string($_POST['cidade']));                                            
  $estado     = $conn->real_escape_string($_POST['estado']);
  $cep        = $conn->real_escape_string($_POST['cep']);
  $dataVisita = $conn->real_escape_string($_POST['data_visita']);
  $nome       = utf8_decode($conn->real_escape_string($_POST['nome']));
  $descricaoL = utf8_decode($conn->real_escape_string($_POST['descricao_local']));
  $descricaoV = utf8_decode($conn->real_escape_string($_POST['descricao_visita']));
                                                   
  $latitude  = number_format((float)$latitude, 10, '.', '');
  $longitude = number_format((float)$longitude, 10, '.', '');

  // Verifica se campos obrigatorios estao preenchidos
  echo "<b>Validando campos obrigatorios</b><br>";
  if( empty($latitude) )   throw new Exception('Campo LATITUDE é obrigatório');
  if( empty($longitude) )  throw new Exception('Campo LONGITUDE é obrigatório');
  if( empty($endereco) )   throw new Exception('Campo ENDERECO é obrigatório');
  if( empty($bairro) )     throw new Exception('Campo BAIRRO é obrigatório');
  if( empty($cidade) )     throw new Exception('Campo CIDADE é obrigatório');
  if( empty($estado) )     throw new Exception('Campo ESTADO é obrigatório');
  if( empty($dataVisita) ) throw new Exception('Campo QUANDO é obrigatório');
  if( empty($nome) )       throw new Exception('Campo NOME é obrigatório');
  
  echo "OK<br><br>";

  // Verifica se já tem um registro no banco para a latitude e longitude recebida
  echo "<b>Validando duplicidade</b><br>";

  $select = "select endereco, nome from local where latitude = {$latitude} and longitude = {$longitude}";

  if(!( $result = $conn->query($select)) )
    throw new Exception('Falha ao validar duplicidade: ' . $conn->error);
    
  if($row = $result->fetch_object()) {
    $row->endereco = utf8_encode($row->endereco);
    $row->nome     = utf8_encode($row->nome);
    $result->free();
    throw new Exception("Duplicidade: Já existe um registro nessas coordenadas para {$row->endereco} ({$row->nome})");
  }
  $result->free();    
  echo "OK<br><br>";

  // Verifica se já tem um registro no banco que esteja a menos de 10 metros de distancia da localização recebida
  echo "<b>Validando distancia</b><br>";

  $select = "select latitude, longitude, endereco, nome from local";

  if(!( $result = $conn->query($select)) )
    throw new Exception('Falha ao validar distancias: ' . $conn->error);
    
  $count = 0;
  while($row = $result->fetch_object()) {
    $row->endereco = utf8_encode($row->endereco);
    $row->nome     = utf8_encode($row->nome);

    $distancia = distancia((float)$latitude, (float)$longitude, (float)$row->latitude, (float)$row->longitude) * 1000;
    
    $distanciaStr = formataDistancia( $distancia );
    
    echo ++$count . ". {$row->endereco} ({$row->nome}) -> {$distanciaStr} <br>";
    
    if($distancia <= 30) {
      $result->free();
      throw new Exception("Duplicidade: Já existe um registro à {$distanciaStr} dessas coordenadas para {$row->endereco} ({$row->nome})");
    }
  }
  $result->free();    
  echo "OK<br><br>";

  // ,
  
  $insert = "insert into local (latitude, longitude, endereco, bairro, cidade, estado, cep, nome, descricao, salvo_em) values ({$latitude}, {$longitude}, '{$endereco}', '{$bairro}', '{$cidade}', '{$estado}', '{$cep}', '{$nome}', '{$descricaoL}', now())";
  if($conn->query($insert) !== TRUE)
    throw new Exception('Falha ao gravar local: ' . $conn->error);
    
  $last_id = $conn->insert_id;

  $insert = "insert into historico (id_local, data_visita, descricao, salvo_em) values ({$last_id}, STR_TO_DATE('{$dataVisita}','%d/%m/%Y'), '{$descricaoV}', now())";
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