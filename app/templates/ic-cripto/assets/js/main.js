$(function () {

    'use strict';
    $(document).ready(function () {
        // Mail Start Class Change
        'use strict';
        if ($('.d2c_mail_tab .btn').hasClass('active')) {
            $('.d2c_mail_tab .btn.active i').removeClass('far fa-star').addClass('fas fa-star');
        }
    });

    ('use strict');
    function d2c_theme_changer() {
        $('body').removeClass('d2c_theme_light d2c_theme_dark');
        $('body').addClass('d2c_theme_light');

        // Apply the cached theme and switch state on page load
        const theme = localStorage.getItem('theme');
        const theme_switch = localStorage.getItem('themeSwitch');

        if (theme) $('body').addClass(theme);
        if (theme_switch === 'true') $('#d2c_theme_changer').prop('checked', true);

        // Toggle theme and switch state on switch button change
        $('#d2c_theme_changer').change(function () {
            const isChecked = $(this).prop('checked');

            $('body').toggleClass('d2c_theme_dark', isChecked).toggleClass('d2c_theme_light', !isChecked);

            localStorage.setItem('theme', isChecked ? 'd2c_theme_dark' : 'd2c_theme_light');
            localStorage.setItem('themeSwitch', isChecked);
        });
    }
    d2c_theme_changer();

    //init
    $('.fileUploader').uploader({
        MessageAreaText: 'No files selected. Please select a file.',
    });
});

// bootstrap form validation
// Example starter JavaScript for disabling form submissions if there are invalid fields
(function () {
    'use strict';

    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.querySelectorAll('.needs-validation');

    // Loop over them and prevent submission
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener(
            'submit',
            function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                form.classList.add('was-validated');
            },
            false
        );
    });
})();

(function ($) {
    $.fn.uploader = function (options) {
        var settings = $.extend(
            {
                MessageAreaText: 'No files selected.',
                MessageAreaTextWithFiles: 'File List:',
                DefaultErrorMessage: 'Unable to open this file.',
                BadTypeErrorMessage: 'We cannot accept this file type at this time.',
                acceptedFileTypes: ['pdf', 'jpg', 'gif', 'jpeg', 'bmp', 'tif', 'tiff', 'png', 'xps', 'doc', 'docx', 'fax', 'wmp', 'ico', 'txt', 'cs', 'rtf', 'xls', 'xlsx'],
            },
            options
        );

        var uploadId = 1;
        //update the messaging
        $('.file-uploader__message-area p').text(options.MessageAreaText || settings.MessageAreaText);

        //create and add the file list and the hidden input list
        var fileList = $('<ul class="file-list"></ul>');
        var hiddenInputs = $('<div class="hidden-inputs hidden"></div>');
        $('.file-uploader__message-area').after(fileList);
        $('.file-list').after(hiddenInputs);

        //when choosing a file, add the name to the list and copy the file input into the hidden inputs
        $('.file-chooser__input').on('change', function () {
            var files = document.querySelector('.file-chooser__input').files;

            for (var i = 0; i < files.length; i++) {
                console.log(files[i]);

                var file = files[i];
                var fileName = file.name.match(/([^\\\/]+)$/)[0];

                //clear any error condition
                $('.file-chooser').removeClass('error');
                $('.error-message').remove();

                //validate the file
                var check = checkFile(fileName);
                if (check === 'valid') {
                    // move the 'real' one to hidden list
                    $('.hidden-inputs').append($('.file-chooser__input'));

                    //insert a clone after the hiddens (copy the event handlers too)
                    $('.file-chooser').append($('.file-chooser__input').clone({ withDataAndEvents: true }));

                    //add the name and a remove button to the file-list
                    $('.file-list').append('<li style="display: none;"><span class="file-list__name">' + fileName + '</span><button class="removal-button" data-uploadid="' + uploadId + '"></button></li>');
                    $('.file-list').find('li:last').show(800);

                    //removal button handler
                    $('.removal-button').on('click', function (e) {
                        e.preventDefault();

                        //remove the corresponding hidden input
                        $('.hidden-inputs input[data-uploadid="' + $(this).data('uploadid') + '"]').remove();

                        //remove the name from file-list that corresponds to the button clicked
                        $(this)
                            .parent()
                            .hide('puff')
                            .delay(10)
                            .queue(function () {
                                $(this).remove();
                            });

                        //if the list is now empty, change the text back
                        if ($('.file-list li').length === 0) {
                            $('.file-uploader__message-area').text(options.MessageAreaText || settings.MessageAreaText);
                        }
                    });

                    //so the event handler works on the new "real" one
                    $('.hidden-inputs .file-chooser__input').removeClass('file-chooser__input').attr('data-uploadId', uploadId);

                    //update the message area
                    $('.file-uploader__message-area').text(options.MessageAreaTextWithFiles || settings.MessageAreaTextWithFiles);

                    uploadId++;
                } else {
                    //indicate that the file is not ok
                    $('.file-chooser').addClass('error');
                    var errorText = options.DefaultErrorMessage || settings.DefaultErrorMessage;

                    if (check === 'badFileName') {
                        errorText = options.BadTypeErrorMessage || settings.BadTypeErrorMessage;
                    }

                    $('.file-chooser__input').after('<p class="error-message">' + errorText + '</p>');
                }
            }
        });

        var checkFile = function (fileName) {
            var accepted = 'invalid',
                acceptedFileTypes = this.acceptedFileTypes || settings.acceptedFileTypes,
                regex;

            for (var i = 0; i < acceptedFileTypes.length; i++) {
                regex = new RegExp('\\.' + acceptedFileTypes[i] + '$', 'i');

                if (regex.test(fileName)) {
                    accepted = 'valid';
                    break;
                } else {
                    accepted = 'badFileName';
                }
            }

            return accepted;
        };
    };
})($);

