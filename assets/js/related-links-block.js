(function (blocks, element, blockEditor, components, serverSideRender) {
	'use strict';
	var el = element.createElement;
	var InspectorControls = blockEditor.InspectorControls;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var RangeControl = components.RangeControl;
	var ServerSideRender = serverSideRender.default || serverSideRender;

	blocks.registerBlockType('seofyme/related-links', {
		edit: function (props) {
			return el(
				element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Related links', initialOpen: true },
						el(TextControl, {
							label: 'Heading',
							value: props.attributes.title,
							onChange: function (v) {
								props.setAttributes({ title: v });
							}
						}),
						el(RangeControl, {
							label: 'Number of links',
							value: props.attributes.count,
							min: 1,
							max: 12,
							onChange: function (v) {
								props.setAttributes({ count: v });
							}
						})
					)
				),
				el(ServerSideRender, {
					block: 'seofyme/related-links',
					attributes: props.attributes
				})
			);
		},
		save: function () {
			return null;
		}
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.serverSideRender
);
