<?php
try {
  require_once('conn.php');
  
  $local = $_POST['local'];        
  
  // DATE_FORMAT(data_visita,'%d/%m/%Y') quando_str
  $select = "select nome, endereco, bairro, cidade, estado, cep from local where id_local = {$local}";

  if(!( $result = $conn->query($select)) )
    throw new Exception('Falha ao recuperar dados: ' . $conn->error);
   
  if($row = $result->fetch_object()) {
  
    $dataVisita  = "";
    $countVisita = 0;
    $select = "select DATE_FORMAT(data_visita,'%d/%m/%Y') visita, descricao from historico where id_local = {$local} order by data_visita desc, salvo_em desc";
    if(( $resultVisita = $conn->query($select)))
    {
      $countVisita = 0;
      while($rowVisita = $resultVisita->fetch_object()) {
        $dataVisita .= ($countVisita++ > 0 ? "<br>" : "") . $rowVisita->visita . ( strlen($rowVisita->descricao) > 0 ? " - {$rowVisita->descricao}" : "" );
      }
    }  

    echo utf8_encode("{ \"success\": true, \"local\": \"<i>{$row->nome}</i><br>{$row->endereco}<br>{$row->cep} - {$row->bairro}<br>{$row->cidade}/{$row->estado}\", \"ultima\": \"{$dataVisita}\" }");
    
  }
  $result->free();
  
  echo "\n";    

} catch(Exception $e) {
  echo "{ \"success\": false, \"erro\": \"{$e->getMessage()}\" }";
}
?>