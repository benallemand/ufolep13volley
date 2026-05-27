export default {
    components: {
        'team-leader-infos': Vue.defineAsyncComponent(() => import('./TeamLeaderInfos.js')),
        'team-leader-alerts': Vue.defineAsyncComponent(() => import('./TeamLeaderAlerts.js'))
    },
    template: `
      <div>
        <team-leader-alerts></team-leader-alerts>
        <team-leader-infos></team-leader-infos>
      </div>
    `
};
