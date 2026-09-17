// JavaScript Document
  // <![CDATA[
$(document).ready(function() {
                $("a[@rel*=jquery-lightbox]").lightBox({
                    overlayOpacity: 0.6,
                    imageBlank: "/img/jquery-lightbox/lightbox-blank.gif",
                    imageLoading: "/img/jquery-lightbox/lightbox-ico-loading.gif",
                    imageBtnClose: "/img/jquery-lightbox/close.png",
                    imageBtnPrev: "/img/jquery-lightbox/goleft.png",
                    imageBtnNext: "/img/jquery-lightbox/goright.png",
                    containerResizeSpeed: 350
                });

                var etiquette_box = $("#addons-display-review-etiquette").hide();
                $("#short-review").focus(function() { etiquette_box.show("fast"); } );
            });

            // This function toggles an element's text between two values
            jQuery.fn.textToggle = function(text1, text2) {
                jQuery(this).text( ( jQuery(this).text() == text1 ? text2 : text1 ) );
            };

          // ]]>
          </script>  <script type="text/javascript">
  // <![CDATA[

    $(document).ready(function() {
        $(".hidden").hide(); // hide anything that should be hidden
        $("#other-apps").addClass("collapsed js"); // collapse other apps menu

        var q = $("#query");
        var l = $("#search-query label");
        l.show();
        if ( q.val() == "search for add-ons"){ //initially q is set to search add-ons text for javascriptless browsing
		  q.val('');
		}
        if ( q.val() != "") { // if field has any value...
            l.hide(); // hide the label
        };
        l.click(function() { // for browsers with unclickable labels
            q.focus();
        });
        q.focus(function() { // when field gains focus...
            l.hide(); // hide the label
        });
        q.blur(function() { // when field loses focus...
            if ( q.val() == "" ) { // if field is empty...
                l.show(); // show the label again, else do nothing (label remains hidden)
            };
        });
        
        // JS for toggling advanced versus normal search.
        var adv = $("#advanced-search");
        var advLink = $("#advanced-search-toggle a");
	      	advLink.isHidden = true;
        $('#advanced-search-toggle-link').attr('href', '#');   // for ie6-7				
        advLink.click(function() {           
            if(advLink.isHidden == true) {
               adv.appendTo("#search-form");
               advLink.addClass("asopen");
               advLink.removeClass("asclosed");
               advLink.isHidden = false;
            } else {
               adv.appendTo("#hidden-form");
               advLink.addClass("asclosed");
               advLink.removeClass("asopen");
               advLink.isHidden = true;
            }
            return false;
        }); 

        //JS for making sure advanced search type and normal search category don't conflict
        var cat = $("#category");								
        cat.change(function() {
	           atyp = document.getElementById('atype');							
            atyp.selectedIndex = 0;								
        });								

        var addontype = $("#atype");								
        addontype.change(function() {
	           ctgry = document.getElementById('category');							
            ctgry.selectedIndex = 0;								
        });
																        
        					
				

        $("#other-apps h3").click(function() {
            $("#other-apps").toggleClass("collapsed");
            $(this).blur();
            $(document).click(function(e) {
                // Prevent weird delay when clicking on the links
                var node = e.target;
                while (node && !node.id) {
                    node = node.offsetParent;
                }
                
                if (!node || node.id != 'other-apps') {
                    $("#other-apps").addClass("collapsed");
                }
            });
            return false;
        });
      
    }); // end dom ready
  
    // Without JS, content leaves space for the category menu.
    // With JS, the category menu collapses and the content spreads.
    $(document).ready(function() {
        $("#categories").addClass("collapsed"); // collapse categories menu
        $("#content-main").addClass("full"); // make the content spread to the full width
        $("#categories.collapsed h3").click(function() { 
            $("#cat-list").toggleClass("visible");
            $(this).toggleClass("open");
            $(document).click(function(e) {
                var node = e.target;
                while (node && !node.id) {
                    node = node.offsetParent;
                }
                if (!node || (node.id != 'categories' && node.id != 'cat-list')) {
                    $("#cat-list").removeClass("visible");
                    $("#categories.collapsed h3").removeClass("open");
                }
            });
        });
    });
