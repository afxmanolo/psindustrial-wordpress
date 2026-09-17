<?php 
$zoom = empty($_GET["zoom"]) ? '12' : $_GET["zoom"];
$lat = empty($_GET["lat"]) ? '19.05' : $_GET["lat"];
$lng = empty($_GET["lng"]) ? '-98.2' : $_GET["lng"];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <style type="text/css">
      @import url('css/estilo.css');
    </style>
    <title>Google Mapper</title>
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAERVLAjzo5aawC9322Nn09RTD3Txe0MzTK7acwS76gCI4nF6ruhTowGY246lbv-sUnb8vUIyUQABTaQ"
      type="text/javascript"></script>
    <script type="text/javascript">
    //<![CDATA[

		var marker; 
		var map;
    
		function showInfo(){
//			point = marker.getPoint();
//			html = "Latitude: " + point.y +"<br>";
//			html += "Longitude: " + point.x + "<br>";
//			html += "Zoom: " + map.getZoom();
//			marker.openInfoWindowHtml( html );
		}
		
    function initialize() {
      if (GBrowserIsCompatible()) {
			  map = new GMap2(document.getElementById("map2"));
        var center = new GLatLng(<?=$lat?>, <?=$lng?>);
        map.setCenter(center, <?php echo $zoom; ?>);

        marker = new GMarker(center, {draggable: true});

        GEvent.addListener(marker, "dragstart", function() {
//          map.closeInfoWindow();
        });

        GEvent.addListener(marker, "dragend", showInfo );

				GEvent.addListener(map,"click", function(overlay,point) {   
					marker.setLatLng( point );
//					showInfo();
				});
        map.addOverlay(marker);
        map.addControl(new GLargeMapControl() );
        map.addControl(new GMapTypeControl() );
				map.enableDoubleClickZoom();
      }
    }
    
    function getCoords(){
      point = marker.getPoint();
      lat   = point.y;
      lng   = point.x;
      zoom  = map.getZoom();
      window.opener.document.getElementById('lat').value = lat;
      window.opener.document.getElementById('lng').value = lng;
      window.opener.document.getElementById('zoom').value  = zoom;
      
      window.close();
    }

    //]]>
    </script>

  </head>
  <body onload="initialize()" onunload="GUnload()">
    
    <div id="map2" style="width: 500px; height: 500px; border: 3px double #ab2131"></div>
    <input type="button" class="frmButton" value="Obtener coordenadas y cerrar" onclick="getCoords()" />
    <div>
      <b>Instrucciones:</b><br />
      <ul>
      <li>Zoom: Dar doble click sobre cualquier parte del mapa.</li>

      <li>Mover el globo: Arrastrar el globo y soltarlo en el lugar deseado, o hacer un click sobre el punto del mapa deseado.</li>
      </ul>
      <b>Notas:</b><br />
      <ul>
      <li>La visualización de tipo Mapa y Sat&eacute;lite tienen un ligero desplazamiento hacia el sur (~20m). Es posible ver la diferencia usando al usar el mapa H&iacute;brido.</li>
      </ul>

    </div>
		
  </body>
</html>