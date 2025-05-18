export default {
    components: {
        'team-leader-infos': () => import('./TeamLeaderInfos.js'),
        'team-leader-alerts': () => import('./TeamLeaderAlerts.js')
    },
    template: `
      <div>
        <team-leader-alerts></team-leader-alerts>
        <team-leader-infos></team-leader-infos>
      </div>
    `
};
