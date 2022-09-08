/*
 * File: iframeResizer.js
 * Desc: Force iframes to size to content.
 * Requires: iframeResizer.contentWindow.js to be loaded into the target frame.
 * Doc: https://github.com/davidjbradshaw/iframe-resizer
 * Author: David J. Bradshaw - dave@bradshaw.net
 * Contributor: Jure Mav - jure.mav@gmail.com
 * Contributor: Reed Dadoune - reed@dadoune.com
 */

!function(e){if("undefined"!=typeof window){var n,i=0,t=!1,o=!1,r="message".length,a="[iFrameSizer]",s=a.length,d=null,c=window.requestAnimationFrame,u={max:1,scroll:1,bodyScroll:1,documentElementScroll:1},f={},l=null,m={autoResize:!0,bodyBackground:null,bodyMargin:null,bodyMarginV1:8,bodyPadding:null,checkOrigin:!0,inPageLinks:!1,enablePublicMethods:!0,heightCalculationMethod:"bodyOffset",id:"iFrameResizer",interval:32,log:!1,maxHeight:1/0,maxWidth:1/0,minHeight:0,minWidth:0,mouseEvents:!0,resizeFrom:"parent",scrolling:!1,sizeHeight:!0,sizeWidth:!1,warningTimeout:5e3,tolerance:0,widthCalculationMethod:"scroll",onClose:function(){return!0},onClosed:function(){},onInit:function(){},onMessage:function(){M("onMessage function not defined")},onMouseEnter:function(){},onMouseLeave:function(){},onResized:function(){},onScroll:function(){return!0}},g={};window.jQuery&&((n=window.jQuery).fn?n.fn.iFrameResize||(n.fn.iFrameResize=function(e){return this.filter("iframe").each(function(n,i){j(i,e)}).end()}):x("","Unable to bind to jQuery, it is not fully loaded.")),"function"==typeof define&&define.amd?define([],q):"object"==typeof module&&"object"==typeof module.exports&&(module.exports=q()),window.iFrameResize=window.iFrameResize||q()}function h(){return window.MutationObserver||window.WebKitMutationObserver||window.MozMutationObserver}function p(e,n,i){e.addEventListener(n,i,!1)}function w(e,n,i){e.removeEventListener(n,i,!1)}function b(e){return a+"["+function(e){var n="Host page: "+e;return window.top!==window.self&&(n=window.parentIFrame&&window.parentIFrame.getId?window.parentIFrame.getId()+": "+e:"Nested host page: "+e),n}(e)+"]"}function y(e){return f[e]?f[e].log:t}function v(e,n){I("log",e,n,y(e))}function x(e,n){I("info",e,n,y(e))}function M(e,n){I("warn",e,n,!0)}function I(e,n,i,t){!0===t&&"object"==typeof window.console&&console[e](b(n),i)}function k(e){function n(){o("Height"),o("Width"),C(function(){W(B),T(q),y("onResized",B)},B,"init")}function i(e){return"border-box"!==e.boxSizing?0:(e.paddingTop?parseInt(e.paddingTop,10):0)+(e.paddingBottom?parseInt(e.paddingBottom,10):0)}function t(e){return"border-box"!==e.boxSizing?0:(e.borderTopWidth?parseInt(e.borderTopWidth,10):0)+(e.borderBottomWidth?parseInt(e.borderBottomWidth,10):0)}function o(e){var n=Number(f[q]["max"+e]),i=Number(f[q]["min"+e]),t=e.toLowerCase(),o=Number(B[t]);v(q,"Checking "+t+" is in range "+i+"-"+n),o<i&&(o=i,v(q,"Set "+t+" to min value")),o>n&&(o=n,v(q,"Set "+t+" to max value")),B[t]=""+o}function c(e){return L.substr(L.indexOf(":")+r+e)}function u(e,n){var i,t,o;i=function(){var i,t;S("Send Page Info","pageInfo:"+(i=document.body.getBoundingClientRect(),t=B.iframe.getBoundingClientRect(),JSON.stringify({iframeHeight:t.height,iframeWidth:t.width,clientHeight:Math.max(document.documentElement.clientHeight,window.innerHeight||0),clientWidth:Math.max(document.documentElement.clientWidth,window.innerWidth||0),offsetTop:parseInt(t.top-i.top,10),offsetLeft:parseInt(t.left-i.left,10),scrollTop:window.pageYOffset,scrollLeft:window.pageXOffset,documentHeight:document.documentElement.clientHeight,documentWidth:document.documentElement.clientWidth,windowHeight:window.innerHeight,windowWidth:window.innerWidth})),e,n)},t=32,g[o=n]||(g[o]=setTimeout(function(){g[o]=null,i()},t))}function l(e){var n=e.getBoundingClientRect();return R(q),{x:Math.floor(Number(n.left)+Number(d.x)),y:Math.floor(Number(n.top)+Number(d.y))}}function m(e){var n=e?l(B.iframe):{x:0,y:0},i={x:Number(B.width)+n.x,y:Number(B.height)+n.y};v(q,"Reposition requested from iFrame (offset x:"+n.x+" y:"+n.y+")"),window.top!==window.self?window.parentIFrame?window.parentIFrame["scrollTo"+(e?"Offset":"")](i.x,i.y):M(q,"Unable to scroll to requested position, window.parentIFrame not found"):(d=i,h(),v(q,"--"))}function h(){!1!==y("onScroll",d)?T(q):E()}function b(e){var n={};if(0===Number(B.width)&&0===Number(B.height)){var i=c(9).split(":");n={x:i[1],y:i[0]}}else n={x:B.width,y:B.height};y(e,{iframe:B.iframe,screenX:Number(n.x),screenY:Number(n.y),type:B.type})}function y(e,n){return F(q,e,n)}var I,k,z,j,P,A,L=e.data,B={},q=null;"[iFrameResizerChild]Ready"===L?function(){for(var e in f)S("iFrame requested init",H(e),f[e].iframe,e)}():a===(""+L).substr(0,s)&&L.substr(s).split(":")[0]in f?(z=L.substr(s).split(":"),j=z[1]?parseInt(z[1],10):0,P=f[z[0]]&&f[z[0]].iframe,A=getComputedStyle(P),B={iframe:P,id:z[0],height:j+i(A)+t(A),width:z[2],type:z[3]},q=B.id,f[q]&&(f[q].loaded=!0),(k=B.type in{true:1,false:1,undefined:1})&&v(q,"Ignoring init message from meta parent page"),!k&&function(e){var n=!0;return f[e]||(n=!1,M(B.type+" No settings for "+e+". Message was: "+L)),n}(q)&&(v(q,"Received: "+L),I=!0,null===B.iframe&&(M(q,"IFrame ("+B.id+") not found"),I=!1),I&&function(){var n,i=e.origin,t=f[q]&&f[q].checkOrigin;if(t&&""+i!="null"&&!(t.constructor===Array?function(){var e=0,n=!1;for(v(q,"Checking connection is from allowed list of origins: "+t);e<t.length;e++)if(t[e]===i){n=!0;break}return n}():(n=f[q]&&f[q].remoteHost,v(q,"Checking connection is from: "+n),i===n)))throw new Error("Unexpected message received from: "+i+" for "+B.iframe.id+". Message was: "+e.data+". This error can be disabled by setting the checkOrigin: false option or by providing of array of trusted domains.");return!0}()&&function(){switch(f[q]&&f[q].firstRun&&f[q]&&(f[q].firstRun=!1),B.type){case"close":O(B.iframe);break;case"message":a=c(6),v(q,"onMessage passed: {iframe: "+B.iframe.id+", message: "+a+"}"),y("onMessage",{iframe:B.iframe,message:JSON.parse(a)}),v(q,"--");break;case"mouseenter":b("onMouseEnter");break;case"mouseleave":b("onMouseLeave");break;case"autoResize":f[q].autoResize=JSON.parse(c(9));break;case"scrollTo":m(!1);break;case"scrollToOffset":m(!0);break;case"pageInfo":u(f[q]&&f[q].iframe,q),function(){function e(e,t){function o(){f[i]?u(f[i].iframe,i):n()}["scroll","resize"].forEach(function(n){v(i,e+n+" listener for sendPageInfo"),t(window,n,o)})}function n(){e("Remove ",w)}var i=q;e("Add ",p),f[i]&&(f[i].stopPageInfo=n)}();break;case"pageInfoStop":f[q]&&f[q].stopPageInfo&&(f[q].stopPageInfo(),delete f[q].stopPageInfo);break;case"inPageLink":e=c(9),t=e.split("#")[1]||"",o=decodeURIComponent(t),(r=document.getElementById(o)||document.getElementsByName(o)[0])?(i=l(r),v(q,"Moving to in page link (#"+t+") at x: "+i.x+" y: "+i.y),d={x:i.x,y:i.y},h(),v(q,"--")):window.top!==window.self?window.parentIFrame?window.parentIFrame.moveToAnchor(t):v(q,"In page link #"+t+" not found and window.parentIFrame not found"):v(q,"In page link #"+t+" not found");break;case"reset":N(B);break;case"init":n(),y("onInit",B.iframe);break;default:0===Number(B.width)&&0===Number(B.height)?M("Unsupported message received ("+B.type+"), this is likely due to the iframe containing a later version of iframe-resizer than the parent page"):n()}var e,i,t,o,r,a}())):x(q,"Ignored: "+L)}function F(e,n,i){var t=null,o=null;if(f[e]){if("function"!=typeof(t=f[e][n]))throw new TypeError(n+" on iFrame["+e+"] is not a function");o=t(i)}return o}function z(e){var n=e.id;delete f[n]}function O(e){var n=e.id;if(!1!==F(n,"onClose",n)){v(n,"Removing iFrame: "+n);try{e.parentNode&&e.parentNode.removeChild(e)}catch(e){M(e)}F(n,"onClosed",n),v(n,"--"),z(e)}else v(n,"Close iframe cancelled by onClose event")}function R(n){null===d&&v(n,"Get page position: "+(d={x:window.pageXOffset!==e?window.pageXOffset:document.documentElement.scrollLeft,y:window.pageYOffset!==e?window.pageYOffset:document.documentElement.scrollTop}).x+","+d.y)}function T(e){null!==d&&(window.scrollTo(d.x,d.y),v(e,"Set page position: "+d.x+","+d.y),E())}function E(){d=null}function N(e){v(e.id,"Size reset requested by "+("init"===e.type?"host page":"iFrame")),R(e.id),C(function(){W(e),S("reset","reset",e.iframe,e.id)},e,"reset")}function W(e){function n(n){o||"0"!==e[n]||(o=!0,v(t,"Hidden iFrame detected, creating visibility listener"),function(){function e(){Object.keys(f).forEach(function(e){!function(e){function n(n){return"0px"===(f[e]&&f[e].iframe.style[n])}f[e]&&(i=f[e].iframe,null!==i.offsetParent)&&(n("height")||n("width"))&&(E(),S("Visibility change","resize",f[e].iframe,e));var i}(e)})}function n(n){v("window","Mutation observed: "+n[0].target+" "+n[0].type),P(e,16)}var i=h();i&&(t=document.querySelector("body"),new i(n).observe(t,{attributes:!0,attributeOldValue:!1,characterData:!0,characterDataOldValue:!1,childList:!0,subtree:!0}));var t}())}function i(i){!function(n){e.id?(e.iframe.style[n]=e[n]+"px",v(e.id,"IFrame ("+t+") "+n+" set to "+e[n]+"px")):v("undefined","messageData id not set")}(i),n(i)}var t=e.iframe.id;f[t]&&(f[t].sizeHeight&&i("height"),f[t].sizeWidth&&i("width"))}function C(e,n,i){i!==n.type&&c&&!window.jasmine?(v(n.id,"Requesting animation frame"),c(e)):e()}function S(e,n,i,t,o){var r,s=!1;t=t||i.id,f[t]&&(i&&"contentWindow"in i&&null!==i.contentWindow?(r=f[t]&&f[t].targetOrigin,v(t,"["+e+"] Sending msg to iframe["+t+"] ("+n+") targetOrigin: "+r),i.contentWindow.postMessage(a+n,r)):M(t,"["+e+"] IFrame("+t+") not found"),o&&f[t]&&f[t].warningTimeout&&(f[t].msgTimeout=setTimeout(function(){!f[t]||f[t].loaded||s||(s=!0,M(t,"IFrame has not responded within "+f[t].warningTimeout/1e3+" seconds. Check iFrameResizer.contentWindow.js has been loaded in iFrame. This message can be ignored if everything is working, or you can set the warningTimeout option to a higher value or zero to suppress this warning."))},f[t].warningTimeout)))}function H(e){return e+":"+f[e].bodyMarginV1+":"+f[e].sizeWidth+":"+f[e].log+":"+f[e].interval+":"+f[e].enablePublicMethods+":"+f[e].autoResize+":"+f[e].bodyMargin+":"+f[e].heightCalculationMethod+":"+f[e].bodyBackground+":"+f[e].bodyPadding+":"+f[e].tolerance+":"+f[e].inPageLinks+":"+f[e].resizeFrom+":"+f[e].widthCalculationMethod+":"+f[e].mouseEvents}function j(n,o){function r(e){var n=e.split("Callback");if(2===n.length){var i="on"+n[0].charAt(0).toUpperCase()+n[0].slice(1);this[i]=this[e],delete this[e],M(d,"Deprecated: '"+e+"' has been renamed '"+i+"'. The old method will be removed in the next major version.")}}var a,s,d=function(e){var r;return""===e&&(n.id=(r=o&&o.id||m.id+i++,null!==document.getElementById(r)&&(r+=i++),e=r),t=(o||{}).log,v(e,"Added missing iframe ID: "+e+" ("+n.src+")")),e}(n.id);d in f&&"iFrameResizer"in n?M(d,"Ignored iFrame, already setup."):(!function(e){var i;e=e||{},f[d]={firstRun:!0,iframe:n,remoteHost:n.src&&n.src.split("/").slice(0,3).join("/")},function(e){if("object"!=typeof e)throw new TypeError("Options is not an object")}(e),Object.keys(e).forEach(r,e),function(e){for(var n in m)Object.prototype.hasOwnProperty.call(m,n)&&(f[d][n]=Object.prototype.hasOwnProperty.call(e,n)?e[n]:m[n])}(e),f[d]&&(f[d].targetOrigin=!0===f[d].checkOrigin?""===(i=f[d].remoteHost)||null!==i.match(/^(about:blank|javascript:|file:\/\/)/)?"*":i:"*")}(o),function(){switch(v(d,"IFrame scrolling "+(f[d]&&f[d].scrolling?"enabled":"disabled")+" for "+d),n.style.overflow=!1===(f[d]&&f[d].scrolling)?"hidden":"auto",f[d]&&f[d].scrolling){case"omit":break;case!0:n.scrolling="yes";break;case!1:n.scrolling="no";break;default:n.scrolling=f[d]?f[d].scrolling:"no"}}(),function(){function e(e){var i=f[d][e];1/0!==i&&0!==i&&(n.style[e]="number"==typeof i?i+"px":i,v(d,"Set "+e+" = "+n.style[e]))}function i(e){if(f[d]["min"+e]>f[d]["max"+e])throw new Error("Value for min"+e+" can not be greater than max"+e)}i("Height"),i("Width"),e("maxHeight"),e("minHeight"),e("maxWidth"),e("minWidth")}(),"number"!=typeof(f[d]&&f[d].bodyMargin)&&"0"!==(f[d]&&f[d].bodyMargin)||(f[d].bodyMarginV1=f[d].bodyMargin,f[d].bodyMargin=f[d].bodyMargin+"px"),a=H(d),(s=h())&&function(e){n.parentNode&&new e(function(e){e.forEach(function(e){Array.prototype.slice.call(e.removedNodes).forEach(function(e){e===n&&O(n)})})}).observe(n.parentNode,{childList:!0})}(s),p(n,"load",function(){var i,t;S("iFrame.onload",a,n,e,!0),i=f[d]&&f[d].firstRun,t=f[d]&&f[d].heightCalculationMethod in u,!i&&t&&N({iframe:n,height:0,width:0,type:"init"})}),S("init",a,n,e,!0),f[d]&&(f[d].iframe.iFrameResizer={close:O.bind(null,f[d].iframe),removeListeners:z.bind(null,f[d].iframe),resize:S.bind(null,"Window resize","resize",f[d].iframe),moveToAnchor:function(e){S("Move to anchor","moveToAnchor:"+e,f[d].iframe,d)},sendMessage:function(e){S("Send Message","message:"+(e=JSON.stringify(e)),f[d].iframe,d)}}))}function P(e,n){null===l&&(l=setTimeout(function(){l=null,e()},n))}function A(){"hidden"!==document.visibilityState&&(v("document","Trigger event: Visibility change"),P(function(){L("Tab Visible","resize")},16))}function L(e,n){Object.keys(f).forEach(function(i){(function(e){return f[e]&&"parent"===f[e].resizeFrom&&f[e].autoResize&&!f[e].firstRun})(i)&&S(e,n,f[i].iframe,i)})}function B(){p(window,"message",k),p(window,"resize",function(){var e;v("window","Trigger event: "+(e="resize")),P(function(){L("Window "+e,"resize")},16)})}function q(){function n(e,n){n&&(!function(){if(!n.tagName)throw new TypeError("Object is not a valid DOM element");if("IFRAME"!==n.tagName.toUpperCase())throw new TypeError("Expected <IFRAME> tag, found <"+n.tagName+">")}(),j(n,e),i.push(n))}var i;return function(){var e,n=["moz","webkit","o","ms"];for(e=0;e<n.length&&!c;e+=1)c=window[n[e]+"RequestAnimationFrame"];c?c=c.bind(window):v("setup","RequestAnimationFrame not supported")}(),B(),function(t,o){switch(i=[],function(e){e&&e.enablePublicMethods&&M("enablePublicMethods option has been removed, public methods are now always available in the iFrame")}(t),typeof o){case"undefined":case"string":Array.prototype.forEach.call(document.querySelectorAll(o||"iframe"),n.bind(e,t));break;case"object":n(t,o);break;default:throw new TypeError("Unexpected data type ("+typeof o+")")}return i}}}();

