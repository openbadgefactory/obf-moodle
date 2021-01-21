/**
  * @module local_obf/chooseprogram
  */

 jQuery(document).ready(function($) {
    console.log("load");
    $("input[id^=id_completedby_]").prop('disabled', true);
   
    var val = $("input[id^=id_program_]:checked").prop('value');
    $("input[id^=id_completedby_" + val + "]").prop('disabled', false);

    $("input[id^=id_program_]").on('change', function() {
        
        var id = $(this).prop("id");
        var value = $(this).prop("value");

        $("input[id^=id_completedby_" + value + "]").prop('disabled', false);

        $("input[id^=id_program_]:checked").each(function () {
            if ($(this).prop("id") != id) {
                $(this).prop('checked', false);
                $("input[id^=id_completedby_" + $(this).prop("value") + "]").prop('disabled', true);
            }
        });
    });
 });