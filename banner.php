<div id="consent_banner" class="consent_banner">
    <div class="container">
        <div class="consent_banner-body">
            <div class="consent_banner-preview">
                <h2><?php
                    the_field('consent_mode_title', 'option'); ?></h2>
                <p><?php
                    the_field('consent_mode_text', 'option'); ?></p>
                <div class="consent_banner-btns">
                    <a href="javascript:;" class="banner-btn banner-btn-primary js-all"><?php
                        the_field('accept_all_button', 'option'); ?></a>
                    <a href="javascript:;" class="banner-btn banner-btn-primary js-set"><?php
                        the_field('setting_button', 'option'); ?></a>
                    <a href="javascript:;" class="banner-btn banner-btn-deny js-deny"><?php
                        the_field('reject_all_button', 'option'); ?></a>
                </div>
            </div>
            <div class="consent_banner-settings">
                <div class="consent_banner-settings_block">
                    <div class="consent_banner-row">
                        <input class="setting_check" type="checkbox" name="ad_storage" id="ad_storage" checked disabled>
                        <label class="setting_label" for="ad_storage"></label>
                        <span><?php
                            the_field('add_storage_checkbox', 'option'); ?></span>
                    </div>
                    <div class="consent_banner-row">
                        <input id="ad_user_data" class="setting_check" type="checkbox" name="ad_user_data" checked>
                        <label class="setting_label" for="ad_user_data"></label>
                        <span><?php
                            the_field('add_user_data_checkbox', 'option'); ?></span>
                    </div>
                    <div class="consent_banner-row">
                        <input id="ad_personalization" class="setting_check" type="checkbox" name="ad_personalization" checked>
                        <label class="setting_label" for="ad_personalization"></label>
                        <span><?php
                            the_field('add_personalization', 'option'); ?></span>
                    </div>
                    <div class="consent_banner-row">
                        <input id="analytics_storage" class="setting_check" type="checkbox" name="analytics_storage" checked>
                        <label class="setting_label" for="analytics_storage"></label>
                        <span><?php
                            the_field('add_analitics_checkbox', 'option'); ?></span>
                    </div>
                </div>
                <div class="consent_banner-settings_btns">
                    <a href="javascript:;" class="banner-btn banner-btn-primary js-save"><?php
                        the_field('set_cookie_button', 'option'); ?></a>
                    <a href="javascript:;" class="banner-btn banner-btn-deny js-back"><?php
                        the_field('back_button', 'option'); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
  window.dataLayer = window.dataLayer || []

  function gtag () {
    dataLayer.push(arguments)
  }

  gtag('consent', 'default', {
        'ad_storage':         'denied',
        'ad_user_data':       'denied',
        'ad_personalization': 'denied',
        'analytics_storage':  'denied',
		'personalization_storage': 'denied',
		'functionality_storage': 'denied',
		'security_storage': 'denied'
      })

  var consentCookie = jQuery.cookie('consent_cookie');

  if (consentCookie && consentCookie != 'false') {
    var cookieObject = JSON.parse(consentCookie);
    console.log(cookieObject)
    gtag('consent', 'update', cookieObject);
  }
</script>

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-159534180-1"></script>
<script>
  window.dataLayer = window.dataLayer || []

  function gtag () {dataLayer.push(arguments)}

  gtag('js', new Date())

  gtag('config', 'UA-159534180-1')
</script>