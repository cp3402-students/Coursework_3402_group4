;(function (ssaTracking, undefined) {

	var trackPage = function(pageData) {
		// Google Analtyics
		if (window.ga) {
			window.ga('set', 'page', pageData.path);
			window.ga('send', 'pageview');
		}

		// Monster Insights Google Analytics
		if (window.__gaTracker) {
			__gaTracker('set', 'page', pageData.path);
			__gaTracker('send', 'pageview');
		}

		// Google Tag Manager
		if (window.dataLayer) {
			window.dataLayer.push({
				'event': 'Pageview',
				'pagePath': pageData.url,
				'pageTitle': pageData.title
			});
		}

		// Facebook tracking pixel
		if (window.fbq) {
			window.fbq('trackCustom', 'virtualPageview', {
				'url': pageData.url
			})
		}

		// Segment
		if (window.analytics) {
			window.analytics.page(pageData.name, {
				'name': pageData.name,
				'path': pageData.path,
				'referrer': pageData.referrer,
				'title': pageData.title,
				'url': pageData.url
			})
		}
	};

	var trackEvent = function(pageData) {

		// Include the value if this is a success message and there's a price
		var includeValue = pageData.action === 'bookingCompleted' && pageData.hasOwnProperty('value')

		// Google Analytics
		if (window.ga) {
			window.ga('send', {
				hitType: 'event',
				eventCategory: 'Appointment',
				eventAction: pageData.action,
				eventLabel: pageData.appointmentType,
				eventValue: includeValue ? Math.round(pageData.value.price) : null
			})
		}

		// Monster Insights Google Analytics
		if (window.__gaTracker) {
			__gaTracker('send', {
				hitType: 'event',
				eventCategory: 'Appointment',
				eventAction: pageData.action,
				eventLabel: pageData.appointmentType,
				eventValue: includeValue ? Math.round(pageData.value.price) : null
			})
		}

		// Google Tag Manager
		if (window.dataLayer) {
			var obj = {
				'event': pageData.action,
				'appointmentType': pageData.appointmentType
			}
			if (includeValue) {
				obj.conversionValue = pageData.value.price
				obj.currency = pageData.value.currency
			}
			window.dataLayer.push(obj);
		}

		// Facebook tracking pixel
		if (window.fbq) {
			if (includeValue) { // Complete booking and payment
				window.fbq('track', 'Purchase', {
					value: pageData.value.price,
					currency: pageData.value.currency
				})
			} else if (pageData.action === 'bookingCompleted') { // Complete booking
				window.fbq('track', 'Schedule');
			} else if (pageData.action === 'paymentInitiated') { // Start payment
				window.fbq('track', 'InitiateCheckout', {
					value: pageData.value.price,
					currency: pageData.value.currency
				})
			} else {
				window.fbq('trackCustom', pageData.action, {
					appointmentType: pageData.appointmentType
				});
			}
		}

		// Segment
		if (window.analytics) {
			if (includeValue) { // Complete booking and payment
				analytics.track('Order Completed', {
					'total': pageData.value.price,
					'currency': pageData.value.currency,
					'products': [pageData.appointmentType]
				});
			} else if (pageData.action === 'paymentInitiated') { // Start payment
				analytics.track('Checkout Started', {
					'value': pageData.value.price,
					'currency': pageData.value.currency,
					'products': [pageData.appointmentType]
				})
			} else {
				analytics.track(pageData.action, {
					'appointmentType': pageData.appointmentType
				})
			}
		}
	};

	ssaTracking.listen = function(e) {
		if (typeof e.data == 'object' && e.data.hasOwnProperty('ssaType')) {
			if (e.data.ssaType === 'page') {
				trackPage(e.data);
			}

			if (e.data.ssaType === 'event') {
				trackEvent(e.data);
			}
		}
	};

}(window.ssaTracking = window.ssaTracking || {}));

window.addEventListener('message', ssaTracking.listen, false);
