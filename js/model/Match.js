Ext.define('Ufolep13Volley.model.Match', {
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
            name: 'parent_code_competition',
            type: 'string'
        },
        {
            name: 'libelle_competition',
            type: 'string'
        },
        {
            name: 'division',
            type: 'string'
        },
        {
            name: 'id_journee',
            type: 'int'
        },
        {
            name: 'journee',
            type: 'string'
        },
        {
            name: 'id_gymnasium',
            type: 'int'
        },
        {
            name: 'gymnasium',
            type: 'string'
        },
        {
            name: 'id_equipe_dom',
            type: 'int'
        },
        {
            name: 'id_equipe_ext',
            type: 'int'
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
            dateFormat: 'd/m/Y'
        },
        {
            name: 'date_original',
            type: 'date',
            dateFormat: 'd/m/Y'
        },
        {
            name: 'forfait_dom',
            type: 'bool',
        },
        {
            name: 'is_match_player_filled',
            type: 'bool',
        },
        {
            name: 'count_status',
            type: 'string',
        },
        {
            name: 'is_forfait',
            type: 'bool',
        },
        {
            name: 'is_match_player_requested',
            type: 'bool',
        },
        {
            name: 'has_forbidden_player',
            type: 'bool',
        },
        {
            name: 'forfait_ext',
            type: 'bool',
        },
        {
            name: 'sheet_received',
            type: 'bool',
        },
        {
            name: 'certif',
            type: 'bool',
        },
        {
            name: 'retard',
            type: 'int'
        },
        {
            name: 'note',
            type: 'string'
        },
        {
            name: 'match_status',
            type: 'string'
        },
        {
            name: 'confrontation',
            convert: function (val, record) {
                return Ext.String.format("<h2>{0} vs {1}</h2>", record.get('equipe_dom'), record.get('equipe_ext'))

            }
        },
        {
            name: 'resultat',
            convert: function (val, record) {
                return record.get('score_equipe_dom') + record.get('score_equipe_ext') >= 3 ? Ext.String.format("{0}/{1} ({2}{3}{4}{5}{6})",
                    record.get('score_equipe_dom'),
                    record.get('score_equipe_ext'),
                    record.get('set_1_dom') + record.get('set_1_ext') >= 25 ? Ext.String.format(" {0}-{1}", record.get('set_1_dom'), record.get('set_1_ext')) : '',
                    record.get('set_2_dom') + record.get('set_2_ext') >= 25 ? Ext.String.format(" {0}-{1}", record.get('set_2_dom'), record.get('set_2_ext')) : '',
                    record.get('set_3_dom') + record.get('set_3_ext') >= 25 ? Ext.String.format(" {0}-{1}", record.get('set_3_dom'), record.get('set_3_ext')) : '',
                    record.get('set_4_dom') + record.get('set_4_ext') >= 25 ? Ext.String.format(" {0}-{1}", record.get('set_4_dom'), record.get('set_4_ext')) : '',
                    record.get('set_5_dom') + record.get('set_5_ext') >= 15 ? Ext.String.format(" {0}-{1}", record.get('set_5_dom'), record.get('set_5_ext')) : '',
                ) : '';

            }
        },
        {
            name: 'is_validation_ready',
            convert: function (val, record) {
                return (
                    !record.get('certif')
                    && record.get('is_sign_team_dom')
                    && record.get('is_sign_team_ext')
                    && record.get('is_sign_match_dom')
                    && record.get('is_sign_match_ext')
                    && record.get('is_survey_filled_dom')
                    && record.get('is_survey_filled_ext')
                    && record.get('is_match_player_filled')
                    && !record.get('is_match_player_requested')
                    && !record.get('has_forbidden_player')
                    && !Ext.isEmpty(record.get('count_status'))
                    && record.get('match_status') === 'CONFIRMED');
            }
        },
        {name: 'is_sign_team_dom', type: 'bool'},
        {name: 'is_sign_team_ext', type: 'bool'},
        {name: 'is_sign_match_dom', type: 'bool'},
        {name: 'is_sign_match_ext', type: 'bool'},
        {name: 'is_survey_filled_dom', type: 'bool'},
        {name: 'is_survey_filled_ext', type: 'bool'},
        {name: 'email_dom', type: 'string'},
        {name: 'email_ext', type: 'string'},
        {name: 'referee', type: 'string'},
    ]
});
