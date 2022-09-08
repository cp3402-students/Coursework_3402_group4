var el = wp.element.createElement,
	registerBlockType = wp.blocks.registerBlockType,
	ServerSideRender = wp.components.ServerSideRender,
	TextControl = wp.components.TextControl,
	InspectorControls = wp.blockEditor.InspectorControls;

registerBlockType("ssa/upcoming-appointments", {
	title: "Upcoming Appointments",
	icon: "calendar-alt",
	category: "widgets",

	edit: function (props) {
		var options = [
			{
				value: "No upcoming appointments",
				label:
					"Message to display if customer has no upcoming appointments",
			},
		];

		return [
			el(ServerSideRender, {
				block: "ssa/upcoming-appointments",
				attributes: props.attributes,
			}),

			el(
				InspectorControls,
				{},
				el(TextControl, {
					label:
						"Message to display if customer has no upcoming appointments",
					value: props.attributes.no_results_message,
					onChange: (value) => {
						props.setAttributes({ no_results_message: value });
					},
				})
			),
		];
	},

	save: function () {
		return null;
	},
});
