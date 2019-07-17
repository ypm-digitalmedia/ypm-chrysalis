var locked_card = null;
var fliplocks = {};
var fliplock = false;
var fliplockTimeout = null;


$(document).ready(function() {

    $(".box-item").each(function() {
        var card = $(this).attr("id");
        fliplocks[card] = false;
    });

    $(".box-item").mouseover(function() {
        fadeToBack($(this));
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


    // hotfix for Adminimal Theme Toolbar

    $("#toolbar-administration").css("font-family", "Open Sans");
    $("#toolbar-administration *").css("font-family", "Open Sans");
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