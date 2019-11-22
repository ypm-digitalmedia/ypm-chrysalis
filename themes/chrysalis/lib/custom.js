var locked_card = null;
var fliplocks = {};
var fliplock = false;
var fliplockTimeout = null;


$(document).ready(function() {

    $(".box-item").each(function() {
        var card = $(this).attr("id");
        if ($(this).hasClass("noflip")) {
            fliplocks[card] = true;
        } else {
            fliplocks[card] = false;
        }
    });

    $(".box-item").mouseover(function() {
        if ($(this).hasClass('noflip')) {} else {
            fadeToBack($(this));
        }
    });


    $("body").on("tap", function(event) {

        var target = $(event.target);
        //        console.log( target.parents() )
        if (target.not(".card-row") || target.parents('.card-row').length < 1) {
            locked_card = null;
            fadeToFront();
        }

    });

    $("#closeSearchBox").click(function() {
        $(".ypm-search-container").slideUp();
    });

    $("#openSearchBox").click(function() {
        // $(".ypm-search-container").slideDown();
        $(".ypm-search-container").slideToggle();
    });

    $("#search-block-form button[type=submit]").removeClass("btn-primary button");


    // hotfix for Adminimal Theme Toolbar

    $("#toolbar-administration").css("font-family", "Open Sans");
    $("#toolbar-administration *").css("font-family", "Open Sans");

    $(".sitemap-parent-item").on("mouseover", function() {
        $(this).find("ul").css("border-left", "4px white solid");
        $(this).find("a:first").css("color", "#01356b").css("background-color", "white").css("padding", "0px 0.25em");
    }).on("mouseout", function() {
        $(this).find("ul").css("border-left", "none");
        $(this).find("a:first").css("color", "white").css("background-color", "#01356b").css("padding", "0px");
    });
	
	$(".copy-button").click(function() {
		var theId = $(this).attr('data-clipboard-target');
//			var el = document.getElementById(theId);
//			var range = document.createRange();
//			range.selectNodeContents(el);
//			var sel = window.getSelection();
//			sel.removeAllRanges();
//			sel.addRange(range);
//			document.execCommand('copy');
		
			copyToClipboard(theId);
		
			alert("Contents of " + theId + " copied to clipboard.");
			window.open("localhost:8080/card.html","_blank");
	});

});

// =================================== FADE ANIMATIONS - CURRENT =========================================

var fadeToBack = _.throttle(function(e) {

    var card = $(e).attr("id");

    if (locked_card == card) {
        // console.log("DENIED | locked card: " + card);
    } else {
        locked_card = card;
        // console.log("SWITCHING locked card: " + card);

        $(e).addClass("shadow");
        $(e).addClass("faded");
        $(e).find(".front").css("opacity", 0);
        $(e).find(".back").css("opacity", 1);

        $(".faded").not(e).find(".front").css("opacity", 1);
        $(".faded").not(e).find(".back").css("opacity", 0);
        $(".faded").not(e).removeClass("shadow");

    }

}, 100);

function fadeToFront() {
    $(".faded").find(".front").css("opacity", 1);
    $(".faded").find(".back").css("opacity", 0);
    $(".faded").removeClass("shadow");
}


// =============================================================================================