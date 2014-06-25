Ext.define('Ufolep13Volley.model.mobile.Player', {
    extend: 'Ext.data.Model',
    config: {
        fields: [
            'full_name',
            'prenom',
            'nom',
            'telephone',
            'email',
            'num_licence',
            'path_photo',
            'sexe',
            {
                name: 'departement_affiliation',
                type: 'int'
            },
            {
                name: 'est_actif',
                type: 'bool',
                convert: function(val) {
                    return val === '1';
                }
            },
            {
                name: 'id_club',
                type: 'int'
            },
            'club',
            'adresse',
            'code_postal',
            'ville',
            'telephone2',
            'email2',
            'telephone3',
            'telephone4',
            {
                name: 'est_licence_valide',
                type: 'bool',
                convert: function(val) {
                    return val === '1';
                }
            },
            {
                name: 'est_responsable_club',
                type: 'bool',
                convert: function(val) {
                    return val === '1';
                }
            },
            {
                name: 'id',
                type: 'int'
            },
            {
                name: 'date_homologation',
                type: 'date',
                dateFormat: 'Y-m-d'
            },
            {
                name: 'show_photo',
                type: 'bool',
                convert: function(val) {
                    return val === '1';
                }
            },
            'team_leader_list',
            'teams_list',
            {
                name: 'is_captain',
                type: 'bool',
                convert: function(val) {
                    return val === '1';
                }
            },
            {
                name: 'is_leader',
                type: 'bool',
                convert: function(val) {
                    return val === '1';
                }
            },
            {
                name: 'is_vice_leader',
                type: 'bool',
                convert: function(val) {
                    return val === '1';
                }
            }
        ]
    }
});
