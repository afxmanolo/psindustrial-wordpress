// JavaScript Document

window.onload = function () {
	/* Initialise example scripts on content pages in all browsers */
if (self.init)
		init();
}

var W3CDOM = (document.createElement && document.getElementsByTagName);

function init() {
	if (!W3CDOM) return;
	var fakeFileUpload = document.createElement('div');
	fakeFileUpload.className = 'fakefile';
	elementInput = document.createElement('input');
	fakeFileUpload.appendChild(elementInput);
	
	var image = document.createElement('img');
	image.src='css/button_select.gif';
	fakeFileUpload.appendChild(image);
	var x = document.getElementsByTagName('input');
	for (var i=0;i<x.length;i++) {
		if (x[i].type != 'file') continue;
		if (x[i].getAttribute('noscript')) continue;

		if (x[i].parentNode.className != 'fileinputs') continue;
		x[i].className = 'file hidden';
		var clone = fakeFileUpload.cloneNode(true);
		x[i].parentNode.appendChild(clone);
		x[i].relatedElement = clone.getElementsByTagName('input')[0];
		if (x[i].value)
			x[i].onchange();
		x[i].onchange = x[i].onmouseout = function () {
			this.relatedElement.value = this.value;
		}
	}
}
