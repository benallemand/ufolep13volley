Ext.define('Ufolep13Volley.model.register', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'id', type: 'int'},
        {name: 'new_team_name', type: 'string'},
        {name: 'competition', type: 'string'},
        {name: 'id_club', type: 'int'},
        {name: 'old_team_id', type: 'int'},
        {name: 'leader_name', type: 'string'},
        {name: 'leader_first_name', type: 'string'},
        {name: 'leader_email', type: 'string'},
        {name: 'leader_phone', type: 'string'},
        {name: 'id_court_1', type: 'int'},
        {name: 'day_court_1', type: 'string'},
        {name: 'hour_court_1', type: 'string'},
        {name: 'id_court_2', type: 'int'},
        {name: 'day_court_2', type: 'string'},
        {name: 'hour_court_2', type: 'string'},
        {name: 'creation_date', type: 'date', dateFormat: 'd/m/Y H:i:s'},
        {name: 'division', type: 'string'},
        {name: 'rank_start', type: 'int', allowNull: true},
        {name: 'remarks', type: 'string'},
        {
            name: 'is_paid',
            type: 'bool',
        },
        {
            name: 'leader',
            convert: function (val, rec) {
                return Ext.String.format("{0} {1}", rec.get('leader_first_name'), rec.get('leader_name'));
            }
        }
    ]
});
