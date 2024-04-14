"use strict";
function removeAnswer(obj){
    $(obj).parent().parent().remove();
}

function addAnswer(q){
    var no_of_answers = $("#no_of_ans_"+q).val();
    no_of_answers = Number(no_of_answers)+Number(1);

   var  answer_structure = $("#answer_structure").html();
    answer_structure = answer_structure.replace(/a-no/g, no_of_answers);
    answer_structure = answer_structure.replace(/q-no/g, q);
    $("#answers_"+q).append(answer_structure);

    $("#no_of_ans_"+q).val(no_of_answers);
}

function addQuestion(){
    var answer_no = 1;
    var no_of_questions = $("#no_of_questions").val();
    no_of_questions = Number(no_of_questions)+Number(1);

  var  question_structure = $("#question_structure").html();
    question_structure = question_structure.replace(/a-no/g, answer_no);
    question_structure = question_structure.replace(/q-no/g, no_of_questions);
    $("#questions").append(question_structure);
    $("#no_of_questions").val(no_of_questions);


}


function slideToggleMainSection(obj,section,chkBox){
    var html = $(obj).html();
    var check = html.indexOf("fa-plus");
    if(check=="-1"){
        $(obj).html('<i class="fa fa-plus" title="Close" id="fa_plus_sign"></i>');
        $('.'+section).hide('slow');

    }else{
        $(obj).html('<i class="fa fa-minus" title="Open" id="fa_plus_sign"></i>');
        $('.'+section).show('slow');

    }
}

function switchTimeDropDown(obj){
    if($(obj).val()=='0'){
        $(obj).parent().find('.timeDropDown').css('display','none');
        $(obj).parent().find('.hoursDropDown').css('display','inline');
    }else{
        $(obj).parent().find('.timeDropDown').css('display','inline');
        $(obj).parent().find('.hoursDropDown').css('display','none');
    }
}
function slideToggleInnerSection(obj,eleMent){
    if($(obj).is(":checked")==true){
        $('.'+eleMent+'').show('slow');
    }else{
        $('.'+eleMent+'').hide('slow');
    }
}

function slideToggleBeaconSection(obj,eleMent){
    if(eleMent=='campaignBeaconCouponSection'){
        $('.campaignBeaconCouponSection').show('slow');
        $('.campaignBeaconURLSection').hide('slow');
    }else{
        $('.campaignBeaconCouponSection').hide('slow');
        $('.campaignBeaconURLSection').show('slow');
    }
}

