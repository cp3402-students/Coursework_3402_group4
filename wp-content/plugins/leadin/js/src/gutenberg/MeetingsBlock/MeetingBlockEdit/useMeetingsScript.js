import $ from 'jquery';

import { useState } from 'react';
import { meetingsScript } from '../../../constants/leadinConfig';
import Raven from '../../../lib/Raven';

function loadMeetingsScript() {
  return new Promise((resolve, reject) =>
    $.getScript(meetingsScript)
      .done(resolve)
      .fail(reject)
  );
}

export default function useMeetingsScript() {
  const [ready, setReady] = useState(false);

  const run = () =>
    loadMeetingsScript()
      .then(() => setReady(true))
      .catch(error => Raven.captureException(error));

  return [run, ready];
}
