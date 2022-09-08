import React, { Fragment, useEffect, useRef } from 'react';
import { monitorMeetingPreviewRender } from '../../../api/hubspotPluginApi';
import UIOverlay from '../../UIComponents/UIOverlay';
import useMeetingsScript from './useMeetingsScript';

export default function PreviewForm({ url }) {
  const [run, firstRun] = useMeetingsScript();
  const inputEl = useRef();
  const reloadDetection = useRef(url);

  useEffect(() => {
    if (!url) {
      return;
    } else if (reloadDetection.current !== url || !firstRun) {
      reloadDetection.current = url;
      inputEl.current.innerHTML = '';
      run();
    }
  }, [url, run, firstRun]);

  useEffect(() => {
    monitorMeetingPreviewRender();
  }, []);

  return (
    <Fragment>
      {url && (
        <UIOverlay
          className="meetings-iframe-container"
          data-src={`${url}?embed=true`}
          ref={inputEl}
        ></UIOverlay>
      )}
    </Fragment>
  );
}
