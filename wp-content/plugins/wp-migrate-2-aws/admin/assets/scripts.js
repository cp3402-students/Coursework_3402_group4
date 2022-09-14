// Custom JS for WP on AWS
// console.log("In Custom Scripts - WPM-2-AWS");

jQuery.noConflict();


// Show-Hide sections (unused)
function wpm2awsEditSettings(sectionName) {
  var editSection = document.getElementById(
    "wpm2aws-" + sectionName + "-container"
  );
  editSection.style.display === "block"
    ? (editSection.style.display = "none")
    : (editSection.style.display = "block");
}

// Show-Hide Setion
function wpm2awsShowEditS3Section($) {
  $("#wpm2aws-edit-s3-notice-section").hide();

  $("#wpm2aws-edit-s3-inputs-section").show();
}


// function wpm2aws_getCurrentProgress($)
// {
//   xhttp = new XMLHttpRequest();
//   xhttp.onreadystatechange = function() {
//     if (this.readyState == 4 && this.status == 200) {
//     document.getElementById("wpm2aws-dynamic-output").innerHTML = this.responseText;
//     } else {
//       document.getElementById("wpm2aws-dynamic-output").innerHTML = 'Error!';
//     }
//   };
//   xhttp.open("GET", "admin.php?page=wpm2aws&action=wpm2aws-get-dynamic-progress", false);
//   xhttp.send();
// }

// function wpm2aws_getCurrentProgress($) {
//   // alert('in fn');
//    let result = $.ajax({
//       url: "admin.php?page=wpm2aws&action=wpm2aws-get-dynamic-progress",
//       type: 'GET',
//       dataType: 'json',
//       // data: data,
//       cache: false, // Appends _={timestamp} to the request query string
//       // success: "populateDynamicProgress"
//   }).done(
//     function() {
//       alert( "success" );
//     }
//   );

// };

function wpm2aws_getCurrentProgress($) {
    // Assign handlers immediately after making the request,
    // and remember the jqXHR object for this request
    var jqxhr = $.ajax({
        url:"admin.php?page=wpm2aws&action=wpm2aws-get-dynamic-progress",
        dataType:'json',
        cache:false,
    })
    .done(function(result) {
        //   console.log(result);
        //   console.log(result.progressComplete);
        //   console.log(result.maxTimeExceeded);
        //   console.log(result.process);

        if ('error' === result.progressComplete) {
            wpm2aws_changeProgressBarToWarning($);
        }

        if (true === result.maxTimeExceeded && progressComplete < 100) {
            // dynamic click re-start button
            // this refreshes the page
            wpm2aws_initiateProcessReRun($);
                // return;
        }

        if (typeof result.progressComplete !== 'undefined') {
            progressComplete = result.progressComplete;
            wpm2aws_updateProgressBar($, progressComplete);
        } else {
            wpm2aws_changeProgressBarToWarning($);
        }

    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        wpm2aws_logError($, 'Get Current Progress:Error', textStatus + ' | ' + errorThrown + ' | ' + JSON.stringify(jqXHR));
        if (0 !== jqXHR.status && '0' !== jqXHR.status) {
            console.log(JSON.stringify(jqXHR));

            var wpm2aws_seahorse_website_url = wpm2aws_script_defined_variables.wpm2aws_seahorse_website_url;
            alert('Error! An Error Has Occurred. Please Contact Seahorse @ "' + wpm2aws_seahorse_website_url + '/contact"');
        }
    });
    return;
}


function wpm2aws_updateProgressBar($, progressValue)
{
    // progressBar = $("#wpm2aws-progress-bar");
    let mainProgressBar = $("#wpm2aws-progress-bar-main");
    let sideProgressBar = $("#wpm2aws-progress-bar");

    let progressBars = [];

    if ('undefined' !== typeof mainProgressBar && mainProgressBar !== null && mainProgressBar.length > 0) {
        progressBars.push(mainProgressBar);
    }
    if ('undefined' !== typeof sideProgressBar && sideProgressBar !== null && sideProgressBar.length > 0) {
        progressBars.push(sideProgressBar);
    }

    $.each(progressBars, function(pbIX, pb){
        // console.log(pb);
        $(pb).data("progress", progressValue);
        $(pb).attr("data-progress", progressValue);
        $(pb).animate(
            {"width" : progressValue + "%"},
            1000,
            "linear",
            function () {
                $(this).after(
                    $(pb).text(progressValue + "%")
                );
            }
        );
    })
}

