<?php
if($_SERVER['SERVER_NAME'] != "localhost" && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on"))
{
  header('Location: https://posicao.elissonsilva.com.br/');
  exit;
}

$ultimosRegistros = 5;
        
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta charset="UTF-8">

  <link rel="stylesheet" href="./css/bootstrap.min.css" />
  <link rel="stylesheet" href="./css/bootstrap-theme.min.css" />
  <link rel="stylesheet" href="./css/bootstrap-datepicker.min.css" />
  <link rel="stylesheet" href="./css/bootstrap-slider.css" />

  <script src="./js/jquery-3.2.1.min.js"></script> 
  <script src="./js/bootstrap.min.js"></script> 
  <script src="./js/bootstrap-datepicker.min.js"></script> 
  <script src="./js/bootstrap-datepicker.pt-BR.min.js"></script> 
  <script src="./js/bootstrap-slider.js"></script>
  <script src="./js/markerclusterer.js"></script>
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDr0udx8SRWTES0eq9R13mcuwY4XqwY6sc&callback=initMap"></script>

  <style>
  html, body {
      height: 100%;
  }
  .fill { 
      min-height: 100%;
      height: 100%;
  }
  #map {
      width: 100%;
      height: 100%;
      min-height: 100%;
  }
  .mapContainer {
      padding: 0px;
  }
  .contentContainer {
      padding-top: 10px;
      padding-bottom: 10px;
      overflow-x: hidden;       
      overflow-y: auto;
  }
  .contentContainer > .container {
      width: 100%;        
  }
  @media (max-width: 991px  ) {
    .contentContainer {
        min-height: 60%;
        height: 60%;
    }
    .mapContainer {
        min-height: 40%;
        height: 40%;
    }
    .nav-tabs > li {
        float: none;
        display:inline-block;
    }
  }
  @media (min-width: 992px  ) {
    .mapContainer, 
    .contentContainer {
      min-height: 100%;
      height: 100%;
    }
  }
  </style>
  <script>
    var geocoder, map, bounds, currentMarker, nearMarks, markerCluster;
    var last10Markers = [
    <?php
    try {
      require_once('conn.php');
      
      $select = "select latitude, longitude from local l, historico h where l.id_local = h.id_local order by h.data_visita desc, h.salvo_em desc limit {$ultimosRegistros}";
    
      if(!( $result = $conn->query($select)) )
        throw new Exception('Falha ao recuperar dados: ' . $conn->error);
        
      $count = 0; $label = $result->num_rows;
      while($row = $result->fetch_object()) {
        $count++;
        echo ($count > 1 ? ",\n        " : "    ") . "{lat: {$row->latitude}, lng: {$row->longitude}, label: '{$label}'}";
        $label--;
      }
      $result->free();
      
      echo "\n";    
    
    } catch(Exception $e) {
    }

    ?>
    ]
    var locationsAll = [
    <?php
    try {
      require_once('conn.php');
      
      $select = "select latitude, longitude, max(h.data_visita) data_visita 
                   from local l, historico h
                  where l.id_local = h.id_local
                  group by latitude, longitude 
                  order by data_visita desc, l.salvo_em desc";
      
    
      if(!( $result = $conn->query($select)) )
        throw new Exception('Falha ao recuperar dados: ' . $conn->error);
        
      $count = 0;  $label = $result->num_rows;
      while($row = $result->fetch_object()) {
        $count++;
        echo ($count > 1 ? ",\n        " : "    ") . "{lat: {$row->latitude}, lng: {$row->longitude}, label: '{$label}'}";
        $label--;
      }
      $result->free();
      
      echo "\n";    
    
    } catch(Exception $e) {
    }

    ?>
    ];
    function error(msg) {
      if(typeof msg === 'string')
        alert(msg);
      else if(typeof msg.message === 'string')
        alert(msg.message);
      else
        alert("Unknow error type: " + msg);
    }
    function setLatLong(lat, lon) {
      $("#latitude").val(lat);
      $("#longitude").val(lon);
    }
    function getAddressInfo(data,requestedType) {
      for (info of data)
        for (infoType of info.types) 
          if(infoType == requestedType) 
            return info.short_name;
      
      return null;
    }
    function setAddress(lat, lon) {
    
      $.ajax({
        url: "https://maps.googleapis.com/maps/api/geocode/json",
        method: "GET",
        dataType: "json", 
        data: { latlng: lat + "," + lon,
                sensor: true },
        success: function(response,status,xhr) {
          if(response.status == "OK" && response.results.length > 0) {
            var addressData = response.results[0].address_components;
            var endereco = getAddressInfo(addressData,"route");
            var numero   = getAddressInfo(addressData,"street_number");
            var bairro   = getAddressInfo(addressData,"sublocality");
            var cidade   = getAddressInfo(addressData,"administrative_area_level_2");
            var estado   = getAddressInfo(addressData,"administrative_area_level_1");
            var cep      = getAddressInfo(addressData,"postal_code");

            endereco = ( endereco ? endereco + ( numero ? ", " + numero : "") : "");

            $("#endereco").val(endereco);
            $("#bairro").val(bairro);
            $("#cidade").val(cidade);
            $("#estado").val(estado);
            $("#cep").val(cep);
          }
        }
      });
    
    }
    function buscarPosicao() {
    
      var endereco = $("#endereco").val();
      var bairro   = $("#bairro").val();
      var cidade   = $("#cidade").val();
      var estado   = $("#estado").val();
      var cep      = $("#cep").val();
      var address  = endereco + " - " + bairro + ", " + cidade + " - " + estado + ", " + cep;  
      
      $.ajax({
        url: "https://maps.googleapis.com/maps/api/geocode/json",
        method: "GET",
        dataType: "json", 
        data: { address: address,
                sensor: true },
        success: function(response,status,xhr) {
          if(response.status == "OK" && response.results.length > 0) {
            map.setCenter(response.results[0].geometry.location);
            currentMarker.setPosition(response.results[0].geometry.location);
            setLatLong(response.results[0].geometry.location.lat, response.results[0].geometry.location.lng);
          }
        }
      });  
    
    }
    // Verifica se precisa realizar o map das localizações
    function isJSON(data) {
      try {return JSON.parse(data);}
      catch(e) {return false;}
    }
    //
    function buscarProximos() {
      var latitude  = $("#latitude").val();
      var longitude = $("#longitude").val();
      var maximo = $("#distancia").val();
      
      var tbody = $("#resultadoProximo tbody");
      
      tbody.html("<tr><td colspan='10'>AGUARDE...</td></tr>");
      
      nearMarks = new Array();
      
      $.ajax({
        url: "proximo.php",
        method: "POST",
        data: { latitude:  latitude,
                longitude: longitude,
                maximo:    maximo },
        success: function(response,status,xhr) {
          tbody.html(response);          
          var linhas = $("tr",tbody);
          
          $.each(linhas,function(i,linha){
            var tempMarker = new google.maps.Marker({
              position: {lat: parseFloat(linha.getAttribute("lat")), lng: parseFloat(linha.getAttribute("lng"))},
              draggable: true,
              label: "" + (i + 1)
            })
            
            google.maps.event.addListener(tempMarker, "dragend", function() {
                var id = linha.getAttribute("local");
                var position = this.getPosition();
                
                // Verifica se o FORM esta utilizando o ID dessa marca
                if( document.salvarVisita.local.value == id)
                {                
                  document.salvarVisita.lat.value = position.lat();
                  document.salvarVisita.lng.value = position.lng();
                }
            });

            nearMarks.push(tempMarker);
            
            $(linha).click(function() {
              showIncluirVisita(this.getAttribute("local"), this.getAttribute("lat"), this.getAttribute("lng"));
              
              var markerLat = parseFloat(this.getAttribute("lat"));
              var markerLng = parseFloat(this.getAttribute("lng"));
              
              map.setCenter({ lat: markerLat, lng: markerLng });
              map.setZoom(17);
              
            });
          });
          
          showNearMarks("show");
                    
        }
      });
    
    }
    //
    function showIncluirVisita(idLocal, lat, lng) {

      var div = $("#incluirVisita");           
      div.show();

      document.salvarVisita.local.value = idLocal;
      document.salvarVisita.lat.value = lat;
      document.salvarVisita.lng.value = lng;
      
      var local  = $("#localVisita",div).text("...");
      var ultima = $("#ultimasVisitas",div).text("...");
      
      $.ajax({
        url: "dadosLocal.php",
        method: "POST",
        data: { local: idLocal },
        dataType: "json",
        success: function(r) {
          if(r.success)
          {
            local.html(r.local);
            ultima.html(r.ultima);
          }
          else
          {
            local.html(r.erro);
            ultima.html("");
          } 
        }
      });        
        
    }
    //
    function cancelIncluirVisita()
    {
      $("#incluirVisita").hide();
      map.fitBounds(bounds);
      if( map.getZoom() > 17 ) map.setZoom(17);
    }
    //
    function showNearMarks(showHide) {
    
      if(!nearMarks || nearMarks.length == 0) return;

      if(showHide == "show") {
      
        markerCluster = new MarkerClusterer(map, nearMarks,
          {imagePath: "https://posicao.elissonsilva.com.br/images/m"});

        bounds = new google.maps.LatLngBounds();
        bounds.extend(currentMarker.position);
        
        nearMarks.forEach(function(marker){
          bounds.extend(marker.position); 
        });

        map.fitBounds(bounds);
        
        if( map.getZoom() > 17 ) map.setZoom(17);
        
        currentMarker.setAnimation(google.maps.Animation.BOUNCE);
        
      }
            
      if(showHide == "hide") {
      
        markerCluster.clearMarkers();

        currentMarker.setAnimation(null);
        
      }
      
    }
    //
    function showLast10Marks(showHide) {
    
      if(!last10Markers || last10Markers.length == 0) return;

      if(showHide == "show") {
      
        // Se retornar é porque não é map e precisa converter
        if( last10Markers[0].lat )
        {
          // Add some markers to the map.
          // Note: The code uses the JavaScript Array.prototype.map() method to
          // create an array of markers based on a given "locations" array.
          // The map() method here has nothing to do with the Google Maps API.
          var markers = last10Markers.map(function(location, i) {
            return new google.maps.Marker({
              position: {lat: location.lat, lng: location.lng},
              label: location.label
            });
          });
          
          last10Markers = markers;
        }

        markerCluster = new MarkerClusterer(map, last10Markers,
          {imagePath: "https://posicao.elissonsilva.com.br/images/m"});

        bounds = new google.maps.LatLngBounds();
        bounds.extend(currentMarker.position);
        
        last10Markers.forEach(function(marker){
          bounds.extend(marker.position); 
        });

        map.fitBounds(bounds);
        
        if( map.getZoom() > 17 ) map.setZoom(17);
        
        currentMarker.setAnimation(google.maps.Animation.BOUNCE);
        
      }
            
      if(showHide == "hide") {
      
        // Se retornar é porque não é map e precisa converter (isso é feito no show)
        if( last10Markers[0].lat ) return;
      
        markerCluster.clearMarkers();

        currentMarker.setAnimation(null);
        
      }
      
    }
    function showAllMarks(showHide) {
    
      if(!locationsAll || locationsAll.length == 0) return;

      if(showHide == "show") {
      
        // Se retornar é porque não é map e precisa converter
        if( locationsAll[0].lat )
        {
          // Add some markers to the map.
          // Note: The code uses the JavaScript Array.prototype.map() method to
          // create an array of markers based on a given "locations" array.
          // The map() method here has nothing to do with the Google Maps API.
          var markers = locationsAll.map(function(location, i) {
            return new google.maps.Marker({
              position: {lat: location.lat, lng: location.lng},
              label: location.label
            });
          });
          
          locationsAll = markers;
        }

        markerCluster = new MarkerClusterer(map, locationsAll,
          {imagePath: "https://posicao.elissonsilva.com.br/images/m"});

        bounds = new google.maps.LatLngBounds();
        bounds.extend(currentMarker.position);
        
        locationsAll.forEach(function(marker){
          bounds.extend(marker.position); 
        });

        map.fitBounds(bounds);
        
        if( map.getZoom() > 17 ) map.setZoom(17);
        
        currentMarker.setAnimation(google.maps.Animation.BOUNCE);
        
      }
            
      if(showHide == "hide") {
      
        // Se retornar é porque não é map e precisa converter (isso é feito no show)
        if( locationsAll[0].lat ) return;
      
        markerCluster.clearMarkers();

        currentMarker.setAnimation(null);
        
      }

    }
    function initMap() {
      if (navigator.geolocation) {
       navigator.geolocation.getCurrentPosition(function(position) {
          var latitude  = position.coords.latitude;
          var longitude = position.coords.longitude;
          
          geocoder = new google.maps.Geocoder;
          map = new google.maps.Map(document.getElementById('map'), {
            zoom: 17,
            center: {lat: latitude, lng: longitude}
          });
          
          // var pinColor = "75FE69";
          // "http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld=%E2%80%A2|" + pinColor
          var pinImage = new google.maps.MarkerImage("https://posicao.elissonsilva.com.br/images/here.png",
              new google.maps.Size(21, 34),
              new google.maps.Point(0,0),
              new google.maps.Point(10, 34));
          var pinShadow = new google.maps.MarkerImage("https://posicao.elissonsilva.com.br/images/shadow.png",
              new google.maps.Size(40, 37),
              new google.maps.Point(0, 0),
              new google.maps.Point(12, 35));
          
          currentMarker = new google.maps.Marker({
            position: {lat: latitude, lng: longitude},
            map: map,
            draggable: true,
            title: "Você está aqui!",
            icon: pinImage,
            shadow: pinShadow
          });
          
          google.maps.event.addListener(currentMarker, "dragend", function() {
              var position = currentMarker.getPosition();
              setLatLong(position.lat(), position.lng());
              setAddress(position.lat(), position.lng());
          });

          setLatLong(latitude, longitude);
          setAddress(latitude, longitude);
          buscarProximos();
          
       }, error);
      } else {
       error('not supported');
      }
    }
    //
    function verTodos() {
      map.fitBounds(bounds);
      if( map.getZoom() > 17 ) map.setZoom(17);
    }
    //
    function initOnClick() {
      $("#tableUltimos10 tbody tr, #tableVisaoGeral tbody tr").click(function(){
        var markerLat = parseFloat(this.getAttribute("lat"));
        var markerLng = parseFloat(this.getAttribute("lng"));
        
        var center = map.getCenter();
        if(center.lat() == markerLat && center.lng() == markerLng)
        {
          map.fitBounds(bounds);
          if( map.getZoom() > 17 ) map.setZoom(17);
        }
        else
        {
          map.setCenter({ lat: markerLat, lng: markerLng });
          map.setZoom(17);
        }
      
      });
    }
    $(function(){
      //
      $("#data_visita1, #data_visita2").datepicker({
        language: "pt-BR"
      }).datepicker("update", new Date());
      //
      $("a[href='#voceEstaAqui']").on("shown.bs.tab", function (e) { 
        map.setCenter(currentMarker.position);
        map.setZoom(17);
      });
      //
      $("a[href='#ultimos10']")
        .on("shown.bs.tab", function (e) { showLast10Marks("show"); })
        .on("hide.bs.tab",  function (e) { showLast10Marks("hide"); });
      //
      $("a[href='#visaoGeral']")
        .on("shown.bs.tab", function (e) { showAllMarks("show"); })
        .on("hide.bs.tab",  function (e) { showAllMarks("hide"); });
      //
      $("a[href='#locaisProximos']")
        .on("shown.bs.tab", function (e) { showNearMarks("show"); })
        .on("hide.bs.tab",  function (e) { showNearMarks("hide"); });
      //
      $("#distancia").slider({
      	formatter: function(value) {
      		return "Raio de " + value + " KM";
      	}
      });
      //
      initOnClick();
      //
    });
  </script>
