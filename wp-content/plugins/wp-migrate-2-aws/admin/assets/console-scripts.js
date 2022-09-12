// Custom JS for Console Pages



// Show-Hide Add Alarms Setion
function wpm2awsToggleAddAlarmSection($) {
    $("#wpm2aws-add-new-metric-alarm-container").toggle();
  
    // $("#wpm2aws-edit-s3-inputs-section").show();
}

// Show-Hide Reboot Instance Confirmation buttons
function wpm2awsToggleRebootInstanceConfirmation($) {
    $("#wpm2aws-console-reboot-instance-confirmation-section").toggle();
}


// Show-Hide Create Instance Snapshot Confirmation buttons
function wpm2awsToggleCreateInstanceSnapshotConfirmation($) {
    $("#wpm2aws-console-create-instance-snapshot-confirmation-section").toggle();
}


// Show-Hide Change Instance Region Confirmation buttons
function wpm2awsToggleChangeInstanceRegionConfirmation($) {
    $("#wpm2aws-console-change-instance-region-confirmation-section").toggle();
}


// Show-Hide Change Instance Plan Confirmation buttons
function wpm2awsToggleChangeInstancePlanConfirmation($) {
    $("#wpm2aws-console-change-instance-plan-confirmation-section").toggle();
}



// On load - bind Functions
jQuery(document).ready(function($) {

    // Show Add New Alarm section
    $("#wpm2aws-add-new-metric-alarm-button").on("click", function() {
        wpm2awsToggleAddAlarmSection($);
    });

    // Hide Add New Alarm Section
    $("#wpm2aws-cancel-new-metric-alarm").on("click", function() {
        wpm2awsToggleAddAlarmSection($);
    });


    // Reboot Instance - Show Confirmation
    $("#wpm2aws-console-reboot-instance-button").on("click", function() {
        wpm2awsToggleRebootInstanceConfirmation($);
        $("#wpm2aws-console-reboot-instance-cross-check").val('reboot-ok');
        
    });

    // Reboot Instance - Hide Form on Confirm
    $("#wpm2aws-console-reboot-instance-confirm-button").on("click", function() {
        $(".wpm2aws-confimation-form-container").hide();
    });

    // Reboot Instance - Hide Confirmation
    $("#wpm2aws-console-reboot-instance-cancel-button").on("click", function() {
        $("#wpm2aws-console-reboot-instance-cross-check").val();
        wpm2awsToggleRebootInstanceConfirmation($);
    });



    // Snapshot Instance - Show Confirmation
    $("#wpm2aws-console-create-instance-snapshot-button").on("click", function() {
        wpm2awsToggleCreateInstanceSnapshotConfirmation($);
        $("#wpm2aws-console-create-instance-snapshot-cross-check").val('create-snapshot-ok');
        
    });

    // Snapshot Instance - Hide Form on Confirm
    $("#wpm2aws-console-create-instance-snapshot-confirm-button").on("click", function() {
        $(".wpm2aws-confimation-form-container").hide();
    });

    // Snapshot Instance - Hide Confirmation
    $("#wpm2aws-console-create-instance-snapshot-cancel-button").on("click", function() {
        $("#wpm2aws-console-create-instance-snapshot-cross-check").val();
        wpm2awsToggleCreateInstanceSnapshotConfirmation($);
    });



    // Change Instance Region - Show Confirmation
    $("#wpm2aws-console-change-instance-region-button").on("click", function() {
        wpm2awsToggleChangeInstanceRegionConfirmation($);
        $("#wpm2aws-console-change-instance-region-cross-check").val('change-region-ok');
    });

    // Change Instance Region - Hide Form on Confirm
    $("#wpm2aws-console-change-instance-region-confirm-button").on("click", function() {
        $(".wpm2aws-confimation-form-container").hide();
    });

    // Change Instance Region - Hide Confirmation
    $("#wpm2aws-console-change-instance-region-cancel-button").on("click", function() {
        $("#wpm2aws-console-change-instance-region-cross-check").val();
        wpm2awsToggleChangeInstanceRegionConfirmation($);
    });



    // Change Instance Plan - Show Confirmation
    $("#wpm2aws-console-change-instance-plan-button").on("click", function() {
        wpm2awsToggleChangeInstancePlanConfirmation($);
        $("#wpm2aws-console-change-instance-plan-cross-check").val('change-plan-ok');
    });

    // Change Instance Plan - Hide Form on Confirm
    $("#wpm2aws-console-change-instance-plan-confirm-button").on("click", function() {
        $(".wpm2aws-confimation-form-container").hide();
    });

    // Change Instance Plan - Hide Confirmation
    $("#wpm2aws-console-change-instance-plan-cancel-button").on("click", function() {
        $("#wpm2aws-console-change-instance-plan-cross-check").val();
        wpm2awsToggleChangeInstancePlanConfirmation($);
    });
}); // End bind on load