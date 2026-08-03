(function ($) {
	'use strict';

	function nonce() {
		return (window.seofymePremium && seofymePremium.nonce) || '';
	}

	$(document).on('click', '.seofyme-create-redirect', function () {
		var $btn = $(this);
		$.post(seofymePremium.ajaxUrl, {
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
		var $list = $('.seofyme-suggestion-list');
		$list.html('<li>Loading…</li>');
		$.post(seofymePremium.ajaxUrl, {
			action: 'seofyme_link_suggestions',
			nonce: nonce(),
			post_id: postId
		}).done(function (res) {
			if (!res.success) {
				$list.html('<li>Could not load suggestions.</li>');
				return;
			}
			var html = '';
			(res.data.suggestions || []).forEach(function (item) {
				html += '<li><a href="' + item.url + '" target="_blank" rel="noopener">' + item.title + '</a></li>';
			});
			$list.html(html || '<li>No suggestions found.</li>');
		});
	});

	function renderAi(items, kind) {
		var $box = $('#seofyme-ai-results').empty();
		(items || []).forEach(function (text) {
			var $b = $('<button type="button" class="button"/>').text(text);
			$b.on('click', function () {
				if (kind === 'metas') {
					var $desc = $('#yoast_wpseo_metadesc, #snippet-editor-field-description, textarea[name="yoast_wpseo_metadesc"]');
					if ($desc.length) {
						$desc.val(text).trigger('change');
					} else {
						window.prompt('Copy meta description', text);
					}
				} else {
					var $title = $('#yoast_wpseo_title, #snippet-editor-field-title, input[name="yoast_wpseo_title"]');
					if ($title.length) {
						$title.val(text).trigger('change');
					} else {
						window.prompt('Copy SEO title', text);
					}
				}
			});
			$box.append($b);
		});
	}

	$(document).on('click', '#seofyme-ai-titles, #seofyme-ai-metas', function () {
		var kind = this.id === 'seofyme-ai-metas' ? 'metas' : 'titles';
		var postId = $(this).data('post-id');
		$('#seofyme-ai-results').text('Generating…');
		$.post(seofymePremium.ajaxUrl, {
			action: 'seofyme_ai_generate',
			nonce: nonce(),
			post_id: postId,
			kind: kind
		}).done(function (res) {
			if (!res.success) {
				$('#seofyme-ai-results').text(res.data && res.data.message ? res.data.message : 'Error');
				return;
			}
			renderAi(res.data.items, kind);
		});
	});

	$(document).on('click', '#seofyme-content-ideas', function () {
		$.post(seofymePremium.ajaxUrl, {
			action: 'seofyme_content_ideas',
			nonce: nonce()
		}).done(function (res) {
			var $list = $('#seofyme-idea-list').empty();
			(res.data.ideas || []).forEach(function (idea) {
				var $li = $('<li/>');
				var $a = $('<a href="#" />').text(idea.title).data('topic', idea.topic);
				$a.on('click', function (e) {
					e.preventDefault();
					$('#title').val(idea.title).trigger('change');
					$list.data('selected-topic', idea.topic);
				});
				$li.append($a);
				$list.append($li);
			});
		});
	});

	$(document).on('click', '#seofyme-starter-draft', function () {
		var topic = $('#seofyme-idea-list').data('selected-topic') || 'this topic';
		$.post(seofymePremium.ajaxUrl, {
			action: 'seofyme_starter_draft',
			nonce: nonce(),
			topic: topic
		}).done(function (res) {
			if (!res.success) {
				return;
			}
			var content = res.data.content;
			if (window.wp && wp.data && wp.data.dispatch('core/editor')) {
				wp.data.dispatch('core/editor').editPost({ content: content });
			} else if ($('#content').length) {
				$('#content').val(content);
			} else {
				window.prompt('Starter draft HTML', content);
			}
		});
	});

	$(document).on('click', '.seofyme-bulk-draft', function () {
		var $row = $(this).closest('tr');
		var id = $row.data('id');
		$.post(seofymePremium.ajaxUrl, {
			action: 'seofyme_bulk_draft',
			nonce: nonce(),
			post_id: id
		}).done(function (res) {
			if (!res.success) {
				return;
			}
			if (res.data.title) {
				$row.find('.seofyme-bulk-title').val(res.data.title);
			}
			if (res.data.desc) {
				$row.find('.seofyme-bulk-desc').val(res.data.desc);
			}
		});
	});

	$(document).on('click', '.seofyme-bulk-apply', function () {
		var $row = $(this).closest('tr');
		$.post(seofymePremium.ajaxUrl, {
			action: 'seofyme_bulk_apply',
			nonce: nonce(),
			post_id: $row.data('id'),
			title: $row.find('.seofyme-bulk-title').val(),
			desc: $row.find('.seofyme-bulk-desc').val()
		}).done(function (res) {
			if (res.success) {
				$row.css('opacity', 0.55);
			}
		});
	});
})(jQuery);
