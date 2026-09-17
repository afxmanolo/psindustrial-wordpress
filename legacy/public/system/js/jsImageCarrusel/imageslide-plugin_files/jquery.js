jQuery(function(){
   jQuery("div.svw").prepend("<img src='http://www.gcmingati.net/wordpress/wp-content/uploads/svwloader.gif' class='ldrgif' alt='loading...'/ >"); 
});
var j = 0;
jQuery.fn.slideView = function(settings) {
	  settings = jQuery.extend({
     easeFunc: "easeInOutExpo", /* <-- easing function names changed in jquery.easing.1.2.js */
//     easeTime: 750,
     easeTime: 1,
     toolTip: false
  }, settings);
	return this.each(function(){
		var container = jQuery(this);
		container.find("img.ldrgif").remove(); // removes the preloader gif
		container.removeClass("svw").addClass("stripViewer");		
		
		/*
		***MODIFICACION
		var pictWidth = container.find("li").find("img").width();
		var pictHeight = container.find("li").find("img").height();
		*/
		var pictWidth = 400;
		var pictHeight = 400;
		
		var pictEls = container.find("li").size();
		var stripViewerWidth = pictWidth*pictEls;
		container.find("ul").css("width" , stripViewerWidth); //assegnamo la larghezza alla lista UL	
		container.css("width" , pictWidth);
		container.css("height" , pictHeight);
		container.each(function(i) {
			jQuery(this).after("<div class='stripTransmitter' id='stripTransmitter" + j + "'><ul><\/ul><\/div>");
			
			jQuery("div#stripTransmitter" + j + " ul").append("<li><a title='Previous' href='#'><<<\/a><\/li>");
			// SE CARGAN BOTONES PARA ACCEDER A UNA DETERMIANDA PLECA
			jQuery(this).find("li").each(
						function(n) {
							jQuery("div#stripTransmitter" + j + " ul").append("<li><a title='jjjj' href='#'>"+(n+1)+"<\/a><\/li>");
						}
				);
			
			jQuery("div#stripTransmitter" + j + " ul").append("<li><a title='Play' href='#'>><\/a><\/li>");
			jQuery("div#stripTransmitter" + j + " ul").append("<li><a title='Pausa' href='#'>||<\/a><\/li>");
			jQuery("div#stripTransmitter" + j + " ul").append("<li><a title='Siguiente' href='#'>>><\/a><\/li>");
			//aplicar sobre cada elemento
			
			NElementosDespuesDeNumeracion = 2;
			positionPlay = 1;
			positionStop = 2;
			positionSiguiente = 3;
			
			ObjetGlobal = jQuery(this).find("ul")
			//ObjetGlobal.animate({ left: cnt}, SettingsGlobal.easeTime, SettingsGlobal.easeFunc);
			SettingsGlobal = settings;
			
			jQuery("div#stripTransmitter" + j + " a").each(
				function(z) { 
					//alert(z);
					if( z > 0 && z <= nPlecas /*&& 1==2/*si entra asigna a cada boton su pleca*/){
							z = z-1;
							functionApp = function(){
												jQuery(this).addClass("current").parent().parent().find("a").not(jQuery(this)).removeClass("current"); // wow!
												var cnt = - (pictWidth* (z));
												actualPlek = z+1;
												
												jQuery(this).parent().parent().parent().prev().find("ul").animate({ left: cnt}, settings.easeTime, settings.easeFunc);
												setBgColorActual();
												return false;
										   };

					}else{//ASIGNACION DE EVENTOS A LOS BOTONES << > || >>
							
						switch(z){
							case 0:
								functionApp = function(){
									var cnt =0;
									setBgColorActual();
									if( actualPlek > 1 ){
										actualPlek--;
										cnt = - (pictWidth* (actualPlek-1));
									jQuery(this).parent().parent().parent().prev().find("ul").animate({ left: cnt}, settings.easeTime, settings.easeFunc);
									}else{
										actualPlek = nPlecas;
										cnt = - (pictWidth* (actualPlek-1));
									jQuery(this).parent().parent().parent().prev().find("ul").animate({ left: cnt}, settings.easeTime, settings.easeFunc);
									}
									setBgColorActual();
									
								}	
							break;
							case (nPlecas+positionStop):
								functionApp =function(){
									setBgColorActual();
									ban=false;
								}
							break;
							case (nPlecas+positionPlay):
								functionApp = function(){
									ban = true;
									setBgColorActual();
									playAni();
								}
							break;
							case (nPlecas+positionSiguiente):
							
								functionApp = function(){
									var cnt =0;
									//alert(actualPlek);
									//actualPlek = (actualPlek <= 0) ? 1 : 0;
									if( actualPlek < nPlecas ){
										//pictWith debe ser modificado ya que es por imagen actual
										//cuando tienen la misma dimension no hay problema
										cnt = - (pictWidth* (actualPlek));
										actualPlek++;
										jQuery(this).parent().parent().parent().prev().find("ul").animate({ left: cnt}, settings.easeTime, settings.easeFunc);
									}else{
										actualPlek = 1;
										jQuery(this).parent().parent().parent().prev().find("ul").animate({ left: cnt}, settings.easeTime, settings.easeFunc);
									}
									setBgColorActual();
									
								}	
							break;
							default:
								functionApp = function(){alert("there is not function for element: " + z);}
							break;
						}
					}
					jQuery(this).bind("click", functionApp);
				}
			);
			
			jQuery("div#stripTransmitter" + j).css("width" , pictWidth);
			jQuery("div#stripTransmitter" + j + " a:eq(1)").addClass("current");
			if(settings.toolTip){
			container.next(".stripTransmitter ul").find("a").Tooltip({
				track: true,
				delay: 0,
				showURL: false,
				showBody: false
				});
			}
			});
		j++;
  });

};