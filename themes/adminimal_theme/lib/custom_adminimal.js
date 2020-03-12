var $ = jQuery;

var customValidation = {
    // id substrings to search for
    "rules": {
        "time": ['field-time-slot'],
        "zip": [],
        "date": []
    },
    // contains elements which are searched for
    "targets": {
        "time": [],
        "zip": [],
        "date": []
    }
}




$(document).ready(function() {


    // customValidate();

    $("body").click(function(event) {
        if ($(event.target).hasClass("do-custom-format")) {
            customValidate();
        }
        if ($(event.target).hasClass("demo-button")) {
            var theVal = $(event.target).text().toLowerCase();
            var theEl = $(event.target).parent().prevAll().find("select[id*='button-style']").eq(0).attr("id");
            if (typeof theEl != undefined) {
                selectButtonStyle(theVal, theEl);
            }
            // console.log(theEl);
        }


        // console.log($(event.target));

    })

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


    // ADD CUSTOM STUFF HERE FOR TIME FIXER BUTTON


});

function doCustomValidate() {

    // time -
    // schedule time slots
    if (customValidation.targets.time.length) {
        for (var i = 0; i < customValidation.targets.time.length; i++) {
            var isSuccess = true;
            var timeString = $(customValidation.targets.time[i]).val();
            var parsedStrings = [];
            // check for time range (multiple times) in string
            if (timeString.indexOf("to") > -1) {
                parsedStrings = timeString.split("to");
                console.log("parsing multiple times entered: " + parsedStrings.join(', '));
            } else if (timeString.indexOf("-") > -1) {
                parsedStrings = timeString.split("-");
                console.log("parsing multiple times entered: " + parsedStrings.join(', '));
            } else if (timeString.indexOf("--") > -1) {
                parsedStrings = timeString.split("--");
                console.log("parsing multiple times entered: " + parsedStrings.join(', '));
            } else {
                // only one time in field 
                parsedStrings.push(timeString);
                console.log("parsing time entered: " + timeString);
            }
            if (parsedStrings.length > 2) { parsedStrings.length = 2; }

            var formattedStrings = [];
            for (var j = 0; j < parsedStrings.length; j++) {
                if (parsedStrings[j] == "noon") { parsedStrings[j] = "12:00"; }
                console.log(parsedStrings[j])
                if (parsedStrings[j].length > 0) {
                    var formatted = Date.parse(parsedStrings[j]).toString("h:mm tt");

                    // vanity formatting
                    // remove :00 from top-of-the-hour times
                    if (formatted.indexOf(":00") > -1) {
                        formatted = formatted.split(":00").join("");
                    }
                    console.log("formatting: " + parsedStrings[j] + " -> " + formatted);
                    formattedStrings.push(formatted);
                } else {
                    console.warn("invalid or missing string - cannot convert.");
                    isSuccess = false;
                }
            }
            // vanity formatting
            // only show AM/PM on second time, if two times present
            if (formattedStrings.length == 2) {
                if (formattedStrings[0].indexOf("AM") > -1 && formattedStrings[1].indexOf("AM") > -1) {
                    formattedStrings[0] = formattedStrings[0].split("AM").join("");
                }
                if (formattedStrings[0].indexOf("PM") > -1 && formattedStrings[1].indexOf("PM") > -1) {
                    formattedStrings[0] = formattedStrings[0].split("PM").join("");
                }
            }

            formattedStrings = formattedStrings.join("–");
            $(customValidation.targets.time[i]).val(formattedStrings);
            if (isSuccess) {
                $(customValidation.targets.time[i]).addClass("success-custom");
            } else {
                $(customValidation.targets.time[i]).addClass("error-custom");
            }
        }
    }
}

function selectButtonStyle(c, elid) {
    var select = document.getElementById(elid);
    for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].value == c) {
            select.options[i].selected = true;
        }
    }
}

function customValidate() {
    // ==========================================
    // test for custom validation form fields
    // ==========================================

    // reset arrays
    if (customValidation.targets.time.length) {
        customValidation.targets.time = [];
    }


    // schedule time slots
    for (var i = 0; i < customValidation.rules.time.length; i++) {
        var str = customValidation.rules.time[i];
        var test = $("input[id*='" + str + "']");
        if (test.length) {
            console.log("found elements for custom TIME validation - schedule time slot.");

            $("input[id*='" + str + "']").each(function() {
                var theId = $(this).attr("id").toString();
                if (theId.indexOf('first') > -1) {
                    customValidation.targets.time.push($(this));
                    console.log('added ' + theId + ' | TIME - schedule time slot');
                }
            });

            console.log(customValidation);
        } else {
            console.log("no elements for custom TIME validation - schedule time slot found.");
        }
    }





    doCustomValidate();
}