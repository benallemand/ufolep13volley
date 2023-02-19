Ext.define('Ufolep13Volley.controller.match', {
    extend: 'Ext.app.Controller',
    stores: ['match', 'MatchPlayers', 'Players'],
    models: ['Match', 'Player'],
    views: [
        'form.match',
        'form.MatchPlayers',
        'view.MatchPlayers',
        'form.field.tag.players',
    ],
    refs: [],
    init: function () {
        this.control({
            'form_match': {
                added: this.load_match
            },
            'form_match_players': {
                added: this.load_match_players
            },
            'button[action=save]': {
                click: this.save_form
            },
            'button[action=sign_team_sheet]': {
                click: this.sign_team_sheet
            },
            'button[action=sign_match_sheet]': {
                click: this.sign_match_sheet
            },
        });
    },
    sign_team_sheet: function (button) {
        var window_show = Ext.Msg.show({
            title: "Signer la fiche équipe ?",
            message: "Je confirme avoir pris connaissance des joueurs/joueuses présent(e)s.<br/>" +
                "les mêmes personnes ont été déclarées présentes sur le site, sur la page de gestion du match.<br/>" +
                "En signant numériquement la fiche équipe, il n'est plus nécessaire de fournir de fiche équipe au format papier.<br/>" +
                "Merci de signer en cliquant sur OK, ou de passer par un format papier en cliquant sur Annuler.",
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn == 'ok') {
                    Ext.Ajax.request({
                        url: 'rest/action.php/matchmgr/sign_team_sheet',
                        params: {
                            id_match: id_match
                        },
                        success: function () {
                            window.location.reload();
                        },
                        failure: function (form, action) {
                            Ext.Msg.alert('Erreur', action.result.message);
                        },
                    });
                }
                window_show.close();
            }
        });
    },
    sign_match_sheet: function (button) {
        var window_show = Ext.Msg.show({
            title: "Signer la feuille de match ?",
            message: "Je confirme avoir pris connaissance du score saisi sur le site.<br/>" +
                "En signant numériquement la feuille de match, il n'est plus nécessaire de fournir de feuille de match au format papier.<br/>" +
                "Merci de signer en cliquant sur OK, ou de passer par un format papier en cliquant sur Annuler.",
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn == 'ok') {
                    Ext.Ajax.request({
                        url: 'rest/action.php/matchmgr/sign_match_sheet',
                        params: {
                            id_match: id_match
                        },
                        success: function () {
                            window.location.reload();
                        },
                        failure: function (form, action) {
                            Ext.Msg.alert('Erreur', action.result.message);
                        },
                    });
                }
                window_show.close();
            }
        });
    },
    load_match: function (form) {
        var store = Ext.data.StoreManager.lookup('match');
        store.load({
            params: {
                id_match: id_match
            },
            callback: function (records, operation, success) {
                form.loadRecord(records[0]);
            }
        })
    },
    load_match_players: function (form) {
        var store = form.down('tagfield').getStore();
        store.load({
            params: {
                id_match: id_match
            }
        });
        store = Ext.data.StoreManager.lookup('MatchPlayers');
        store.load({
            params: {
                id_match: id_match
            }
        });
        form.down('field[name=id_match]').setValue(id_match);
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
                    switch (button.up('form').getXType()) {
                        case 'form_match_players':
                            me.load_match_players(button.up('form'));
                            break;
                        case 'form_match':
                            me.load_match(button.up('form'));
                            break;
                        default:
                            return;
                    }
                }, failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }

    },
});