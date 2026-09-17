// JavaScript Document

var images = new Array();
function init( idCatalog, imagePreviewId ){
	catalogActual = document.getElementById( idCatalog );
//	catalogActual.innerHTML = '&nbsp;&nbsp;' + catalogActual.innerHTML ;
	childsLI = document.getElementById( idCatalog ).getElementsByTagName('li');
	for(i=0; i < childsLI.length; i++){
		actualElement = childsLI[i];

		//link = document.getElementById( idCatalog ).getElementsByTagName('a');
		link = actualElement.getElementsByTagName('a')
		link = link[0];
		//image = document.getElementById( idCatalog ).getElementsByTagName('img');
		link.target='_self';
		
		
		image = actualElement.getElementsByTagName('img');
		image = image[0];
		imageId = 'image_' + i;
		image.id = imageId;
		
		image.style.width = 0;
		image.style.height = 0;
		image.style.visibility = 'hidden';
		link.href= 'javascript:changeImage("' + imagePreviewId + '","'+ imageId +'");';
		
		separator = (i==0) ? '' : ' | ';
		
		addClassName(link, "classResultPages");
		link.innerHTML = link.innerHTML + separator + "<b>" + (i+1) +"</b>";
		
	}

}

function changeImage( idActualImg, nextImage ){

	nextImage = document.getElementById( nextImage );
	
	linkImagePreview = document.getElementById( idActualImg );
	imagePreview = linkImagePreview.getElementsByTagName('img');
	imagePreview = imagePreview[0];
	
	
	linkImagePreview.target='_blank';
	
	absImgSrc= nextImage.absSrc ? nextImage.absSrc : (nextImage.getAttribute ? nextImage.getAttribute('absSrc') : nextImage.src);
	
	linkImagePreview.href= absImgSrc;
	
	imagePreview.src= nextImage.previewSrc ? nextImage.previewSrc : (nextImage.getAttribute ? nextImage.getAttribute('previewSrc') : nextImage.src);
	
}



function loadLowSRC( imageId ){
	var img=document[ imageId ];
	img.imgRolln=img.src;
	img.src=img.lowsrc?img.lowsrc:img.getAttribute?img.getAttribute('lowsrc'):img.src;
}
function resetSRC( imageId ){
	document[ imageId ].src=document[ imageId ].imgRolln;
}
//--------------------------------------------------------------------------------
//--------------------------------------------------------------------------------
//--------------------------------------------------------------------------------																						
function addEventListener(element, eventType, handler, capture)
{
	try
	{
		if (element.addEventListener)
		{
			element.addEventListener(eventType, handler, capture);
		}
		else if (element.attachEvent)
		{
			element.attachEvent('on' + eventType, handler);
		}
	}
	catch (e) {}
}






function removeClass(ele, className){
	ele.className = ele.className.replace(new RegExp("\\s*\\b" + className + "\\b", "g"), "");
}

function addClassName(ele, className){
	ele.className += (ele.className ? " " : "") + className;
}



function changeImage2( idActualImg, absImgSrc, previewSrc, alt){

	linkImagePreview = document.getElementById( idActualImg );
	imagePreview = linkImagePreview.getElementsByTagName('img');
	imagePreview = imagePreview[0];
	linkImagePreview.target='_blank';
	linkImagePreview.alt=alt;
	linkImagePreview.title=alt;	
	
	linkImagePreview.href= absImgSrc;
	imagePreview.src= previewSrc;
	
}