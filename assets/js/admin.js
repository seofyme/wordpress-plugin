(function ($) {
	'use strict';

	function nonce() {
		return (window.seofymeSEO && seofymeSEO.nonce) || '';
	}

	$(document).on('click', '.seofyme-create-redirect', function () {
		var $btn = $(this);
		$.post(seofymeSEO.ajaxUrl, {
			action: 'seofyme_create_suggested_redirect',
			nonce: nonce(),
			origin: $btn.data('origin'),
			target: $btn.data('target'),
			index: $btn.data('index')
		}).done(function () {
			$btn.closest('.notice').fadeOut();
		});
	});

	$(document).on('click', '#seofyme-refresh-links', function () {
		var postId = $('#seofyme-link-suggestions').data('post-id');
		var $list = $('.seofyme-suggestion-list').html('<li>Loading…</li>');
		$.post(seofymeSEO.ajaxUrl, {
			action: 'seofyme_link_suggestions',
			nonce: nonce(),
			post_id: postId
		}).done(function (res) {
			var html = '';
			(res.data && res.data.suggestions || []).forEach(function (item) {
				html += '<li><a href="' + item.url + '" target="_blank" rel="noopener">' + item.title + '</a></li>';
			});
			$list.html(html || '<li>No suggestions found.</li>');
		});
	});

	function renderAi(items, kind) {
		var $box = $('#seofyme-ai-results').empty();
		(items || []).forEach(function (text) {
			$('<button type="button" class="button"/>').text(text).on('click', function () {
				if (kind === 'metas') {
					$('#seofyme_description').val(text).trigger('input');
				} else {
					$('#seofyme_title').val(text).trigger('input');
				}
			}).appendTo($box);
		});
	}

	$(document).on('click', '#seofyme-ai-titles, #seofyme-ai-metas', function () {
		var kind = this.id === 'seofyme-ai-metas' ? 'metas' : 'titles';
		$('#seofyme-ai-results').text('Generating…');
		$.post(seofymeSEO.ajaxUrl, {
			action: 'seofyme_ai_generate',
			nonce: nonce(),
			post_id: $(this).data('post-id'),
			kind: kind
		}).done(function (res) {
			if (!res.success) {
				$('#seofyme-ai-results').text((res.data && res.data.message) || 'Error');
				return;
			}
			renderAi(res.data.items, kind);
		});
	});

	$(document).on('click', '#seofyme-content-ideas', function () {
		$.post(seofymeSEO.ajaxUrl, { action: 'seofyme_content_ideas', nonce: nonce() }).done(function (res) {
			var $list = $('#seofyme-idea-list').empty();
			(res.data.ideas || []).forEach(function (idea) {
				$('<a href="#" />').text(idea.title).on('click', function (e) {
					e.preventDefault();
					$('#title').val(idea.title).trigger('change');
					$list.data('selected-topic', idea.topic);
				}).wrap('<li/>').parent().appendTo($list);
			});
		});
	});

	$(document).on('click', '#seofyme-starter-draft', function () {
		var topic = $('#seofyme-idea-list').data('selected-topic') || 'this topic';
		$.post(seofymeSEO.ajaxUrl, {
			action: 'seofyme_starter_draft',
			nonce: nonce(),
			topic: topic
		}).done(function (res) {
			if (!res.success) return;
			var content = res.data.content;
			if (window.wp && wp.data && wp.data.dispatch('core/editor')) {
				wp.data.dispatch('core/editor').editPost({ content: content });
			} else if ($('#content').length) {
				$('#content').val(content);
			}
		});
	});

	$(document).on('click', '.seofyme-bulk-draft', function () {
		var $row = $(this).closest('tr');
		$.post(seofymeSEO.ajaxUrl, {
			action: 'seofyme_bulk_draft',
			nonce: nonce(),
			post_id: $row.data('id')
		}).done(function (res) {
			if (!res.success) return;
			if (res.data.title) $row.find('.seofyme-bulk-title').val(res.data.title);
			if (res.data.desc) $row.find('.seofyme-bulk-desc').val(res.data.desc);
		});
	});

	$(document).on('click', '.seofyme-bulk-apply', function () {
		var $row = $(this).closest('tr');
		$.post(seofymeSEO.ajaxUrl, {
			action: 'seofyme_bulk_apply',
			nonce: nonce(),
			post_id: $row.data('id'),
			title: $row.find('.seofyme-bulk-title').val(),
			desc: $row.find('.seofyme-bulk-desc').val()
		}).done(function (res) {
			if (res.success) $row.css('opacity', 0.55);
		});
	});

	$('#seofyme_title, #seofyme_description').on('input', function () {
		if (this.id === 'seofyme_title') $('.seofyme-serp-title').text(this.value || document.title);
		if (this.id === 'seofyme_description') $('.seofyme-serp-desc').text(this.value || '');
	});
})(jQuery);