function removeFollowUp(obj){
    if(confirm("Are you sure you want to remove this follow up?")){
        obj.closest('.delay_table').remove('slow');
    }
}
function followUpHtml(){
    var maxlenght = '100';
   var timeOption =  '<option value="00:00">12:00 AM</option><option value="00:15">12:15 AM</option><option value="00:30">12:30 AM</option><option value="00:45">12:45 AM</option><option value="01:00">01:00 AM</option><option value="01:15">01:15 AM</option><option value="01:30">01:30 AM</option><option value="01:45">01:45 AM</option><option value="02:00">02:00 AM</option><option value="02:15">02:15 AM</option><option value="02:30">02:30 AM</option><option value="02:45">02:45 AM</option><option value="03:00">03:00 AM</option><option value="03:15">03:15 AM</option><option value="03:30">03:30 AM</option><option value="03:45">03:45 AM</option><option value="04:00">04:00 AM</option><option value="04:15">04:15 AM</option><option value="04:30">04:30 AM</option><option value="04:45">04:45 AM</option><option value="05:00">05:00 AM</option><option value="05:15">05:15 AM</option><option value="05:30">05:30 AM</option><option value="05:45">05:45 AM</option><option value="06:00">06:00 AM</option><option value="06:15">06:15 AM</option><option value="06:30">06:30 AM</option><option value="06:45">06:45 AM</option><option value="07:00">07:00 AM</option><option value="07:15">07:15 AM</option><option value="07:30">07:30 AM</option><option value="07:45">07:45 AM</option><option value="08:00">08:00 AM</option><option value="08:15">08:15 AM</option><option value="08:30">08:30 AM</option><option value="08:45">08:45 AM</option><option value="09:00">09:00 AM</option><option value="09:15">09:15 AM</option><option value="09:30">09:30 AM</option><option value="09:45">09:45 AM</option><option value="10:00">10:00 AM</option><option value="10:15">10:15 AM</option><option value="10:30">10:30 AM</option><option value="10:45">10:45 AM</option><option value="11:00">11:00 AM</option><option value="11:15">11:15 AM</option><option value="11:30">11:30 AM</option><option value="11:45">11:45 AM</option><option value="12:00">12:00 PM</option><option value="12:15">12:15 PM</option><option value="12:30">12:30 PM</option><option value="12:45">12:45 PM</option><option value="13:00">01:00 PM</option><option value="13:15">01:15 PM</option><option value="13:30">01:30 PM</option><option value="13:45">01:45 PM</option><option value="14:00">02:00 PM</option><option value="14:15">02:15 PM</option><option value="14:30">02:30 PM</option><option value="14:45">02:45 PM</option><option value="15:00">03:00 PM</option><option value="15:15">03:15 PM</option><option value="15:30">03:30 PM</option><option value="15:45">03:45 PM</option><option value="16:00">04:00 PM</option><option value="16:15">04:15 PM</option><option value="16:30">04:30 PM</option><option value="16:45">04:45 PM</option><option value="17:00">05:00 PM</option><option value="17:15">05:15 PM</option><option value="17:30">05:30 PM</option><option value="17:45">05:45 PM</option><option value="18:00">06:00 PM</option><option value="18:15">06:15 PM</option><option value="18:30">06:30 PM</option><option value="18:45">06:45 PM</option><option value="19:00">07:00 PM</option><option value="19:15">07:15 PM</option><option value="19:30">07:30 PM</option><option value="19:45">07:45 PM</option><option value="20:00">08:00 PM</option><option value="20:15">08:15 PM</option><option value="20:30">08:30 PM</option><option value="20:45">08:45 PM</option><option value="21:00">09:00 PM</option><option value="21:15">09:15 PM</option><option value="21:30">09:30 PM</option><option value="21:45">09:45 PM</option><option value="22:00">10:00 PM</option><option value="22:15">10:15 PM</option><option value="22:30">10:30 PM</option><option value="22:45">10:45 PM</option><option value="23:00">11:00 PM</option><option value="23:15">11:15 PM</option><option value="23:30">11:30 PM</option><option value="23:45">11:45 PM</option>';
    var options = '<option value="+1 hour">After 1 Hour</option><option value="+2 hour">After 2 Hour</option><option value="+3 hour">After 3 Hour</option><option value="+4 hour">After 4 Hour</option><option value="+5 hour">After 5 Hour</option><option value="+6 hour">After 6 Hour</option><option value="+7 hour">After 7 Hour</option><option value="+8 hour">After 8 Hour</option><option value="+9 hour">After 9 Hour</option><option value="+10 hours">After 10 Hours</option><option value="+11 hours">After 11 Hours</option><option value="+12 hours">After 12 Hours</option><option value="+13 hours">After 13 Hours</option><option value="+14 hours">After 14 Hours</option><option value="+15 hours">After 15 Hours</option><option value="+16 hours">After 16 Hours</option><option value="+17 hours">After 17 Hours</option><option value="+18 hours">After 18 Hours</option><option value="+19 hours">After 19 Hours</option><option value="+20 hours">After 20 Hours</option><option value="+21 hours">After 21 Hours</option><option value="+22 hours">After 22 Hours</option><option value="+23 hours">After 23 Hours</option>';
    var html = '<table width="100%" class="delay_table">';
    html += '<tr><td colspan="2"><hr id="timeoptions"></td></tr>';
    html += '<tr><td width="25%">Select Days/Time</td><td><input type="text" class="form-control numericOnly" id="date_time" placeholder="Days delay..." name="delay_day[]" value="0" onblur="switchTimeDropDown(this)">&nbsp;<select class="form-control timeDropDown" id="timeDropDowns"  name="delay_time[]">' +timeOption+ '</select><select class="form-control hoursDropDown" id="hoursDropDown" name="delay_time_hours[]">'+options+'</select></td></tr>';
    html += '<tr><td>Message</td><td><textarea name="delay_message[]" class="form-control textCounter"></textarea><span class="showCounter"><span class="showCount"> '+maxLength+'</span> Characters left</span></td></tr>';
    html += '<tr><td>Attach Media</td><td><input type="file" name="delay_media[]" id="file"><span class="fa fa-trash" id="fa_trash" title="Remove Message" onclick="removeFollowUp(this)"></span></td></tr></table>';
    return html;
}
function addMoreFollowUpMsg(){
    var html = followUpHtml();
    $('#followUpContainer').append('<div>'+html+'</div>');
    $('.showCounter').hide();
}
