Ext.define('Ufolep13Volley.model.Match', Sencha.modelCompatibility({
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'id_match',
            type: 'int'
        },
        {
            name: 'code_match',
            type: 'string'
        },
        {
            name: 'code_competition',
            type: 'string'
        },
        {
            name: 'libelle_competition',
            type: 'string'
        },
        {
            name: 'division',
            type: 'int'
        },
        {
            name: 'journee',
            type: 'string'
        },
        {
            name: 'id_equipe_dom',
            type: 'string'
        },
        {
            name: 'id_equipe_ext',
            type: 'string'
        },
        {
            name: 'equipe_dom',
            type: 'string'
        },
        {
            name: 'equipe_ext',
            type: 'string'
        },
        {
            name: 'score_equipe_dom',
            type: 'int'
        },
        {
            name: 'score_equipe_ext',
            type: 'int'
        },
        {
            name: 'set_1_dom',
            type: 'int'
        },
        {
            name: 'set_1_ext',
            type: 'int'
        },
        {
            name: 'set_2_dom',
            type: 'int'
        },
        {
            name: 'set_2_ext',
            type: 'int'
        },
        {
            name: 'set_3_dom',
            type: 'int'
        },
        {
            name: 'set_3_ext',
            type: 'int'
        },
        {
            name: 'set_4_dom',
            type: 'int'
        },
        {
            name: 'set_4_ext',
            type: 'int'
        },
        {
            name: 'set_5_dom',
            type: 'int'
        },
        {
            name: 'set_5_ext',
            type: 'int'
        },
        {
            name: 'heure_reception',
            type: 'string'
        },
        {
            name: 'date_reception',
            type: 'date',
            dateFormat: 'Y-m-d'
        },
        {
            name: 'forfait_dom',
            type: 'bool',
            convert: function (val) {
                return val === '1';
            }
        },
        {
            name: 'forfait_ext',
            type: 'bool',
            convert: function (val) {
                return val === '1';
            }
        },
        {
            name: 'certif',
            type: 'bool',
            convert: function (val) {
                return val === '1';
            }
        },
        {
            name: 'report',
            type: 'bool',
            convert: function (val) {
                return val === '1';
            }
        },
        {
            name: 'retard',
            type: 'int'
        }

    ]
}));
