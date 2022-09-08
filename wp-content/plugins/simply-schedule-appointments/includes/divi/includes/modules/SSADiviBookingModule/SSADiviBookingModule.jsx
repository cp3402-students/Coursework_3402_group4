/**
 * Steps to build this Divi Module:
 *
 * 1. cd into /divi/ folder
 * 2. If it's the first time you're building this file, run 'yarn install' first
 * 3. Then finally build the module by running 'yarn build'
 * 4. Done!
 */

// External Dependencies
import React, { Component } from "react";
import purify from "dompurify";

class SSADiviBookingModule extends Component {
  constructor(props) {
    super(props);
    this.state = {
      output: null,
      endpoint: `${window.wpApiSettings.root}ssa/v1/embed`
    };
  }

  static slug = "ssa_divi_booking_module";

  propsToParams() {
    let params = {};
    if (this.props.appointment_type) {
      params.appointment_type = this.props.appointment_type;
    }
    if (this.props.accent_color) {
      params.accent_color = this.props.accent_color.replace("#", "");
    }
    if (this.props.background_color) {
      params.background_color = this.props.background_color.replace("#", "");
    }
    if (this.props.font_family) {
      const font = this.props.font_family.split("|");
      params.font = font[0];
    }
    if (this.props.padding && this.props.padding_css_unit) {
      params.padding = `${this.props.padding}${this.props.padding_css_unit}`;
    }

    return params;
  }

  getShortcodeOutput(params) {
    window.jQuery
      .ajax({
        url: this.state.endpoint,
        data: params,
        method: "GET",
        beforeSend: function(xhr) {
          xhr.setRequestHeader("X-WP-Nonce", window.wpApiSettings.nonce);
        }
      })
      .done(response => {
        this.setState(state => {
          return {
            output: purify.sanitize(response, {
              ADD_TAGS: ["iframe"],
              ADD_ATTR: ["allow", "allowfullscreen", "frameborder", "scrolling"]
            })
          };
        });
      })
      .fail(xhr => {
        console.log(xhr);
      });
  }

  getRenderedOutput() {
    return {
      __html: this.state.output
    };
  }

  componentDidUpdate(prevProps) {
    if (
      this.state.output !== null &&
      prevProps.appointment_type === this.props.appointment_type &&
      prevProps.font_family === this.props.font_family &&
      prevProps.accent_color === this.props.accent_color &&
      prevProps.background_color === this.props.background_color &&
      prevProps.padding === this.props.padding &&
      prevProps.padding_css_unit === this.props.padding_css_unit
    ) {
      return;
    }
    const params = this.propsToParams();
    this.getShortcodeOutput(params);
  }

  render() {
    return (
      <div className="divi-module-ssa-booking-wrapper">
        <div
          className="ssa-booking"
          dangerouslySetInnerHTML={this.getRenderedOutput()}
        ></div>
      </div>
    );
  }
}

export default SSADiviBookingModule;
