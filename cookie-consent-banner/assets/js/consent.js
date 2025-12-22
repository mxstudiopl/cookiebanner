jQuery(document).ready(function ($) {
    // Check cookie using native method first (for Brave compatibility)
    function getCookie(name) {
        var value = "; " + document.cookie;
        var parts = value.split("; " + name + "=");
        if (parts.length == 2) return parts.pop().split(";").shift();
        return null;
    }
    
    var cookieMode = getCookie('consent_cookie');
    if (!cookieMode && typeof $.cookie === 'function') {
        cookieMode = $.cookie('consent_cookie');
    }
    
        if (!cookieMode) {
            setTimeout(function () {
                var modal = document.getElementById('ccb-modal');
                var overlay = document.querySelector('.ccb-overlay');
                if (modal) {
                    modal.classList.add('active');
                    if (overlay) {
                        overlay.style.display = 'block';
                        overlay.style.opacity = '1';
                    }
                }
            }, 3000)
        }
    
    
        $('.js-set').click(function () {
            $('.ccb-modal-body').addClass('sets')
        })
    
        $('.js-all').click(function() {
            const cookieData = {
                'ad_storage': 'granted',
                'ad_user_data': 'granted',
                'ad_personalization': 'granted',
                'analytics_storage': 'granted',
                'personalization_storage': 'granted',
                'functionality_storage': 'granted',
                'security_storage': 'granted'
            };
    
            const jsonData = JSON.stringify(cookieData);
    
            $.cookie('consent_cookie', jsonData, { expires: 365 * 10, path: '/' });
            $('#ccb-modal').removeClass('active');
            $('.ccb-overlay').fadeOut(500);
    
            location.reload()
        })
    
        $('.js-back').click(function () {
            $('.ccb-modal-body').removeClass('sets')
        })
    
        $('.js-save').click(function () {
            let ad_storage = $('#ad_storage').prop('checked') ? 'granted' : 'denied';
            let user_data = $('#ad_user_data').prop('checked') ? 'granted' : 'denied';
            let personalisation = $('#ad_personalization').prop('checked') ? 'granted' : 'denied';
            let storage = $('#analytics_storage').prop('checked') ? 'granted' : 'denied';
            let personalization_storage = $('#personalization_storage').prop('checked') ? 'granted' : 'denied';
            let functionality_storage = $('#functionality_storage').prop('checked') ? 'granted' : 'denied';
            let security_storage = $('#security_storage').prop('checked') ? 'granted' : 'denied';
    
            const cookieData = {
                'ad_storage': ad_storage,
                'ad_user_data': user_data,
                'ad_personalization': personalisation,
                'analytics_storage': storage,
                'personalization_storage': personalization_storage,
                'functionality_storage': functionality_storage,
                'security_storage': security_storage
            };
    
            const jsonData = JSON.stringify(cookieData);
            $.cookie('consent_cookie', jsonData, { expires: 365 * 10, path: '/' });
            $('#ccb-modal').removeClass('active');
            $('.ccb-overlay').fadeOut(1000);
    
            location.reload()
        })
    });


