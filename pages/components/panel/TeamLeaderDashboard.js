import { defineAsyncComponent } from 'vue';

export default {
    components: {
        'team-leader-infos': defineAsyncComponent(() => import('./TeamLeaderInfos.js')),
        'team-leader-alerts': defineAsyncComponent(() => import('./TeamLeaderAlerts.js'))
    },
    template: `
      <div>
        <team-leader-alerts></team-leader-alerts>
        <team-leader-infos></team-leader-infos>
      </div>
    `
};
