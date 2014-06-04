Ext.define('Ufolep13Volley.model.Classement', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id_equipe',
            type: 'int'
        },
        {
            name: 'code_competition',
            type: 'string'
        },
        {
            name: 'equipe',
            type: 'string'
        },
        {
            name: 'points',
            type: 'int'
        },
        {
            name: 'joues',
            type: 'int'
        },
        {
            name: 'gagnes',
            type: 'int'
        },
        {
            name: 'perdus',
            type: 'int'
        },
        {
            name: 'sets_pour',
            type: 'int'
        },
        {
            name: 'sets_contre',
            type: 'int'
        },
        {
            name: 'diff',
            type: 'int'
        },
        {
            name: 'coeff_s',
            type: 'float'
        },
        {
            name: 'points_pour',
            type: 'int'
        },
        {
            name: 'points_contre',
            type: 'int'
        },
        {
            name: 'coeff_p',
            type: 'float'
        },
        {
            name: 'penalites',
            type: 'int'
        },
        {
            name: 'rang',
            type: 'int'
        },
        {
            name: 'matches_won_with_5_players_count',
            type: 'int'
        },
        {
            name: 'matches_lost_by_forfeit_count',
            type: 'int'
        }
    ]
});
