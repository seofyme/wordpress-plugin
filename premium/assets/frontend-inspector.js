(function () {
	'use strict';
	var root = document.getElementById('seofyme-fe-inspector');
	if (!root || !window.seofymeFE) {
		return;
	}
	root.hidden = false;
	var toggle = document.getElementById('seofyme-fe-toggle');
	var save = document.getElementById('seofyme-fe-save');
	var status = root.querySelector('.seofyme-fe-status');

	toggle.addEventListener('click', function () {
		var open = root.classList.toggle('is-open');
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
	});

	save.addEventListener('click', function () {
		status.textContent = 'Saving…';
		var body = new FormData();
		body.append('action', 'seofyme_fe_save');
		body.append('nonce', seofymeFE.nonce);
		body.append('post_id', seofymeFE.postId);
		body.append('title', document.getElementById('seofyme-fe-title').value);
		body.append('desc', document.getElementById('seofyme-fe-desc').value);
		fetch(seofymeFE.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				status.textContent = res.success ? 'Saved.' : 'Could not save.';
			})
			.catch(function () {
				status.textContent = 'Could not save.';
			});
	});
})();
