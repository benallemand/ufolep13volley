Ext.define('Ufolep13Volley.model.Player', {
    extend: 'Ext.data.Model',
    fields: [
        'full_name',
        'prenom',
        'nom',
        'telephone',
        'email',
        'num_licence',
        {
            name: 'date_homologation',
            type: 'date',
            dateFormat: 'd/m/Y'
        },
        {
            name: 'path_photo',
            type: 'string',
            convert: function (val, rec) {
                if (!rec.get('show_photo')) {
                    switch (rec.get('sexe')) {
                        case 'M':
                            return 'images/MalePhotoNotAllowed.png';
                        case 'F':
                            return 'images/FemalePhotoNotAllowed.png';
                        default:
                            break;
                    }
                }
                return val;
            }
        },
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
            type: 'bool',
            convert: function (val) {
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
            name: 'est_responsable_club',
            type: 'bool',
            convert: function (val) {
                return val === '1';
            }
        },
        {
            name: 'id',
            type: 'int'
        },
        {
            name: 'show_photo',
            type: 'bool',
            convert: function (val) {
                return val === '1';
            }
        },
        'team_leader_list',
        'teams_list',
        {
            name: 'is_captain',
            type: 'bool',
            convert: function (val) {
                return val === '1';
            }
        },
        {
            name: 'is_leader',
            type: 'bool',
            convert: function (val) {
                return val === '1';
            }
        },
        {
            name: 'is_vice_leader',
            type: 'bool',
            convert: function (val) {
                return val === '1';
            }
        },
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
            name: 'id_match',
            type: 'int',
        }
    ]
});
