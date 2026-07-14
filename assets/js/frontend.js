document.querySelectorAll('.verwaltung-boote-browser-datetime').forEach(function (element) {
	var value = element.getAttribute('datetime') || element.dataset.vbUtc;
	var date = value ? new Date(value) : null;

	if (!date || Number.isNaN(date.getTime())) {
		return;
	}

	var options = element.dataset.vbFormat === 'time'
		? { timeStyle: 'short' }
		: { dateStyle: 'short', timeStyle: 'short' };
	var formatted = new Intl.DateTimeFormat(undefined, options).format(date);

	if ('value' in element && element.tagName === 'INPUT') {
		element.value = formatted;
	} else {
		element.textContent = formatted;
	}
});

document.querySelectorAll('.verwaltung-boote-rueckgabe').forEach(function (form) {
	var damageSelect = form.querySelector('.verwaltung-boote-schaden-auswahl');
	var details = form.querySelector('.verwaltung-boote-schadendetails');
	var severity = form.querySelector('.verwaltung-boote-schwere');

	if (!damageSelect || !details || !severity) {
		return;
	}

	function updateDamageFields() {
		var hasDamage = damageSelect.value === 'ja';
		details.hidden = !hasDamage;
		severity.required = hasDamage;
	}

	damageSelect.addEventListener('change', updateDamageFields);
	updateDamageFields();
});

document.querySelectorAll('.verwaltung-boote-reservieren').forEach(function (form) {
	var start = form.querySelector('.verwaltung-boote-reservierung-start');
	var end = form.querySelector('.verwaltung-boote-reservierung-ende');

	if (!start || !end) {
		return;
	}

	start.addEventListener('change', function () {
		var startDate = new Date(start.value);
		if (Number.isNaN(startDate.getTime())) {
			return;
		}

		startDate.setHours(startDate.getHours() + 1);
		var local = new Date(startDate.getTime() - startDate.getTimezoneOffset() * 60000);
		end.value = local.toISOString().slice(0, 16);
	});
});

if (typeof QRCode !== 'undefined') {
	document.querySelectorAll('.verwaltung-boote-qr-code').forEach(function (element) {
		var url = element.dataset.vbQrUrl;

		if (!url) {
			return;
		}

		new QRCode(element, {
			text: url,
			width: 220,
			height: 220,
			correctLevel: QRCode.CorrectLevel.H
		});
	});
}

document.querySelectorAll('.verwaltung-boote-qr-drucken').forEach(function (button) {
	button.addEventListener('click', function () {
		window.print();
	});
});
