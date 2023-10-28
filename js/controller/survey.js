Ext.define('Ufolep13Volley.controller.survey', {
    extend: 'Ext.app.Controller',
    stores: ['survey',],
    models: ['survey',],
    views: [
        'form.survey',
    ],
    refs: [],
    init: function () {
        this.control({
            'form_survey': {
                added: this.load_survey
            },
            'button[action=save]': {
                click: this.save_form
            },
        });
    },
    load_survey: function (form) {
        var store = Ext.data.StoreManager.lookup('survey');
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
                    me.load_survey(button.up('form'));
                }, failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }

    },
});