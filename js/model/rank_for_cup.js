Ext.define('Ufolep13Volley.model.rank_for_cup', {
    extend: 'Ext.data.Model',
    fields: [
        {name: 'rang',type: 'int'},
        {name: 'id_equipe',type: 'int'},
        {name: 'equipe',type: 'string'},
        {name: 'points',type: 'int'},
        {name: 'joues',type: 'int'},
        {name: 'gagnes',type: 'int'},
        {name: 'perdus',type: 'int'},
        {name: 'sets_pour',type: 'int'},
        {name: 'sets_contre',type: 'int'},
        {name: 'diff_sets',type: 'int'},
        {name: 'points_pour',type: 'int'},
        {name: 'points_contre',type: 'int'},
        {name: 'diff_points',type: 'int'},
        {name: 'penalites',type: 'int'},
        {name: 'matches_lost_by_forfeit_count',type: 'int'},
        {name: 'report_count',type: 'int'},
    ]
});
