var el = wp.element.createElement,
	registerBlockType = wp.blocks.registerBlockType,
	ServerSideRender = wp.ServerSideRender,
	SelectControl = wp.components.SelectControl,
	RangeControl = wp.components.RangeControl,
	InspectorControls = wp.blockEditor.InspectorControls,
	PanelColorSettings = wp.blockEditor.PanelColorSettings,
	PanelBody = wp.components.PanelBody;

registerBlockType("ssa/booking", {
	title: "Appointment Booking Form",
	description:
		"Displays an Appointment Booking Form. You can customize the appointment type and styles.",
	icon: "calendar-alt",
	category: "widgets",

	edit: function (props) {
		var options = [
			{
				value: "",
				label: "All",
			},
		];
		Object.keys(ssaAppointmentTypes).forEach(function (key) {
			options.push({
				value: key,
				label: ssaAppointmentTypes[key],
			});
		});

		return [
			el(
				"div",
				{ className: "ssa-block-container" },
				el(ServerSideRender, {
					block: "ssa/booking",
					attributes: props.attributes,
				}),
				el("div", {
					className: "ssa-block-handler",
				})
			),

			el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: "Appointment Type", initialOpen: true },
					el(SelectControl, {
						// label: "Appointment Type",
						value: props.attributes.type,
						options: options,
						onChange: function (value) {
							props.setAttributes({ type: value });
						},
					})
				),
				el(PanelColorSettings, {
					title: "Colors",
					colorSettings: [
						{
							value: props.attributes.accent_color,
							label: "Accent Color",
							onChange: function (value) {
								props.setAttributes({
									accent_color: value,
								});
							},
						},
						{
							value: props.attributes.background,
							label: "Background Color",
							onChange: function (value) {
								props.setAttributes({
									background: value,
								});
							},
						},
					],
				}),
				el(
					PanelBody,
					{ title: "Padding", initialOpen: true },
					el(RangeControl, {
						initialPosition: 0,
						value: props.attributes.padding,
						onChange: function (value) {
							props.setAttributes({
								padding: value,
							});
						},
						min: 0,
						max: 100,
					}),
					el(SelectControl, {
						label: "Padding Unit",
						value: props.attributes.padding_unit,
						options: [
							{
								value: "px",
								label: "px",
							},
							{
								value: "em",
								label: "em",
							},
							{
								value: "rem",
								label: "rem",
							},
							{
								value: "vw",
								label: "vw",
							},
							{
								value: "percent",
								label: "%",
							},
						],
						onChange: function (value) {
							props.setAttributes({ padding_unit: value });
						},
					})
				)
			),
		];
	},

	save: function () {
		return null;
	},
});
