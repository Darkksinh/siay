jQuery(function ($) {
    $(document).ready(function () {

        //------------globals vars--------------------
        let isNewAccount = false;
        //--------------------------------------------

        function Validator(){};
        Validator.prototype.invalidValidate = function(fieldValue, validatorFunc, limitValue=null, callback=null){
            if(validatorFunc(fieldValue, limitValue)){
                if(!!callback) callback();
                return true;
            }

            return false;
        }

        Validator.prototype.minLenght = function(fieldValue, limitValue){
            return fieldValue.length <= limitValue;
        }

        Validator.prototype.maxLenght = function(fieldValue, limitValue){
            return fieldValue.length >= limitValue;
        }

        Validator.prototype.lenghtBetween = function(fieldValue, limitValue){
            return fieldValue.length < limitValue.min || fieldValue.length > limitValue.max;
        }

        Validator.prototype.empty = function(fieldValue){
            return fieldValue === '';
        }

        Validator.prototype.email = function(fieldValue){
            //let regex = /^((?!\.)[\w-_.]*[^.])(@\w+)(\.\w+(\.\w+)?[^.\W])$/i;
            //let regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/i;
            let regex = /^[a-z0-9!#$%&'"*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'"*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i;
            return !regex.test(fieldValue);
        }

        Validator.prototype.name = function(fieldValue){
            let regex = /^[a-zA-Z ,.'-]+$/;
            return !regex.test(fieldValue);
        }

        Validator.prototype.accountSuffix = function(fieldValue){
            let regex = /^.*-(srv[0-9]*|direct|cdn)$/i;
            return regex.test(fieldValue);
        }

        Validator.prototype.upperCase = function(fieldValue){
            let regex = /[A-Z]+/;
            return regex.test(fieldValue);
        }


        //add scrollable menu for help tab
        $(window).on('scroll', function () {
            if ($(window).scrollTop() > 170 && $(window).width() > 767) {
                $('.sirv-help-menu').addClass('sirv-help-menu-fixed');
                $('.sirv-help-data').addClass('sirv-help-data-margin');
            } else {
                $('.sirv-help-menu').removeClass('sirv-help-menu-fixed');
                $('.sirv-help-data').removeClass('sirv-help-data-margin');
            }
        });


        // Add smooth scrolling on all links inside the navbar
        $(".sirv-help-menu a").on('click', function (event) {
            // Make sure this.hash has a value before overriding default behavior
            if (this.hash !== "") {
                // Prevent default anchor click behavior
                event.preventDefault();

                // Store hash
                var hash = this.hash;

                // Using jQuery's animate() method to add smooth page scroll
                // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area
                $('html, body').animate({
                    scrollTop: $(hash).offset().top
                }, 800, function () {

                    // Add hash (#) to URL when done scrolling (default click behavior)
                    window.location.hash = hash;
                });
            } // End if
        });


        $('.nav-tab-wrapper > a').on('click', function (e) {
            changeTab(e, $(this));
        });
        $('.sirv-show-account-tab, .sirv-show-stats-tab').on('click', function (e) {
            changeTab(e, $('.nav-tab-sirv-account'));
        });


        function changeTab(e, $object) {
            if (!!e) e.preventDefault();
            $('.sirv-tab-content').removeClass('sirv-tab-content-active');
            $('.nav-tab-wrapper > a').removeClass('nav-tab-active');
            $('.sirv-tab-content' + $object.attr('href')).addClass('sirv-tab-content-active');
            $object.addClass('nav-tab-active').blur();
            $('#active_tab').val($object.attr('href'));
            //window.location.hash = $object.attr('href');
            window.location.hash = $object.attr('data-link');
        }


        function getTabFromUrlHash(){
            let urlHash = window.location.hash.replace('#', '');
            urlHash = urlHash.replace('sirv-', '');
            urlHash = urlHash == 'undefined' ? 'settings' : urlHash;
            if (!!urlHash) changeTab(undefined, $('.nav-tab-sirv-' + urlHash));
        }


        $('.sirv-toogle-pass').on('click', tooglePassVisibility);
        function tooglePassVisibility() {
            let $passInput = $('.sirv-pass');
            let $toggleIcon = $('.sirv-toogle-pass span.dashicons');
            let inputType = $passInput.attr('type');

            if (inputType == 'password') {
                $passInput.attr('type', 'text');
                $toggleIcon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $passInput.attr('type', 'password');
                $toggleIcon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
            //$passInput.focus();
            $passInput.trigger('focus');
        }


        $('.sirv-init').on('click', sirvInitAccount);
        function sirvInitAccount(){
            hideMessage('sirv-init-account', true);

            let name = $.trim($('input[name=SIRV_NAME]').val()).split(' ');

            let data = {};
            data['action'] = 'sirv_init_account';
            data['email'] = $.trim($('input[name=SIRV_EMAIL]').val());
            data['pass'] = $.trim($('input[name=SIRV_PASSWORD]').val());
            data['firstName'] = name[0] || '';
            data['lastName'] = name[1] || '';
            data['accountName'] = $.trim($('input[name=SIRV_ACCOUNT_NAME]').val());
            data['isNewAccount'] = $('input[name=SIRV_ACCOUNT_NAME]').is(':visible') ? 1 : 0;


            let validator = new Validator();

            if(validator.invalidValidate(data['email'], validator.empty)){
                showMessage('.sirv-error', 'Please specify email.', 'sirv-init-account');
                return;
            }

            if(validator.invalidValidate(data['pass'], validator.empty)){
                showMessage('.sirv-error', 'Please specify password.', 'sirv-init-account');
                return;
            }

            if(!!data['isNewAccount']){
                if(validator.invalidValidate(data['email'], validator.email)){
                    showMessage('.sirv-error', 'Please enter correct email.', 'sirv-init-account');
                    return;
                }

                if(validator.invalidValidate(data['pass'], validator.upperCase)){
                    showMessage('.sirv-error', 'Uppercase symbols does not permitted to use in the password.', 'sirv-init-account');
                    return;
                }

                if(validator.invalidValidate(data['pass'], validator.lenghtBetween, {min: 8, max: 128})){
                    showMessage('.sirv-error', 'Choose a password at least 8 characters long and less than 128 characters.', 'sirv-init-account');
                    return;
                }

                if(validator.invalidValidate(data['firstName'], validator.empty)){
                    showMessage('.sirv-error', 'Please specify your first name.', 'sirv-init-account');
                    return;
                }

                if(validator.invalidValidate(data['firstName'], validator.name)){
                    showMessage('.sirv-error', 'Please enter correct first name.', 'sirv-init-account');
                    return;
                }

                if(validator.invalidValidate(data['firstName'], validator.lenghtBetween, {min: 2, max: 35})){
                    showMessage('.sirv-error', 'First name must be 2-35 characters.', 'sirv-init-account');
                    return;
                }

                if(validator.invalidValidate(data['lastName'], validator.empty)){
                    showMessage('.sirv-error', 'Please specify your last name.', 'sirv-init-account');
                    return;
                }

                if(validator.invalidValidate(data['lastName'], validator.name)){
                    showMessage('.sirv-error', 'Please enter correct last name.', 'sirv-init-account');
                    return;
                }

                if(validator.invalidValidate(data['lastName'], validator.lenghtBetween, {min: 2, max: 35})){
                    showMessage('.sirv-error', 'Last name must be 2-35 characters.', 'sirv-init-account');
                    return;
                }

                if(validator.invalidValidate(data['accountName'], validator.empty)){
                    showMessage('.sirv-error', 'Please specify account name.', 'sirv-init-account');
                    return;
                }

                if(validator.invalidValidate(data['accountName'], validator.lenghtBetween, {min: 6, max: 30})){
                    showMessage('.sirv-error', 'Account name must be 6-30 characters. It may contain letters, numbers or hyphens (no spaces).', 'sirv-init-account');
                    return;
                }

                if(validator.invalidValidate(data['accountName'], validator.accountSuffix)){
                    let accName = data['accountName'];
                    showMessage('.sirv-error', 'Account name <b>' + accName + '</b> is not permitted. You could try <b>'+ accName.replace('-', '') +'</b> instead.', 'sirv-init-account');
                    return;
                }
            }

            $.ajax({
                url: ajaxurl,
                data: data,
                type: 'POST',
                dataType: "json",
                beforeSend: function (){
                    $('.sirv-connect-account-wrapper').addClass('sirv-loading');
                },
            }).done(function (res) {
                //debug
                //console.log(res);

                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');

                if(!!res && !!res.error){
                showMessage('.sirv-error', res.error, 'sirv-init-account');
                } else if(!!res && !!res.users){
                    let $button = $('input.sirv-init');
                    $button.removeClass('sirv-init');
                    $button.addClass('sirv-log-in');
                    $button.off('click');
                    $button.on('click', sirvLogin);

                    $('.sirv-select').show();
                    $('.sirv-field').hide();

                    for(let i in res.users){
                        $('select[name="sirv_account"]').append('<option value="' + res.users[i].token + '">' + res.users[i].alias + '</option>');
                    }

                    if (res.users.length == 1) {
                        $('select[name="sirv_account"] option').last().prop('selected', 'true').closest('select').trigger('change');
                        $('.sirv-log-in').trigger('click');
                    }
                }

            }).fail(function (jqXHR, status, error) {
                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');
                console.log("Error during ajax request: " + error);
                showMessage('.sirv-error', "Error during ajax request: " + error, 'sirv-init-account');
            });
        }


        function sirvLogin() {
            let data = {};

            data['action'] = 'sirv_setup_credentials';
            data['email'] = $('input[name=SIRV_EMAIL]').val();
            data['sirv_account'] = $('select[name="sirv_account"]').val();

            $.ajax({
                url: ajaxurl,
                data: data,
                type: 'POST',
                dataType: "json",
                beforeSend: function(){
                    $('.sirv-connect-account-wrapper').addClass('sirv-loading');
                },
            }).done(function (res) {
                //debug
                //console.log(res);

                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');

                if( !!res && !!res.error ){
                    showMessage('.sirv-error', res.error, 'sirv-init-account');
                }

                window.location.href = window.location.href.replace(/\#.*/i, '');

            }).fail(function (jqXHR, status, error) {
                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');
                console.log("Error during ajax request: " + error);
                showMessage('.sirv-error', "Error during ajax request: " + error, 'sirv-init-account');
            });
        }


        function showMessage(selector, message, msg_id, type='error'){
            $(selector).append('<div id="'+ msg_id +'" class="sirv-message '+ type +'-message">'+ message +'</div>');
        }


        function hideMessage(selector, removeAll=false) {
            if(removeAll){
                $('#' + selector).parent().empty();
            }else{
                $('#' + selector).remove();
            }
        }

        $('.sirv-disconnect').on('click', disconnectAccount);
        function disconnectAccount(event){
            event.preventDefault();

            $.ajax({
                url: ajaxurl,
                data: { action: 'sirv_disconnect'},
                type: 'POST',
                dataType: "json",
                beforeSend: function(){
                    $('.sirv-connect-account-wrapper').addClass('sirv-loading');
                },
            }).done(function (res){
                //debug
                //console.log(res);

                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');

                if (!!res && !!res.disconnected) {
                    window.location.href = window.location.href.replace(/\#.*/i, '');
                }

            }).fail(function(jqXHR, status, error){
                $('.sirv-connect-account-wrapper').removeClass('sirv-loading');
                console.log("Error during ajax request: " + error);
            });
        }


        $('.optimize-cache').on('click', optimizeCache);
        function optimizeCache(){
            let type = 'garbage';

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_clear_cache',
                    clean_cache_type: type,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    hideMessage('sirv-sync-message');
                    $('.sync-errors').hide();
                    $('.sirv-discontinued-images span.sirv-traffic-loading-ico').show();
                }
            }).done(function (data) {
                //debug
                //console.log(data);

                updateCacheInfo(data);
                showMessage('.sirv-sync-messages', getMessage(type), 'sirv-sync-message', 'ok');
                $('.sirv-discontinued-images span.sirv-traffic-loading-ico').hide();

            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error);
                showMessage('.sirv-sync-messages', "Error during ajax request: " + error, 'sirv-sync-message');
                $('.sirv-discontinued-images span.sirv-traffic-loading-ico').hide();
            });
        }


        $('.empty-cache').on('click', clearCache);
        function clearCache(){
            let type = $('input[name=empty_cache]:checked').val();

            if(type == 'all'){
                let confirmResult = confirm('This will clear the database cache of image locations. Continue?');
                if(!!confirmResult){
                    deleteCache(type);
                }
            }else{
                deleteCache(type);
            }
        }


        function deleteCache(cacheType){
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_clear_cache',
                    clean_cache_type: cacheType,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    hideMessage('sirv-sync-message');
                    $('.sync-errors').hide();
                    $('.sirv-resync-button-block span').show();
                }
            }).done(function (data) {
                //debug
                //console.log(data);

                updateCacheInfo(data);
                showMessage('.sirv-sync-messages', getMessage(cacheType), 'sirv-sync-message', 'ok');
                $('.sirv-resync-button-block span').hide();

            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error);
                showMessage('.sirv-sync-messages', "Error during ajax request: " + error, 'sirv-sync-message');
                $('.sirv-resync-button-block span').hide();
            });
        }


        function getMessage(type){
            let fMessage = 'Failed images have been deleted from Sirv cache';
            let aMessage = 'Sirv cache has been cleared';
            let mMessage = 'Your image cache has been cleared';
            let gMessage = 'Old images cleared from Sirv plugin cache';
            let message = '';

            switch (type) {
                case 'failed':
                    message = fMessage;
                    break;
                case 'all':
                    message = aMessage;
                    break;
                case 'master':
                    message = mMessage;
                    break;
                case 'garbage':
                    message = gMessage;
                    break;
                default:
                    break;
            }

            return message;
        }


        function updateReSyncBlockItems(data){
            if(!!data){
                let isFailed  = data.FAILED.count*1 > 0 ? true : false;
                let isGarbage = data.garbage_count*1 > 0 ? true : false;

                $('.sirv-old-cache-count').text(data.garbage_count);

                if (isGarbage){
                    $('.sirv-discontinued-images').show();
                }else{
                    $('.sirv-discontinued-images').hide();
                }

                if(isFailed){
                    $('.sirv-ec-failed-item input').prop('checked', true);
                    $('.sirv-ec-failed-item input').prop('disabled', false)
                    $('.sirv-ec-failed-item').removeClass('sirv-dis-text');
                }else{
                    $('.sirv-ec-failed-item input').prop('disabled', true);
                    $('.sirv-ec-failed-item').addClass('sirv-dis-text');
                    $('.sirv-ec-all-item input').prop('checked', true);
                }

                /* if(!isGarbage && !isFailed){
                    $('.sirv-ec-all-item input').attr('checked', true);
                    $('.sirv-ec-garbage-item input').attr('disabled', true);
                    $('.sirv-ec-failed-item input').attr('disabled', true);
                    $('.sirv-ec-garbage-item').addClass('sirv-dis-text');
                    $('.sirv-ec-failed-item').addClass('sirv-dis-text');
                }else if(isGarbage && isFailed){
                    $('.sirv-ec-garbage-item input').attr('checked', true);
                    $('.sirv-ec-garbage-item input').attr('disabled', false);
                    $('.sirv-ec-failed-item input').attr('disabled', false);
                    $('.sirv-ec-garbage-item').removeClass('sirv-dis-text');
                    $('.sirv-ec-failed-item').removeClass('sirv-dis-text');
                }else if(isGarbage && !isFailed){
                    $('.sirv-ec-garbage-item input').attr('checked', true);
                    $('.sirv-ec-garbage-item input').attr('disabled', false);
                    $('.sirv-ec-failed-item input').attr('disabled', true);
                    $('.sirv-ec-garbage-item').removeClass('sirv-dis-text');
                    $('.sirv-ec-failed-item').addClass('sirv-dis-text');
                }else if(!isGarbage && isFailed){
                    $('.sirv-ec-failed-item input').attr('checked', true);
                    $('.sirv-ec-failed-item input').attr('disabled', false)
                    $('.sirv-ec-garbage-item input').attr('disabled', true);
                    $('.sirv-ec-garbage-item').addClass('sirv-dis-text');
                    $('.sirv-ec-failed-item').removeClass('sirv-dis-text');
                } */

                //.sirv-ec-garbage-item
                //.sirv-ec-failed-item
                //.sirv-ec-all-item
                //.sirv-old-cache-count
            }
        }


        function updateCacheInfo(data){
            if(!!data){
                let isGarbage = data.garbage_count*1 > 0 ? true : false;
                if(data.q*1 > data.total_count*1){
                    data.q = isGarbage
                        ? data.q*1 - data.garbage_count*1 > data.total_count
                            ? data.total_count
                            : data.q * 1 - data.garbage_count * 1
                        : data.total_count;
                }

                $('.sirv-progress-data__complited--text').html(data.q_s);
                $('.sirv-progress-data__complited--size').html(data.size_s);
                $('.sirv-progress-data__queued--text').html(data.queued_s);
                $('.sirv-progress-data__failed--text').html(data.FAILED.count_s);

                $('.sirv-progress__text--percents').html(data.progress + '%');
                $('.sirv-progress__text--complited span').html(data.q_s + ' out of ' + data.total_count_s);

                $('.sirv-progress__bar--line-complited').css('width', data.progress_complited + '%');
                $('.sirv-progress__bar--line-queued').css('width', data.progress_queued + '%');
                $('.sirv-progress__bar--line-failed').css('width', data.progress_failed + '%');

                if(data.q*1 > 0){
                    $('.sirv-resync-block').show();
                }else{
                    $('.sirv-resync-block').hide();
                }

                if(data.FAILED.count*1 > 0){
                    $('.failed-images-block').show();
                    $('.failed-images-block a').show();
                }else{
                    $('.failed-images-block').hide();
                }

                if ( (data.q*1 + data.FAILED.count*1) == data.total_count*1) {
                    if (data.FAILED.count * 1 == 0) {
                        manageElement('input[name=sirv-sync-images]', disableFlag = true, text = '100% synced', button = true);
                    } else {
                        manageElement('input[name=sirv-sync-images]', disableFlag = true, text = 'Synced', button = true);
                    }
                }else{
                    manageElement('input[name=sirv-sync-images]', disableFlag = false, text = 'Sync images', button = true);
                }

                updateReSyncBlockItems(data);
            }

        }


        //send the message from plugin to support@sirv.com
        $('#send-email-to-sirv').on('click', function () {
            //fields
            let name = $('#sirv-writer-name').val();
            let contactEmail = $('#sirv-writer-contact-email').val();
            //let priority = $('#sirv-priority').val();
            let summary = $('#sirv-summary').val();
            let messageText = $('#sirv-text').val();

            //messages;
            let proccessingSendMessage = '<span class="sirv-traffic-loading-ico sirv-no-lmargin"></span> Sending message. This may take some time...';
            let messageSent = 'Your message has been sent.';
            let ajaxError = 'Error during AJAX request. Please try to send the message again or use the <a target="_blank" href="https://sirv.com/contact/">Sirv contact form here</a> Error: <br/>';
            let sendingError = 'Something went wrong. The most likely reason is that Sendmail is not installed/configured. Please try to send the message again or use the <a target="_blank" href="https://sirv.com/contact/">Sirv contact form here</a>';
            //form messages
            let emptyFields = '<span style="color: red;">Please fill form fields.</span>';
            let incorrectEmail = '<span style="color: red;">Email is incorrect. Please check email field.</span>';

            let generatedViaWP = '\n\n\n---\nThis message was sent from the Sirv WordPress plugin form at ' + document.location.hostname;


            let formMessages = '';

            if (summary == '' || messageText == '' || name == '' || contactEmail == '') {
                formMessages += emptyFields + '<br />';
            }

            if (contactEmail.match(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,3}$/i) == null && contactEmail != '') {
                formMessages += incorrectEmail + '<br />';
            }

            if (formMessages != '') {
                $('.sirv-show-result').html(formMessages);
                return false;
            }

            $.post(ajaxurl, {
                action: 'sirv_send_message',
                name: name,
                emailFrom: contactEmail,
                //priority: priority + ' (via WP)',
                summary: summary,
                text: 'Contact name: ' + name + '\n' + 'Contact Email: ' + contactEmail + '\n\n' + messageText + generatedViaWP,
                beforeSend: function () {
                    $('.sirv-show-result').html(proccessingSendMessage);
                }
            }).done(function (data) {
                //debug
                //console.log(data);

                if (data == '1') {
                    $('.sirv-show-result').html(messageSent);
                } else {
                    $('.sirv-show-result').html(sendingError);
                }

                //clear contact form fields
                $('#sirv-writer-name').val('');
                $('#sirv-writer-contact-email').val('');
                $('#sirv-summary').val('');
                $('#sirv-text').val('');

            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error);
                $('.sirv-show-result').html(ajaxError + error);
            });

        });


        //sanitize folder name on sirv
        $('#sirv-save-options').submit(function () {
            let folderOnSirv = $("[name='SIRV_FOLDER']").val();
            let sanitizedFolderOnSirv = folderOnSirv == '' ? 'WP_SirvMediaLibrary' : folderOnSirv.replace(/^[\/]*(.*?)[\/]*$/ig, '$1');
            $("[name='SIRV_FOLDER']").val(sanitizedFolderOnSirv);

            return true;
        });


        $('#SIRV_USE_SIRV_RESPONSIVE').on('change', showResponsiveWarning);
        function showResponsiveWarning(){
            let $checkbox = $('#SIRV_USE_SIRV_RESPONSIVE');
            let $msg = $('.sirv-responsive-msg');

            if($checkbox.is(":checked")){
                $msg.slideDown();
            }else{
                $msg.slideUp();
            }
        }


        $('.sirv-option-edit').on('click', optionEdit);
        function optionEdit(e){
            e.preventDefault();

            $p = $(this).parent();
            $p.hide();
            $p.next('p').show();
        }


        $('.tst').on('click', tst);
        function tst() {
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_tst',
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {

                },
            }).done(function (data) {
                //debug
                console.log(data);

            }).fail(function (jqXHR, status, error) {
                console.error("Error during ajax request: " + error);
            });
        }


        $('.sirv-initialize-sync').on('click', initializeMassSync);
        function initializeMassSync(){
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_initialize_process_sync_images',
                    sirv_initialize_sync: true,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    $('.sirv-sync-messages').empty();
                    manageElement('input[name=sirv-sync-images]', disableFlag = true, text = 'Syncing', button = true);
                    $('.sirv-progress__bar--line-complited').addClass('sirv-progress-bar-animated');
                    $('.sirv-queue').html('Processing (1/3): calculating folders...');
                    $('.sirv-processing-message').show();
                    $('.sync-errors').hide();
                    $('.sirv-resync-block').hide();
                    $('.failed-images-block').hide();
                    $('.failed-images-block a').hide();
                },
            }).done(function (data) {
                //debug
                //console.log(data);
                if (!!data && data.folders_calc_finished){
                    $('.sirv-queue').html('Processing (2/3): calculating images in queue...');
                    massSyncImages();
                }else{
                    showMessage('.sirv-sync-messages', "Please reload this page and try again.", 'sirv-sync-message', 'warning');
                    $('.sirv-processing-message').hide();
                    $('.sirv-progress__bar--line-complited').removeClass('sirv-progress-bar-animated');
                    manageElement('input[name=sirv-sync-images]', disableFlag = false, text = 'Sync images', button = true);
                }

            }).fail(function (jqXHR, status, error) {
                console.error("Error during ajax request: " + error);
                showMessage('.sirv-sync-messages', "Error during ajax request: " + error, 'sirv-sync-message');
                showMessage('.sirv-sync-messages', "Please reload this page and try again.", 'sirv-sync-message', 'warning');
                $('.sirv-processing-message').hide();
                $('.sirv-progress__bar--line-complited').removeClass('sirv-progress-bar-animated');
                manageElement('input[name=sirv-sync-images]', disableFlag = false, text = 'Sync images', button = true);

            });
        }


        $('.sirv-sync-images').on('click', massSyncImages);
        function massSyncImages(){
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_process_sync_images',
                    sirv_sync_uncached_images: true,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function(){
                    /* $('.sirv-sync-messages').empty();
                    manageElement('input[name=sirv-sync-images]', disableFlag = true, text = 'Syncing', button = true);
                    $('.sirv-progress__bar--line').addClass('sirv-progress-bar-animated');
                    $('.sirv-processing-message').show();
                    $('.sync-errors').hide();
                    $('.sirv-resync-block').hide();
                    $('.failed-images-count-row a').hide(); */
                },
            }).done(function(data){
                //debug
                //console.log(data);

                if (!!data) {
                    $('.sirv-progress-data__complited--text').html(data.q_s);
                    $('.sirv-progress-data__complited--size').html(data.size_s);
                    $('.sirv-progress-data__queued--text').html(data.queued_s);
                    $('.sirv-progress-data__failed--text').html(data.FAILED.count_s);

                    $('.sirv-progress__text--percents').html(data.progress + '%');
                    $('.sirv-progress__text--complited span').html(data.q_s + ' out of ' + data.total_count_s);

                    $('.sirv-progress__bar--line-complited').css('width', data.progress_complited + '%');
                    $('.sirv-progress__bar--line-queued').css('width', data.progress_queued + '%');
                    $('.sirv-progress__bar--line-failed').css('width', data.progress_failed + '%');

                    //let processing_q = data.total_count * 1 - data.q * 1 - data.FAILED.count * 1;
                    //$('.sirv-queue').html('Images in queue: ' + processing_q);
                    $('.sirv-queue').html('Processing (3/3): syncing images...');

                    if (data.FAILED.count) {
                        $('.failed-images-block').show();
                        $('.failed-images-block a').show();
                    }

                    if (!!data.status && data.status.isStopSync){
                        manageElement('input[name=sirv-sync-images]', disableFlag = true, text = 'Can\'t sync', button = true);
                        showMessage('.sirv-sync-messages', data.status.errorMsg, 'sirv-sync-message');
                        $('.sirv-processing-message').hide();
                        $('.sirv-progress__bar--line-complited').removeClass('sirv-progress-bar-animated');
                        return;
                    }

                    if ((data.q * 1 + data.FAILED.count * 1 ) < data.total_count*1) {
                        massSyncImages();
                    } else {
                        $('.sirv-progress__bar--line-complited').removeClass('sirv-progress-bar-animated');
                        $('.sirv-processing-message').hide();
                        if (data.FAILED.count * 1 == 0){
                            manageElement('input[name=sirv-sync-images]', disableFlag = true, text = '100% synced', button = true);
                        }else{
                            manageElement('input[name=sirv-sync-images]', disableFlag = true, text = 'Synced', button = true);
                        }
                        updateReSyncBlockItems(data);
                        $('.sirv-resync-block').show();
                    }
                }
            }).fail(function(jqXHR, status, error){
                console.error("Error during ajax request: " + error);
                showMessage('.sirv-sync-messages', "Error during ajax request: " + error, 'sirv-sync-message');
                showMessage('.sirv-sync-messages', "Please reload this page and try again.", 'sirv-sync-message', 'warning');
                $('.sirv-processing-message').hide();
                $('.sirv-progress__bar--line-complited').removeClass('sirv-progress-bar-animated');
                manageElement('input[name=sirv-sync-images]', disableFlag = false, text = 'Sync images', button = true);

            });
        }


        function get_formated_num(num){
            return new Intl.NumberFormat('en-IN').format(num);
        }


        $('.sirv-get-error-data').on('click', getErrorData);
        function getErrorData(e){
            e.preventDefault();
            e.stopPropagation();


            let $link = $(this);
            let reportType = $link.attr('data-type');
            let errorId = $link.attr('data-error-id');
            let $ajaxAnimation = $(this).next();

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_get_error_data',
                    error_id: errorId,
                    report_type: reportType,
                },
                type: 'POST',
                dataType: 'text',
                beforeSend: function () {
                    $ajaxAnimation.show();
                    $link.text('Generating...');
                },
            }).done(function (data) {
                //debug
                //console.log(data);

                $ajaxAnimation.hide();

                if (!!data) {
                    if(reportType == 'html'){
                        $link.text('Open HTML report');
                        $link.attr('href', data);
                        $link.attr('target', '_blank');
                        $('.sirv-get-error-data:contains(Open HTML report)').off('click', getErrorData);
                        window.open($link.attr('href'));
                    }else{
                        $link.text('CSV downloaded');
                        let filename = 'failed_images_error_'+ errorId + '.csv';
                        $('.sirv-get-error-data:contains(CSV downloaded)').off('click', getErrorData);
                        let a = document.createElement('a');
                        /* let url = window.URL.createObjectURL(data);
                        a.href = url;
                        a.download = 'failed_images_list.txt';
                        a.style.display = 'none';
                        document.body.append(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url); */
                        a.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(data));
                        a.setAttribute('download', filename);

                        a.style.display = 'none';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }

                }
            }).fail(function (jqXHR, status, error) {
                $ajaxAnimation.hide();
                $link.text('An error occurred');
                console.error("Error during ajax request: " + error);
                showMessage('.sirv-sync-messages', "Error during ajax request: " + error, 'sirv-get-failed-message');
            });
        }

        $('.failed-images-block a').on('click', getErrorsData);
        function getErrorsData(e){
            e.preventDefault();

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_get_errors_info',
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    $('.failed-images-block span.sirv-traffic-loading-ico').show();
                    $('.failed-images-block a').hide();
                    $('.sirv-fetch-errors').empty();
                    $('.sirv-get-error-data').off('click', getErrorData);
                },
            }).done(function (data) {
                //debug
                //console.log(data);

                if (!!data) {
                    let documentFragment = $(document.createDocumentFragment());
                    for (let i in data) {
                        if(data[i]['count'] > 0){
                            let solution = !!data[i]['error_desc'] ? 'Solution: ' + data[i]['error_desc'] : '';
                            documentFragment.append('<tr><td><span class="sirv-error-title">' + i + '</span><br><span class="sirv-error-solution">'+ solution + '</span></td><td>' + data[i]['count'] + '</td>'+
                            '<td><a class="sirv-get-error-data" data-type="html" data-error-id="' + data[i]['error_id'] + '" href="#">Generate HTML report</a>' +
                            ' <span class="sirv-traffic-loading-ico sirv-get-fimages-list" style="display:none;"></span><br/>' +
                            '<a class="sirv-get-error-data" data-type="csv" data-error-id="' + data[i]['error_id'] + '" href="#">Download CSV</a>' +
                            ' <span class="sirv-traffic-loading-ico sirv-get-fimages-list" style="display:none;"></span>' +
                            '</td></tr>');
                        }
                    }

                    $('.sirv-fetch-errors').append(documentFragment);
                    $('.sirv-get-error-data').on('click', getErrorData);
                    $('.failed-images-block span.sirv-traffic-loading-ico').hide();
                    $('.sync-errors').show();
                }
            }).fail(function (jqXHR, status, error) {
                console.error("Error during ajax request: " + error);
                showMessage('.sirv-sync-messages', "Error during ajax request: " + error, 'sirv-get-failed-message');
            });
        }


        $('.sirv-crop-radio').on('change', onCropChange);
        function onCropChange(e){
            e.preventDefault();
            e.stopPropagation();

            let $dataObj = $('#sirv-crop-sizes');
            let data = JSON.parse($dataObj.val());
            let $rObj = $(this);
            if(!!data){
                data[$rObj.attr('name')] = $rObj.val();
                $dataObj.val(JSON.stringify(data));
            }
        }


        $('.sirv-hide-show-a').on('click', toogleBlockVisivility);
        function toogleBlockVisivility(e){
            e.preventDefault();
            e.stopPropagation();

            let showMsgTxt = $(this).attr('data-show-msg');
            let hideMsgTxt = $(this).attr('data-hide-msg');
            let showIconCls = $(this).attr('data-icon-show') || '';
            let hideIconCls = $(this).attr('data-icon-hide') || '';
            let status = $(this).attr('data-status') == 'true';

            let showMsg = showIconCls ? '<span class="'+ showIconCls +'"></span>' + showMsgTxt : showMsgTxt;
            let hideMsg = hideIconCls ? '<span class="'+ hideIconCls +'"></span>' + hideMsgTxt : hideMsgTxt;

            let selector = $(this).attr('data-selector');

            if (status){
                $(selector).slideUp();
                $(this).html(showMsg);
            }else{
                $(selector).slideDown();
                $(this).html(hideMsg)
            }

            $(this).attr('data-status', !status);
        }

        $('.sirv-clear-view-cache').on('click', emptyViewCache);
        function emptyViewCache(){
            let type = $('input:radio[name=empty-view-cache]:checked').val();

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_empty_view_cache',
                    type: type,
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function () {
                    //hideMessage('sirv-sync-message');
                    $('.sirv-clear-view-cache').siblings('span.sirv-show-empty-view-result').text('');
                    $('.sirv-clear-view-cache').siblings('span.sirv-show-empty-view-result').hide();
                    $('.sirv-clear-view-cache').siblings('span.sirv-traffic-loading-ico').show();
                }
            }).done(function (data) {
                //debug
                //console.log(data);

                //showMessage('.sirv-sync-messages', getMessage(type), 'sirv-sync-message', 'ok');
                $('.sirv-clear-view-cache').siblings('span.sirv-traffic-loading-ico').hide();

                if(!!data){
                    if (!!data.result && Number.isInteger(data.result)){
                        let msg = (data.result / 2) + ' items deleted';
                        $('.sirv-clear-view-cache').siblings('span.sirv-show-empty-view-result').text(msg);
                        $('.sirv-clear-view-cache').siblings('span.sirv-show-empty-view-result').show();

                        setTimeout(function () { $('.sirv-clear-view-cache').siblings('span.sirv-show-empty-view-result').hide();}, 3000);
                    }

                    if (!!data.cache_data && typeof data.cache_data == 'object'){
                        let cacheData = data.cache_data;

                        for(let type in cacheData){
                            $('.empty-view-cache-'+ type).text(cacheData[type]);
                        }
                    }

                }

            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error);
                //showMessage('.sirv-sync-messages', "Error during ajax request: " + error, 'sirv-sync-message');
                $('.sirv-clear-view-cache').siblings('span.sirv-traffic-loading-ico').hide();
                $('.sirv-clear-view-cache').siblings('span.sirv-show-empty-view-result').text("Error during ajax request: " + error);
                $('.sirv-clear-view-cache').siblings('span.sirv-show-empty-view-result').show();
            });
        }


        function isJsonString(str) {
            try {
                JSON.parse(str);
            } catch (e) {
                return false;
            }
            return true;
        }

        $('.sirv-stat-refresh').on('click', refreshStats);
        function refreshStats(e){
            e.preventDefault();

            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'sirv_refresh_stats',
                },
                type: 'POST',
                dataType: "json",
                beforeSend: function (){
                    $('.sirv-stats-messages').empty();
                    $('.sirv-stats-container').addClass('sirv-loading');
                },
            }).done(function (data) {
                //debug
                //console.log(data);

                $('.sirv-stats-container').removeClass('sirv-loading');
                if (!!data) {
                    window.abc = data.traffic.traffic;
                    $('.sirv-stat-last-update').html(data.lastUpdate);
                    renderStorage(data.storage);
                    renderTransfer(data.traffic);
                    renderApiUsage(data.limits);
                }
            }).fail(function (jqXHR, status, error) {
                console.error("Error during ajax request: " + error);
                showMessage('.sirv-stats-messages', "Error during ajax request: " + error, 'sirv-get-failed-message');
                $('.sirv-stats-container').removeClass('sirv-loading');
            });
        }


        function renderStorage(storage){
            if(!!storage){
                $('.sirv-allowance').html(storage.allowance_text);
                $('.sirv-st-used').html(storage.used_text + '<span> (' + storage.used_percent + '%)</span>');
                $('.sirv-st-available').html(storage.available_text + '<span> (' + storage.available_percent + '%)</span>');
                $('.sirv-st-files').html(storage.files);
            }
        }


        function renderTransfer(traffic){
            if(!!traffic){
                $('.sirv-trf-month').html(traffic.allowance_text);

                let traffics = sortTraffic(traffic.traffic);

                let trafficFragment = $(document.createDocumentFragment());


                for (let i in traffics) {
                    let tr = $('<tr class="small-padding">' +
                                '<th><label>' + traffics[i].month + '</label></th>'+
                                '<td><span>' + traffics[i].size_text + '</span></td>'+
                                '<td>'+
                                    '<div class="sirv-progress-bar-holder"><div class="sirv-progress-bar"><div>'+
                                        '<div style = "width:' + traffics[i].percent_reverse + '%;"></div>' +
                                    '</div></div></div>'+
                                '</td>'+
                            '</tr>'
                            );
                    trafficFragment.append(tr);
                }

                let $trafficRows = $('.traffic-wrapper').children();
                for (let i = 1; i < $trafficRows.length; i++) {
                    $trafficRows[i].remove();
                }

                $($trafficRows[0]).after(trafficFragment);
            }
        }


        function sortTraffic(traffic){
            let keys = Object.keys(traffic);
            let tmpArr = [];

            for (let index in keys) {
                let key = keys[index];
                traffic[key]['month'] = key;
                tmpArr.push(traffic[key]);
            }

            tmpArr.sort(function (a, b) {
                return a.order - b.order
            })

            return tmpArr;
        }


        function renderApiUsage(limits){

            if(!!limits){
                let apiFragment = $(document.createDocumentFragment());
                let apiTypes = Object.keys(limits);

                for (let i in apiTypes) {
                    let limit = limits[apiTypes[i]];

                    let count = limit.count > 0 ? limit.count : '-';
                    let used = limit.count > 0 ? ' (' + limit.used + ')' : '';
                    //let reset = limit.count > 0 ? limit.reset_str + ' <span class="sirv-limits-reset-local">(' + renderTime(limit.reset_timestamp) + ')</span>' : '-';
                    let reset = limit.count > 0 ? limit.count_reset_str + ' <span class="sirv-grey">(' + limit.reset_str + ')</span>' : '-';
                    let isLimitReached = limit.count >=limit.limit ? 'style="color: red;"' : '';

                    let limitRow = $('<tr '+ isLimitReached +'>\n<td>' +
                                    limit.type + '</td>\n<td>' +
                                    limit.limit + '</td>\n<td>'+
                        count + used +'</td>\n<td><span class="sirv-limits-reset" data-timestamp="'+ limit.reset_timestamp+'" >' + reset + '</span></td></tr>\n');
                    apiFragment.append(limitRow);
                }
                $('.sirv-api-usage-content').empty();
                $('.sirv-api-usage-content').append(apiFragment);

            }
        }


        function setCorrectTime(){
            let $times = $('.sirv-limits-reset');
            $.each($times, function(index, elem){
                let el = $(elem);
                let timestamp = el.attr('data-timestamp');
                el.html(el.text() + ' <span class="sirv-limits-reset-local">(' + renderTime(timestamp) + ')</span>');
            });
        }


        function renderTime(timestamp){
            let jsTimeStamp = timestamp * 1000;
            let d = new Date(jsTimeStamp);

            let h = d.getHours();
            let m = d.getMinutes();
            let s = d.getSeconds();
            return zeroInTime(h) + ':' + zeroInTime(m) + ':' + zeroInTime(s) + ' ' + convertTimeZoneOffset(d.getTimezoneOffset());
        }


        function zeroInTime(t){
            let ct = t < 10 ? '0' + t : t;
            return ct;
        }


        function convertTimeZoneOffset(offset){
            let timeOffset = Math.abs(offset);
            timeOffset = timeOffset > 0 ? timeOffset / 60 : timeOffset;

            return '+0' + timeOffset + ':00';
        }


        function toogleList() {
            if ($('.sirv-error-list-images-wrap').is(":visible")) {
                $('.sirv-error-list-images-wrap').hide();
            } else {
                $('.sirv-error-list-images-wrap').show();
            }
        }


        function resetConnectForm(){

        }


        function ajaxRequest(ajaxurl, data, type = 'POST', async = true, trafficData, key, value) {
            $.ajax({
                url: ajaxurl,
                data: data,
                type: type,
                async: async
            }).done(function (response) {
                //console.log(response);

                if (response !== '' && isJsonString(response)) {
                    let json_obj = JSON.parse(response);
                    trafficData.push({
                        size: calcTraffic(json_obj),
                        date: value[2],
                        order: value[3]
                    });
                } else {
                    console.error('Server returned non JSON Trafic data');
                    console.info('Response dump:', response);
                    trafficData.length = 13;
                    $('.sirv-tf-loading-error').html("Error during ajax request: Fetch data failed");
                    $('.sirv-traffic-loading').hide();
                }

            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error + status);
                //hack to check that data is not fetched
                trafficData.length = 13;
                if (error) {
                    $('.sirv-tf-loading-error').html("Error during ajax request: " + error);
                } else {
                    $('.sirv-tf-loading-error').html("Error during ajax request: Fetch data failed");
                }
                $('.sirv-traffic-loading').hide();
            });
        }


        function getFormatedFileSize(bytes) {
            let negativeFlag = false;
            let position = 0;
            let units = [" Bytes", " KB", " MB", " GB", " TB"];

            bytes = parseInt(bytes);

            if (bytes < 0) {
                bytes = Math.abs(bytes);
                negativeFlag = true;
            }

            while (bytes >= 1000 && (bytes / 1000) >= 1) {
                bytes /= 1000;
                position++;
            }

            if (negativeFlag) bytes *= -1;

            bytes = bytes % 1 == 0 ? bytes : bytes.toFixed(2);

            return bytes + units[position];

        }


        //modify form action before send settings data
        $("form#sirv-save-options").on('submit', sendSettings);
        function sendSettings(e){
            let activeTab = $('.nav-tab-active').attr('href');

            let $form = $("form#sirv-save-options");
            let action = $form.attr('action');

            if (!!activeTab) action += activeTab;

            $form.attr('action', action);

            return true;
        }


        $('input[name=sirv_js_extend]').on('change', manageJsFileExtendBlock);
        function manageJsFileExtendBlock(){
            let optionData = {};
            let ids = $('input[name=sirv_js_extend]');

            $.each(ids, function(index, val){
                let id = $(val).attr('id');
                optionData[id] = $(val).is(':checked');
            });

            $('#sirv-js-file-extend').val(JSON.stringify(optionData));
        }


        $('input:radio[name=SIRV_JS_FILE]').on('change', toggleJsExtend);
        function toggleJsExtend(){
            let $toggleBlock = $('.sirv-js-extended');
            if ($(this).val() == '3'){
                $toggleBlock.removeClass('sirv-hide');
            }else{
                $toggleBlock.addClass('sirv-hide');
            }
            //.sirv-hide
        }


        function manageElement(selector, disableFlag, text, button, css, html) {
            if (disableFlag !== 'none') {
                $(selector).prop('disabled', disableFlag);
            }
            if (typeof text !== 'undefined' && text !== 'none') {
                if (typeof button !== 'undefined' && button !== 'none') {
                    $(selector).val(text);
                } else {
                    $(selector).html(text);
                }

            }
            if (typeof css !== 'undefined' && css !== 'none') {
                $(selector).css(css);
            }
            if (typeof html !== 'undefined' && html !== 'none') {
                $(selector).html(html);
            }
        }


        function setProfiles(){
            manageSelect('#sirv-shortcodes-profiles', '#sirv-shortcodes-profiles-val', false);
            manageSelect('#sirv-cdn-profiles', '#sirv-cdn-profiles-val', false);
        }


        $('#sirv-shortcodes-profiles').on('change', function () {
            manageSelect('#sirv-shortcodes-profiles', '#sirv-shortcodes-profiles-val', true);
        });
        $('#sirv-cdn-profiles').on('change', function () {
            manageSelect('#sirv-cdn-profiles', '#sirv-cdn-profiles-val', true);
        });

        $('#sirv-woo-product-profiles').on('change', function () {
            manageSelect('#sirv-woo-product-profiles', '#sirv-woo-product-profiles-val', true);
        });

        function manageSelect(selector, valueElem, onChange = false) {
            let $valElem = $(valueElem);

            if (onChange) {
                $valElem.val($(selector + ' option:selected').val());
            } else {
                $(selector + " option[value='" + $valElem.val() + "']").prop('selected', true);
            }

        }



        $('.debug-button').on('click', function () {
            let data = {}
            data['action'] = 'sirv_debug';

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                beforeSend: function () {
                    console.log('-----------------------------------DEBUG START-----------------------------------')
                }
            }).done(function (response) {
                console.log(response);
                console.log('-----------------------------------DEBUG END-----------------------------------')
            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error + ' ' + status);
                console.log('-----------------------------------DEBUG END-----------------------------------')
            });
        });

        //$('.sirv-switch').on('change', function () {
        $('.sirv-switch-acc-login').on('click', switchAccLogin);
        function switchAccLogin(e){

            e.preventDefault();
            e.stopPropagation();
            $(this).blur();

            isNewAccount = !isNewAccount;

            let passText = isNewAccount ? 'Choose password' : 'Password';
            let buttonText = isNewAccount ? 'Create account' : 'Connect account';
            let accText = isNewAccount ? 'Choose account name' : 'Account name';
            let accLabelText = isNewAccount ? 'Already have an account?' : 'Don\'t have an account?';
            let linkText = isNewAccount ? 'Sign in' : 'Create account';

            $('.sirv-pass-field').text(passText);
            $('.sirv-acc-field').text(accText);
            $('.sirv-init').val(buttonText);
            $('.sirv-acc-label').text(accLabelText);
            $('.sirv-switch-acc-login').text(linkText);

            let $hide = $('.sirv-block-hide');
            let $visible = $('.sirv-block-visible');
            $hide.addClass('sirv-block-visible').removeClass('sirv-block-hide');
            $visible.addClass('sirv-block-hide').removeClass('sirv-block-visible');
        }


        $('.storage-size-test').on('click', getImagesStorageSize);
        function getImagesStorageSize(){
            $.ajax({
                url: ajaxurl,
                data: {action: 'sirv_images_storage_size'},
                type: 'POST',
                dataType: "json",
                beforeSend: function (){
                    $('.v-time').text('calc...');
                    $('.v-size').text('calc...');
                    $('.v-count').text('calc...');
                },
            }).done(function (res) {
                //debug
                console.log(res);

                $('.v-time').text(res.microtime + ' ms ( '+ res.time + ' sec )');
                $('.v-size').text(res.size);
                $('.v-count').text(res.count);


            }).fail(function (jqXHR, status, error) {
                console.log("Error during ajax request: " + error);
            });
        }

        //-----------------------initialization-----------------------
        setProfiles();
        getTabFromUrlHash();
        //setCorrectTime();
        //showResponsiveWarning();

    }); //domready end
});