</head>
<body>
  <div class="container-fluid fill">
    <div class="row fill">
      <div class="col-md-4 contentContainer">
        <div class="container">
        <!-- ############################################################################################## //-->
        <ul class="nav nav-tabs">
          <li class="active"><a data-toggle="tab" href="#locaisProximos"><span class="glyphicon glyphicon-screenshot"></span></a></li>
          <li><a data-toggle="tab" href="#voceEstaAqui"><span class="glyphicon glyphicon-map-marker"></span></a></li>
          <li><a data-toggle="tab" href="#ultimos10"><span class="glyphicon glyphicon-th-list"></span></a></li>
          <li><a data-toggle="tab" href="#visaoGeral"><span class="glyphicon glyphicon-globe"></span></a></li>
        </ul>
        <div class="tab-content">
        <!-- ############################################################################################## //-->
        <div id="locaisProximos" class="tab-pane fade in active">
          <div class="panel panel-default">
            <div class="panel-heading">Locais visitados proximos</div>
            <div class="panel-body">
            
            Raio: &nbsp; <input id="distancia" type="text"  
              data-slider-id='ex1Slider' 
              data-slider-min="1" 
              data-slider-max="10" 
              data-slider-step="1" 
              data-slider-value="2"/>
              
            &nbsp;
            
            <button type="button" class="btn btn-default btn-sm" onclick="buscarProximos()">BUSCAR</button>
            <br><br>
            <table id="resultadoProximo" class="table table-condensed table-hover">
            <thead>
            <tr>
              <th>#</th>
              <th>Distancia</th>
              <th>Nome</th>
              <th>Bairro</th>
              <th>Cidade/UF</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
            </table>
            
            </div>
          </div>       
          <br>
          <div class="panel panel-default" id="incluirVisita" style="display: none">
            <div class="panel-heading">Incluir uma visita</div>
            <div class="panel-body">
              <form class="form-horizontal" action="salvarVisita.php" name="salvarVisita" method="POST">
              <input type="hidden" id="local" name="local">
              <input type="hidden" id="lat" name="lat">
              <input type="hidden" id="lng" name="lng">
                <div class="form-group">
                  <label for="localVisita" class="col-sm-3 control-label">Local</label>
                  <div class="col-sm-9 form-control-static">
                    <span id="localVisita">...</span>
                  </div>
                </div>
                <div class="form-group">
                  <label for="ultimasVisitas" class="col-sm-3 control-label">Histórico</label>
                  <div class="col-sm-9 form-control-static">
                    <span id="ultimasVisitas">...</span>
                  </div>
                </div>
                <div class="form-group">
                  <label for="data_visita1" class="col-sm-3 control-label">Quando</label>
                  <div class="col-sm-9">
                    <div class='input-group date' id='data_visita1'>
                        <input type='text' class="form-control input-sm" name="data_visita"/>
                        <span class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label for="descricaoVisita" class="col-sm-3 control-label">Descrição Visita</label>
                  <div class="col-sm-9">
                    <textarea class="form-control" rows="3" id="descricao" name="descricao_visita"></textarea>
                  </div>
                </div>
                <div class="form-group">
                  <div class="col-sm-offset-3 col-sm-9">
                    <button type="submit" class="btn btn-success">SALVAR</button>
                    <button type="button" class="btn btn-default" onclick="cancelIncluirVisita()">CANCELAR</button>
                  </div>
                </div>
              </form>      
            </div>
          </div>  
        </div>
        <!-- ############################################################################################## //-->
        <div id="voceEstaAqui" class="tab-pane fade">
          <div class="panel panel-default">
            <div class="panel-heading">Você está aqui</div>
            <div class="panel-body">
              <form class="form-horizontal" action="salvar.php" method="POST">
              <input type="hidden" id="latitude" name="latitude">
              <input type="hidden" id="longitude" name="longitude">
                <div class="form-group">
                  <label for="endereco" class="col-sm-3 control-label">Endereço</label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control input-sm" id="endereco" name="endereco">
                  </div>
                </div>
                <div class="form-group">
                  <label for="bairro" class="col-sm-3 control-label">Bairro</label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control input-sm" id="bairro" name="bairro">
                  </div>
                </div>
                <div class="form-group">
                  <label for="cidade" class="col-sm-3 control-label">Cidade</label>
                  <div class="col-sm-5">
                    <input type="text" class="form-control input-sm" id="cidade" name="cidade">
                  </div>
                  <label for="estado" class="col-sm-1 control-label">UF</label>
                  <div class="col-sm-3">
                    <input type="text" class="form-control input-sm" id="estado" name="estado">
                  </div>
                </div>
                <div class="form-group">
                  <label for="cep" class="col-sm-3 control-label">CEP</label>
                  <div class="col-sm-4">
                    <input type="text" class="form-control input-sm" id="cep" name="cep">
                  </div>
                </div>
                <div class="form-group">
                  <label for="data_visita2" class="col-sm-3 control-label">Quando</label>
                  <div class="col-sm-9">
                    <div class='input-group date' id='data_visita2'>
                        <input type='text' class="form-control input-sm" name="data_visita"/>
                        <span class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label for="nome" class="col-sm-3 control-label">Nome</label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control input-sm" id="nome" name="nome">
                  </div>
                </div>
                <div class="form-group">
                  <label for="descricao" class="col-sm-3 control-label">Descrição Local</label>
                  <div class="col-sm-9">
                    <textarea class="form-control" rows="3" id="descricao_local" name="descricao_local"></textarea>
                  </div>
                </div>
                <div class="form-group">
                  <label for="descricao" class="col-sm-3 control-label">Descrição Visita</label>
                  <div class="col-sm-9">
                    <textarea class="form-control" rows="3" id="descricao_visita" name="descricao_visita"></textarea>
                  </div>
                </div>
                <div class="form-group">
                  <div class="col-sm-offset-3 col-sm-9">
                    <button type="submit" class="btn btn-success">SALVAR</button>
                    <button type="button" class="btn btn-default" onclick="buscarPosicao()">BUSCAR</button>
                  </div>
                </div>
              </form>      
            </div>
          </div>        
        </div>
        <!-- ############################################################################################## //-->
        <div id="ultimos10" class="tab-pane fade">
          <div class="panel panel-default">
            <div class="panel-heading">Ultimos <?php echo $ultimosRegistros; ?> registros <button type="button" class="btn btn-primary btn-xs" onclick="verTodos()" style="float: right;">VER TODOS</button></div>
            <div class="panel-body">
            <table id="tableUltimos10" class="table table-condensed table-hover">
            <thead>
            <tr>
              <th>#</th>
              <th>Visita</th>
              <th>Nome</th>
              <th>Bairro</th>
              <th>Cidade/UF</th>
            </tr>
            </thead>
            <tbody>
            <?php
            try {
    
              require_once('conn.php');
              
              $select = "select l.id_local, latitude, longitude, nome, bairro, cidade, estado, DATE_FORMAT(data_visita,'%d/%m/%Y') visita from local l, historico h where l.id_local = h.id_local order by h.data_visita desc, h.salvo_em desc limit {$ultimosRegistros}";
            
              if(!( $result = $conn->query($select)) )
                throw new Exception('Falha ao recuperar dados: ' . $conn->error);
                
              $count = $result->num_rows;
              while($row = $result->fetch_object()) {
                $row->nome    = utf8_encode($row->nome);
                $row->bairro  = utf8_encode($row->bairro);
                $row->cidade  = utf8_encode($row->cidade);
                
                echo "<tr lat='{$row->latitude}' lng='{$row->longitude}' local='{$row->id_local}'><td>{$count}</td><td>{$row->visita}</td><td>{$row->nome}</td><td>{$row->bairro}</td><td>{$row->cidade}/{$row->estado}</td></tr>";
                $count--;
                
              }
              $result->free();    
            
            } catch(Exception $e) {
            
              echo "<tr><td colspan='10'><p><b>{$e->getMessage()}</b></p></td></tr>";
            
            }
    
            ?>
            </tbody>
            </table>
            </div>
          </div>        
        </div>
        <!-- ############################################################################################## //-->
        <div id="visaoGeral" class="tab-pane fade">
          <div class="panel panel-default">
            <div class="panel-heading">Visão geral <button type="button" class="btn btn-primary btn-xs" onclick="verTodos()" style="float: right;">VER TODOS</button></div>
            <div class="panel-body">
            <table id="tableVisaoGeral" class="table table-condensed table-hover">
            <thead>
            <tr>
              <th>#</th>
              <th>Visita</th>
              <th>Nome</th>
              <th>Bairro</th>
              <th>Cidade/UF</th>
            </tr>
            </thead>
            <tbody>
            <?php
            try {
    
              require_once('conn.php');
              
              $select = "select l.id_local, latitude, longitude, nome, bairro, cidade, estado, l.salvo_em, max(h.data_visita) data_visita 
                           from local l, historico h
                          where l.id_local = h.id_local
                          group by l.id_local, latitude, longitude, nome, bairro, cidade, estado, l.salvo_em 
                          order by data_visita desc, l.salvo_em desc";
            
              if(!( $result = $conn->query($select)) )
                throw new Exception('Falha ao recuperar dados: ' . $conn->error);
                
              $count = $result->num_rows;
              while($row = $result->fetch_object()) {
                $row->nome    = utf8_encode($row->nome);
                $row->bairro  = utf8_encode($row->bairro);
                $row->cidade  = utf8_encode($row->cidade);
                
                $dataVisita  = "";
                $countVisita = 0;
                $select = "select DATE_FORMAT(data_visita,'%d/%m/%Y') visita from historico where id_local = {$row->id_local} order by data_visita desc, salvo_em desc";
                if(( $resultVisita = $conn->query($select)))
                {
                  $countVisita = 0;
                  while($rowVisita = $resultVisita->fetch_object()) {
                    $dataVisita .= ($countVisita++ > 0 ? "<br>" : "") . $rowVisita->visita;
                  }
                }
            
                echo "<tr lat='{$row->latitude}' lng='{$row->longitude}'><td>{$count}</td><td>{$dataVisita}</td><td>{$row->nome}</td><td>{$row->bairro}</td><td>{$row->cidade}/{$row->estado}</td></tr>";
                $count--;
                
              }
              $result->free();    
            
            } catch(Exception $e) {
            
              echo "<tr><td colspan='10'><p><b>{$e->getMessage()}</b></p></td></tr>";
            
            }
    
            ?>
            </tbody>
            </table>
            </div>
          </div>        
        </div>
        <!-- ############################################################################################## //-->
        </div>
        </div>
      </div>
      <div class="col-md-8 mapContainer">
        <div id="map"></div> 
      </div>
    </div>  
  </div>
</body>
</html>