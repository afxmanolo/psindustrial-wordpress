// JavaScript Document
	//var nPlecas = 3;
	var pictWidthGlobal = 0;
	var ObjetGlobal;
	var SettingsGlobal;
	var actualPlek = 0;	
	
function removeClassAll(){
	for(i=0;i<nPlecas+1;i++){
		jQuery("div#stripTransmitter0 a:eq("+i+")").removeClass("current");
	}
}
function playAni(){
	if(ban){
		var cnt =0;
		if( actualPlek < nPlecas ){
			cnt = - (anchoPlecas * (actualPlek));
			actualPlek++;
			ObjetGlobal.animate({ left: cnt}, SettingsGlobal.easeTime, SettingsGlobal.easeFunc);
		}else{
			actualPlek = 1;
			setBgColorActual();
			ObjetGlobal.animate({ left: cnt}, 1, SettingsGlobal.easeFunc);
		}
		setBgColorActual();
		setTimeout("playAni()",miliSeconds);
	}
	
}

function setBgColorActual(){
	removeClassAll();
	jQuery("div#stripTransmitter0 a:eq("+(actualPlek)+")").addClass("current");
}


$(window).bind("load", function() {
	$("div#mygaltop").slideView({toolTip: true});	

// chili syntax highlighter
	$("code").chili();
	playAni();
});