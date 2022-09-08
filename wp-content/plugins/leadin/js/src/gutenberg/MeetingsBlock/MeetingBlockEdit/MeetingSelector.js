import React, { Fragment } from 'react';
import UISelect from '../../UIComponents/UISelect';
import { i18n } from '../../../constants/leadinConfig';
import UISpacer from '../../UIComponents/UISpacer';

export default function MeetingSelector({ options, onChange, value }) {
  const optionsWrapper = [
    {
      label: i18n.meetingName,
      options,
    },
  ];

  return (
    <Fragment>
      <UISpacer />
      <p data-test-id="leadin-form-select">
        <b>{i18n.selectExistingMeeting}</b>
      </p>
      <UISelect
        defaultOptions={optionsWrapper}
        cacheOptions={true}
        onChange={onChange}
        placeholder={i18n.selectMeeting}
        value={value}
      />
    </Fragment>
  );
}