function wpm2aws_changeProgressBarToWarning($)
{
    progressBar = $("#wpm2aws-progress-bar");
    progressBarContainer = $(".wpm2aws-progress-bar-bg");
    currentProgressBarText = progressBar.text();
    progressBarContainer.css({"background-color" : "#ffb900"});

    if (currentProgressBarText.indexOf("Please Refresh this Page") < 0) {
        progressBar.css({"white-space" : "nowrap"});
        progressBar.text(currentProgressBarText + " - Refresh page to see progress")
    }
}

function wpm2aws_initiateProcessReRun($)
{
    reStartBtn = $("#wpm2aws-process-all-restart");
    if (typeof reStartBtn === 'undefined' || reStartBtn === null || reStartBtn.length === 0) {
        location.reload();
        return;
    }

    // Click the re-start button
    reStartBtn.click();
}

function wpm2aws_logError($, action, message)
{
    var wpm2aws_migrations_api_url = wpm2aws_script_defined_variables.wpm2aws_migrations_api_url;

    $.post(
        wpm2aws_migrations_api_url + "/api/migration/log/action",
        {
            "data":
            {
                "wpm2aws_site": location.host,
                "wpm2aws_action":action,
                "wpm2aws_message":message
            }
        }
    );

    return;
}

function wpm2aws_launchBuildProgress($)
{
    let progressBarContainer = $('#wpm2aws-launch-build-progress-bar');

    // If not on a page where the progress bar exists, then cancel this function
    if (progressBarContainer.length < 1) {
       return;
    }

    let buildCompleteBar = $('#wpm2aws-launch-build-completed');

    let totalTime = buildCompleteBar.data('totalTime');
    let timeRemaining = buildCompleteBar.data('remainingTime');

    let refreshInterval = 10;
    let timeOutInterval = (refreshInterval * 1000);

    let expendedTime = totalTime - timeRemaining;
    let expendedTimePercent = expendedTime / totalTime;
    let progressBarContainerWidth = progressBarContainer.width();
    let progressBarWidth = progressBarContainerWidth * expendedTimePercent;
    let progressBar = progressBarContainer.find('div.wpm2aws-launch-build-completed-progress-bar');
    let animationDuration = 1000;

    // Start the progress bar a little larger than non-existant
    if (expendedTimePercent < 0.02) {
        progressBarWidth = progressBarContainerWidth * 0.02;
    }

    progressBar.animate(
        {
            width: progressBarWidth
        },
        animationDuration,
        "linear"
    );

    timeRemaining -= refreshInterval;
    buildCompleteBar.data('remainingTime', timeRemaining);

    if (timeRemaining > 0) {
        $('.wpm2aws-launch-build-launch-button').hide();
        $('.wpm2aws-launch-build-notice').show();
        $('.wpm2aws-instance-launch-text-before-launch-complete').show();
        $('.wpm2aws-instance-launch-text-after-launch-complete').hide();
        $('#wpm2aws-summary-section button').prop('disabled', true);

        setTimeout(
            function() {
                wpm2aws_launchBuildProgress($);
            },
            timeOutInterval
        );
    } else {
        $('.wpm2aws-launch-build-launch-button').show();
        $('.wpm2aws-launch-build-notice').hide();
        $('.wpm2aws-instance-launch-text-before-launch-complete').hide();
        $('.wpm2aws-instance-launch-text-after-launch-complete').show();
        $('#wpm2aws-summary-section button').removeAttr('disabled');
    }
}

let progressBar;
let progressBarParent;
let reStartBtn;
let progressComplete;
let uploadSuccessNotice;
let zippedSuccessNotice;

