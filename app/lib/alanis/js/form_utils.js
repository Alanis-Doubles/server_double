function utils_disable_form() {
    // var form = $("form[name="+form_name+"]");
    var inputs = document.getElementsByTagName("input");
    for (var i = 0; i < inputs.length; i++) {
        var readonly =  inputs[i].readOnly;
        if (!readonly)
            inputs[i].disabled = true;
    }
    var selects = document.getElementsByTagName("select");
    for (var i = 0; i < selects.length; i++) {
        if (selects[i].form.id != 'search_box') {
            var readonly = selects[i].readOnly;
            if (!readonly)
                selects[i].disabled = true;
        }
    }
    var textareas = document.getElementsByTagName("textarea");
    for (var i = 0; i < textareas.length; i++) {
        var readonly = textareas[i].readOnly;
        if (!readonly)
            textareas[i].disabled = true;
    }
    var buttons = document.getElementsByTagName("button");
    for (var i = 0; i < buttons.length; i++) {
        if (!buttons[i].getAttribute('data-bb-handler') && !buttons[i].classList.contains('bootbox-close-button'))
            buttons[i].disabled = true;
    }
    var span = document.getElementsByTagName("span");
    for (var i = 0; i < span.length; i++) {
        if (span[i].classList.contains('btn'))
                span[i].style['pointer-events'] = 'none ';
    }
    var div = document.getElementsByTagName("div");
    for (var i = 0; i < div.length; i++) {
        if (div[i].classList.contains('btn'))
                div[i].style['pointer-events'] = 'none ';
    }
    var icon = document.getElementsByClassName("tfile_del_icon");
    for (var i = 0; i < icon.length; i++) {
        icon[i].setAttribute('hidden', '');
    }
}

function utils_hide_field(field, speed) {
    if (typeof speed == 'undefined') {
        $('#'+field).hide('fast');
    }
    else
    {
        $('#'+field).hide(speed);
    }
}

function utils_show_field(field, speed) {
    if (typeof speed == 'undefined') {
        $('#'+field).show('fast');
    }
    else
    {
        $('#'+field).show(speed);
    }
}

function utils_tentry_change_maxlen(form_name, field, maxlen)
{
    if(typeof form_name != 'undefined' && form_name != '') {
        form_name_sel = 'form[name="'+form_name+'"] ';
    }
    else {
        form_name_sel = '';
    }
    
    var selector = '[name="'+field+'"]';
    if (field.indexOf('[') == -1 && $('#'+field).length >0) {
        var selector = '#'+field;
    }
    
    $(document).ready(function(){
        $(selector).unmask();
        $(selector).attr('maxlength', maxlen);
    });

    // $('#' + field).unmask();
    // $('#' + fild).attr(maxlength=maxlen);
}