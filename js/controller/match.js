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