Ext.define('Ufolep13Volley.controller.reset_password', {
    extend: 'Ext.app.Controller',
    stores: [
        'Teams',
    ],
    models: [
        'Team',
    ],
    views: [
        'form.reset_password',
        'form.field.combo.team',
    ],
    refs: [],
    init: function () {
        this.control({
            'button[action=save]': {
                click: this.save_form
            },
            'button[action=cancel]': {
                click: this.cancel_form
            },
        });
    },
    cancel_form: function (button) {
        var form = button.up('form');
        form.getForm().getFields().each(function (f) {
            f.originalValue = undefined;
        });
        form.getForm().reset();
    },
    save_form: function (button) {
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
                }, success: function (form, action) {
                    if (action.result.success === true) {
                        Ext.Msg.alert('Info', action.result.message);
                    }
                }, failure: function (form, action) {
                    Ext.Msg.alert('Erreur',
                        action.result ?
                            action.result.message :
                            Ext.decode(action.response.responseText).message);
                }
            });
        }

    },
});