// countdown js
function countDown($days,$hour, $min, $sec, $deadLine) {
    // CountDown JS
    function timingCalc(endtime) {
        "use strict";
        var timeTotal = Date.parse(endtime) - Date.now(),
            timeSeconds = Math.floor((timeTotal / 1000) % 60),
            timeMinutes = Math.floor((timeTotal / 1000 / 60) % 60),
            timeHours = Math.floor((timeTotal / (1000 * 60 * 60)) % 24),
            timeDays = Math.floor(timeTotal / (1000 * 60 * 60 * 24));
        return {
            total: timeTotal,
            seconds: timeSeconds,
            minutes: timeMinutes,
            hours: timeHours,
            days: timeDays
        };
    }
    function animateCounter(selector, targetValue) {
        var $element = $(selector);
        var currentValue = parseInt($element.text(), 10);
        if (currentValue === targetValue) {
            return;
        }
        $element.addClass("counter-animate");
        $element.text(targetValue);
        setTimeout(function () {
            $element.addClass("show");
        }, 10);
        setTimeout(function () {
            $element.removeClass("counter-animate show");
        }, 500);
    }
    function startCalc(endtime) {
        var timeTotal = timingCalc(endtime);
        animateCounter($days, timeTotal.days);
        animateCounter($hour, timeTotal.hours);
        animateCounter($min, timeTotal.minutes);
        animateCounter($sec, timeTotal.seconds);
        if (timeTotal.total <= 0) {
            clearInterval(timingNow);
        }
    }
    // you can easily change the deadline 
    var DeadLine = new Date(Date.parse($deadLine));    
    setInterval(function () {
        startCalc(DeadLine);
    }, 1000);
}

countDown(".days",".hours", ".minutes", ".seconds", "30 june 2025 00:00:00 GMT+6");// upcoming nft card countdown
countDown(".days_1",".hours_1", ".minutes_1", ".seconds_1", "30 dec 2024 00:00:00 GMT+6");// upcoming nft card countdown

// preloader
$(window).on("load",function(){
  // Preloader Js
  $(".preloader").fadeOut(1000);
});

/* 
Template Name: IC Crypto - Free Bootstrap Crypto Dashboard Admin Template
Template URI:  https://www.designtocodes.com/product/ic-crypto-free-bootstrap-crypto-dashboard-admin-template
Description:   IC Crypto is an impressive and free crypto admin dashboard template that caters to the needs of cryptocurrency enthusiasts and professionals alike. Its well-designed interface, comprehensive features, and accessibility make it a strong contender as one of the best crypto dashboard templates available for download.
Author:        DesignToCodes
Author URI:    https://www.designtocodes.com
Text Domain:   IC Crypto
*/