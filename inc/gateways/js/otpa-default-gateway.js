/* global OTPA, window */
jQuery(document).ready(function ($) {
	var requestCodeHandle = $('#otpa_send_code'),
		requestCodeHandleText = requestCodeHandle.html(),
		requestCodeHandleDisabled = false;

	function setRequestCodeHandleDisabled(disabled) {
		requestCodeHandleDisabled = disabled;
		requestCodeHandle.toggleClass('otpa-disabled', disabled);

		if (disabled) {
			requestCodeHandle.attr('disabled', 'disabled');
		} else {
			requestCodeHandle.removeAttr('disabled');
		}
	}

	$('#otpa_id_widget').on('keyup', function () {

		if (!requestCodeHandleDisabled && 0 < $(this).val().length) {
			setRequestCodeHandleDisabled(false);
		} else {
			setRequestCodeHandleDisabled(true);
		}
	});

	requestCodeHandle.on('click', function (e) {
		e.preventDefault();
		enableHandleCountdown(OTPA.otp_code_request_throttle);
		window.requestOTPCode();
	});

	if (0 < OTPA.otp_code_request_wait) {
		enableHandleCountdown(OTPA.otp_code_request_wait);
	}

	function enableHandleCountdown(time) {
		setRequestCodeHandleDisabled(true);

		setTimeout(function () {

			if (requestCodeHandle.hasClass('cancelcountdown')) {
				requestCodeHandle.removeClass('cancelcountdown');
				if (time > 3) {
					time = 3;
				}

			}

			if (0 !== time) {
				time--;
				if (OTPA && OTPA.otp_hide_request_countdown) {
					//handle.html(text);
				} else {
					requestCodeHandle.html(time + 's');
				}

				enableHandleCountdown(time);
			} else {
				requestCodeHandle.html(requestCodeHandleText);
				setRequestCodeHandleDisabled(false);
			}
		}, 1000);
	}
});
