Ext.define('Ufolep13Volley.model.Player', {
    extend: 'Ext.data.Model',
    fields: [
        'full_name',
        'prenom',
        'nom',
        'telephone',
        'email',
        'num_licence',
        'num_licence_ext',
        'path_photo',
        {
            name: 'photo',
            type: 'string',
            convert: function (val, rec) {
                if (Ext.isEmpty(rec.get('path_photo'))) {
                    return '';
                }
                return Ext.String.format("<img src='{0}' width='50px' height='50px'/>", rec.get('path_photo'));
            }
        },
        'sexe',
        {
            name: 'departement_affiliation',
            type: 'int'
        },
        {
            name: 'est_actif',
            type: 'bool'
        },
        {
            name: 'id_club',
            type: 'int'
        },
        'club',
        'telephone2',
        'email2',
        {
            name: 'est_responsable_club',
            type: 'bool',
        },
        {
            name: 'is_captain',
            type: 'bool',
        },
        {
            name: 'is_leader',
            type: 'bool',
        },
        {
            name: 'is_vice_leader',
            type: 'bool',
        },
        {
            name: 'id',
            type: 'int'
        },
        'active_teams_list',
        'inactive_teams_list',
        'teams_list',
        {
            name: 'date_homologation',
            type: 'date',
            dateFormat: 'd/m/Y'
        },
        'adresse',
        'team_leader_list',
        'code_postal',
        'ville',
        'telephone3',
        'telephone4',
        // uniquement pour match_player
        {
            name: 'date_reception',
            type: 'date',
            dateFormat: 'd/m/Y'
        },
        {
            name: 'is_valid_for_match',
            type: 'bool',
            convert: function (val, record) {
                return record.get('est_actif') &&
                    record.get('date_homologation') &&
                    record.get('date_homologation') <= record.get('date_reception');
            }
        },
        {
            name: 'role',
            type: 'string',
            convert: function (val, record) {
                var value = '';
                if(record.get('is_captain')) {
                    value += 'Capitaine<br/>';
                }
                if(record.get('is_leader')) {
                    value += 'Responsable<br/>';
                }
                if(record.get('is_vice_leader')) {
                    value += 'Suppléant<br/>';
                }
                return value;
            }
        },
        {
            name: 'id_match',
            type: 'int',
        }
    ]
});
