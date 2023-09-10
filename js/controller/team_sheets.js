Ext.define('Ufolep13Volley.controller.team_sheets', {
    extend: 'Ext.app.Controller',
    stores: [
        'Ufolep',
        'match',
        'MatchPlayers',
        'NotMatchPlayers',
        'ReinforcementPlayers',
    ],
    models: ['Match', 'Player'],
    views: [
        'form.MatchPlayers',
        'view.MatchPlayers',
        'form.field.tag.players',
        'form.field.combo.player',
    ],
    refs: [],
    init: function () {
        this.control({
            'form_match_players': {
                added: this.load_match_players
            },
            'button[action=save]': {
                click: this.save_form
            },
            'button[action=sign_team_sheet]': {
                click: this.sign_team_sheet
            },
        });
    },
    sign_team_sheet: function (button) {
        var window_show = Ext.Msg.show({
            title: "Signer la fiche équipe ?",
            message: "Je confirme avoir pris connaissance des joueurs/joueuses présent(e)s.<br/>" +
                "Les personnes présentes pour ce match ont été déclarées présentes sur le site, sur la page de gestion du match.<br/>" +
                "En signant numériquement la fiche équipe, il n'est plus nécessaire de fournir de fiche équipe au format papier.<br/>" +
                "Merci de signer en cliquant sur OK, ou de passer par un format papier en cliquant sur Annuler.",
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn == 'ok') {
                    button.up('viewport').setLoading(true);
                    Ext.Ajax.request({
                        url: 'rest/action.php/matchmgr/sign_team_sheet',
                        params: {
                            id_match: id_match
                        },
                        success: function () {
                            button.up('viewport').setLoading(false);
                            Ext.Msg.alert('OK', "Signature prise en compte, merci de recharger la page.");
                        },
                        failure: function (response) {
                            button.up('viewport').setLoading(false);
                            var resp = Ext.decode(response.responseText);
                            Ext.Msg.alert('Erreur', resp.message);
                        },
                    });
                }
                window_show.close();
            }
        });
    },
    load_match_players: function (form) {
        var store = form.down('view_match_players').getStore();
        store.load({
            params: {
                id_match: id_match
            }
        });
        store = form.down("tagfield[name='player_ids[]']").getStore();
        store.load({
            params: {
                id_match: id_match
            }
        });
        form.down('combo[name=reinforcement_player_id]').getStore().getProxy().setExtraParams({
            id_match: id_match
        });
        form.down('field[name=id_match]').setValue(id_match);
        var store = Ext.data.StoreManager.lookup('match');
        store.load({
            params: {
                id_match: id_match
            },
            callback: function (records, operation, success) {
                form.up('viewport').down('displayfield[name=confrontation-tbar]').setValue(records[0].get('confrontation'))
            }
        });
    },
    save_form: function (button) {
        var me = this;
        var form = button.up('form').getForm();
        if (form.isValid()) {
            var dirtyFieldsJson = form.getFieldValues(true);
            var dirtyFieldsArray = [];
            for (var key in dirtyFieldsJson) {
                dirtyFieldsArray.push(key);
            }
            form.submit({
                params: {
                    dirtyFields: dirtyFieldsArray.join(',')
                }, success: function () {
                    Ext.Msg.alert('Info', this.result.message);
                    me.load_match_players(button.up('form'));
                }, failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }

    },
});