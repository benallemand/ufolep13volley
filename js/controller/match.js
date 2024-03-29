Ext.define('Ufolep13Volley.controller.match', {
    extend: 'Ext.app.Controller',
    stores: ['match',],
    models: ['Match',],
    views: [
        'form.match',
    ],
    refs: [],
    init: function () {
        this.control({
            'form_match': {
                added: this.load_match
            },
            'button[action=save]': {
                click: this.save_form
            },
            'button[action=sign_match_sheet]': {
                click: this.sign_match_sheet
            },
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
                    button.up('viewport').setLoading(true);
                    Ext.Ajax.request({
                        url: 'rest/action.php/matchmgr/sign_match_sheet',
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
    load_match: function (form) {
        var store = Ext.data.StoreManager.lookup('match');
        store.load({
            params: {
                id_match: id_match
            },
            callback: function (records, operation, success) {
                form.loadRecord(records[0]);
                form.up('viewport').down('displayfield[name=confrontation-tbar]').setValue(records[0].get('confrontation'))
            }
        })
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
                    me.load_match(button.up('form'));
                }, failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }

    },
});