// On load - bind Functions
jQuery(document).ready(function($) {

    $(".wpm2aws-view-more-button").click(function () {

        var viewMoreSection = $(this).data('viewSection');

        var viewMoreSectionId = "#wpm2aws-view-more-section-" + viewMoreSection;

        var text = $(this).text() === '(View Less)' ? '(View More)' : '(View Less)';
        $(this).text(text);

        $(viewMoreSectionId).toggleClass("wpm2aws-view-more-open", "");
    });

    // Confirmation dialog before reset
    $("#wpm2aws-reset-form").on('submit', function() {
        return confirm('Do you really want reset? Upon reset all progress will be lost.');
    });

    $(".wpm2aws-edit-inputs-button").on("click", function() {
        wpm2awsEditSettings($(this).attr("data"));
    });

    $("#wpm2aws-edit-s3-section-notice-button").on("click", function() {
        wpm2awsShowEditS3Section($);
    });

    $("#wpm2aws-show-re-start-btn").on("click", function() {
        $(this).hide();
        $(".wpm2aws-process-all-restart-button").show();
    });

    $(".wpm2aws-navigate-button").on("click", function() {
        // console.log($(this).data('wpm2aws-section'));
        let inputSection = $("#wpm2aws-input-section-" + $(this).data('wpm2aws-section'));
        if (typeof inputSection !== 'undefined' && inputSection !== null && inputSection.length > 0) {
            $(inputSection).toggle();
        }
    });

    $(".wpm2aws-run-migration-btn-container .wpm2aws-launch-aws-button").on("click", function() {
        let lightsailLoading = $(".wpm2aws_post_launch_loader");
        if (typeof lightsailLoading !== 'undefined' && lightsailLoading !== null && lightsailLoading.length > 0) {
            $(lightsailLoading).show();
        }
        $(".wpm2aws-run-migration-btn-container").hide();
    });


    $(".wpm2aws-download-database-form .wpm2aws-prepare-database-button").on("click", function() {
        let databasePreparing = $(".wpm2aws_prepare_database_loader");
        if (typeof databasePreparing !== 'undefined' && databasePreparing !== null && databasePreparing.length > 0) {
            $(databasePreparing).show();
        }
        $(".wpm2aws-prepare-database-btn-container").hide();
        $(".wpm2aws-download-database-form").hide();
    });


    progressBar = $("#wpm2aws-progress-bar");
    progressBarParent = $(progressBar).parent('div').parent('div').attr('id');
    // console.log(progressBarParent);
    if ('wpm2aws-fszip-results-container' === progressBarParent) {
        uploadSuccessNotice = $("#wpm2aws-fszip-success-notice");
    } else if ('wpm2aws-zipped-fs-upload-results-container' === progressBarParent) {
        uploadSuccessNotice = $("#wpm2aws-zipped-fs-upload-success-notice");
    } else {
        uploadSuccessNotice = null;
    }

    if (typeof progressBar !== 'undefined' && progressBar !== null && progressBar.length !== 0) {
        progressComplete = progressBar.data('progress');
        console.log("Progress Complete: " + progressComplete);
        console.log("Progress for Action: " + progressComplete);
        console.log("Progress for Action: " + progressBarParent);

        wpm2aws_getCurrentProgress($);

        let loop = setInterval(
            function(){
                wpm2aws_getCurrentProgress($);

                if (progressComplete >= 100) {
                    console.log("Greater than 100");

                    clearInterval(loop);

                    if (typeof uploadSuccessNotice === 'undefined' || uploadSuccessNotice === null || uploadSuccessNotice.length === 0) {
                        location.reload();
                    }

                }
            },
            2500
        );
    }

  // Show Progress Bar Dynamically
  $('#wpm2aws-get-dynamic-progress').on("click", function() {

    wpm2aws_getCurrentProgress($);

    let loop = setInterval(
      function(){
        wpm2aws_getCurrentProgress($);

        if (0 === counter) {
          clearInterval(loop);
        }
      },
      2500
    );
  });

    // Launch Build Progress Bar
    wpm2aws_launchBuildProgress($);
});
