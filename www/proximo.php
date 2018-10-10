<?php
class Local {

  private $dados; 
  private $distancia;

  public function __construct($dados, $distancia) {
    $this->dados = $dados;
    $this->distancia = $distancia;
  }
  
  public function getDados() {
    return $this->dados;
  }
  
  public function getDistancia() {
    return $this->distancia;
  }

}
function cmp($a, $b) {
  return ($a->getDistancia() > $b->getDistancia());
}
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
  
  if( $distancia == 0 ) return "< 1 metro";
  
  if( $distancia < 1000 )
  {
    $plural = ($distancia == 1 ? "" : "s");
    return number_format((float)$distancia, 0, ',', '.') . " metro{$plural}";
  };
  
  $distancia = $distancia / 1000;
  return number_format((float)$distancia, 0, ',', '.') . " Km";
  
}  
try {
  require_once('conn.php');
  
  $refLat = $_POST['latitude'];
  $refLon = $_POST['longitude'];
  $distanciaMaxima = $_POST['maximo'] * 1000;        
  
  // DATE_FORMAT(data_visita,'%d/%m/%Y') quando_str
  $select = "select distinct l.id_local, latitude, longitude, nome, bairro, cidade, estado from local l, historico h where l.id_local = h.id_local order by h.data_visita desc, h.salvo_em desc";

  if(!( $result = $conn->query($select)) )
    throw new Exception('Falha ao recuperar dados: ' . $conn->error);
   
  $posicoesProximas = [];
    
  $count = 0; $label = $result->num_rows;
  while($row = $result->fetch_object()) {
  
    $distancia = distancia((float)$refLat, (float)$refLon, (float)$row->latitude, (float)$row->longitude) * 1000;
    if( $distancia < $distanciaMaxima )
    {
      $posicoesProximas[] = new Local($row, $distancia);
    }
    
  }
  $result->free();
  
  if(count($posicoesProximas) == 0)
  {
    echo "<tr><td colspan='10'>Não foram localizados locais próximos</td></tr>";
  }
  else
  {                       
    usort($posicoesProximas, "cmp");

    $count = 0;
    foreach($posicoesProximas as $local)
    {
      $distancia = $local->getDistancia();
      $distancia = formataDistancia($distancia);
      $row = $local->getDados();
      $row->nome    = utf8_encode($row->nome);
      $row->bairro  = utf8_encode($row->bairro);
      $row->cidade  = utf8_encode($row->cidade);
      
      $count++;
      echo "<tr lat='{$row->latitude}' lng='{$row->longitude}' local='{$row->id_local}'><td>{$count}</td><td>{$distancia}</td><td>{$row->nome}</td><td>{$row->bairro}</td><td>{$row->cidade}/{$row->estado}</td></tr>";
    }
  }
  
  echo "\n";    

} catch(Exception $e) {
  echo "<tr><td colspan='10'>{$e->getMessage()}</td></tr>";
}
?>