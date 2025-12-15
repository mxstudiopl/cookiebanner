jQuery(document).ready(function ($) {
const cookieMode = $.cookie('consent_cookie');

	if (!cookieMode) {
		setTimeout(function () {
			$('#consent_banner').addClass('active');
			$('.consent-overlay').fadeIn(500);
		}, 3000)
	}


	$('.js-set').click(function () {
		$('.consent_banner-body').addClass('sets')
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
		$('#consent_banner').removeClass('active');
		$('.consent-overlay').fadeOut(500);

		location.reload()
	})

	$('.js-deny').click(function () {
		const cookieData = 'false';

		$.cookie('consent_cookie', cookieData, { expires: 1, path: '/' });
		$('#consent_banner').removeClass('active');

		location.reload()
	})

	$('.js-back').click(function () {
		$('.consent_banner-body').removeClass('sets')
	})

	$('.js-save').click(function () {
		let user_data = $('#ad_user_data').prop('checked') ? 'granted' : 'denied';
		let personalisation = $('#ad_personalization').prop('checked') ? 'granted' : 'denied';
		let storage = $('#analytics_storage').prop('checked') ? 'granted' : 'denied';

		const cookieData = {
			'ad_storage': 'granted',
			'personalization_storage': 'granted',
			'functionality_storage': 'granted',
			'security_storage': 'granted',
			'ad_user_data': user_data,
			'ad_personalization': personalisation,
			'analytics_storage': storage
		};

		const jsonData = JSON.stringify(cookieData);
		$.cookie('consent_cookie', jsonData, { expires: 365 * 10, path: '/' });
		$('#consent_banner').removeClass('active');
		$('.consent-overlay').fadeOut(1000);

		location.reload()
	})
});