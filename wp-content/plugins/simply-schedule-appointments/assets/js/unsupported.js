var ssaCompatibleBrowser = function () {
	var objTest = typeof Object['__defineSetter__'] === 'function';

	var arrayTest = !!Array.prototype.find;

	var constTest = function () {
		try {
			const xy = 123;
			return true;
		} catch (err) {
			return false;
		}
	};

	var svgTest = !!document.createElementNS && !!document.createElementNS("http://www.w3.org/2000/svg", "svg").createSVGRect;

	var transitionTest = ('transition' in document.documentElement.style) || ('WebkitTransition' in document.documentElement.style);

	var flexboxTest = function () {
	  var f = 'flex';
	  var fw = '-webkit-' + f;
	  var el = document.createElement('b');

	  try {
	    el.style.display = fw;
	    el.style.display = f;
	    return !!(el.style.display === f || el.style.display === fw);
	  } catch (err) {
	    return false;
	  }
	};

	var intlTest = function () {
		try {
			return !!(new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(123.456));
		} catch (err) {
			return false;
		}
	};

	return objTest && arrayTest && constTest() && svgTest && transitionTest && flexboxTest() && intlTest();
}

if (!ssaCompatibleBrowser()) {
	var adminApp = document.getElementById('ssa-admin-app');
	if (adminApp) {
		adminApp.parentNode.removeChild(adminApp);
	}

	var bookingApp = document.getElementById('ssa-booking-app');
	if (bookingApp) {
		bookingApp.parentNode.removeChild(bookingApp);
	}

	var message = document.getElementById('ssa-unsupported');
	message.style.display = 'block'
	throw new Error('Your browser is not compatible');
}