/* ----- SSA Code --------- */

var bookingIframes = document.querySelectorAll('.ssa_booking_iframe');
var iframeInteraction = false; // Track if the iframe has been activated

var ssaDebouncedScroll = debounce(function(e){ // Debouncing scroll event listener to improve performance
	ssaHandleScroll(e);
}, 300);

// Detect any interactivity with iframe - focus moved to any element inside
window.addEventListener('blur', function(){
	let activeEl = document.activeElement;

	if (activeEl.tagName === 'IFRAME' && activeEl.classList.contains('ssa_booking_iframe')) {
		iframeInteraction = true;
	}
});

// helper function to initialize the IframeResizer
var ssaInitIframeResizer = function() {
	var ssaIframeSettings = {
		heightCalculationMethod: 'bodyScroll',
		checkOrigin: false,
		warningTimeout: 20000,
		onResized: ssaDebouncedScroll,
		onScroll: false
	}

	iFrameResize(ssaIframeSettings, '.ssa_booking_iframe')
}

// call on page load if there are SSA iframes
if (bookingIframes) {
	ssaInitIframeResizer();
}

// Only run Elementor and FF code if jQuery is on the page
if ( window.jQuery ) {

	// Elementor integration
	jQuery( document ).on( 'elementor/popup/show', function(){
		ssaInitIframeResizer()
	});

	jQuery( document ).ready(function($){

		// Formidable Forms integration
		$(document).on( 'frmPageChanged', function( event, form, response ) {
			var $iframes = $( '.frm_forms .ssa_booking_iframe' );

			// if form page has changed, and the current page contains an SSA iframe,
			// initialize IframeResizer
			if( $iframes.length ) {
				ssaInitIframeResizer();
			}
		});

		// Gravity Forms integration
		$( document ).on( 'gform_page_loaded', function( event, form_id, current_page ) {
			var $iframes = $( '.gform_wrapper .ssa_booking_iframe' );

			// if form page has changed, and the current page contains an SSA iframe,
			// initialize IframeResizer
			if( $iframes.length ) {
				ssaInitIframeResizer();
			}
		});
	});

} // end jQuery check

// Returns a function, that, as long as it continues to be invoked, will not
// be triggered. The function will be called after it stops being called for
// N milliseconds. If `immediate` is passed, trigger the function on the
// leading edge, instead of the trailing.
function debounce(func, wait, immediate) {
	var timeout;
	return function() {
		var context = this, args = arguments;
		var later = function() {
			timeout = null;
			if (!immediate) func.apply(context, args);
		};
		var callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if (callNow) func.apply(context, args);
	};
};

function ssaHandleScroll(e) {
	if ( e.type === 'init' ) { // Initial page load, don't scroll
		return;
	}

	// Is at least the top 50 pixels of the booking form visible?
	var rect = e.iframe.getBoundingClientRect();
	var isInViewport = rect.top >= 0 &&
		rect.left >= 0 &&
		rect.top + 50 <= (window.innerHeight || document.documentElement.clientHeight);

	if (!isInViewport && iframeInteraction) {
		e.iframe.scrollIntoView({
			behavior: 'smooth',
			block: 'center'
		});
	}
}

