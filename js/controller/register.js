Ext.define('Ufolep13Volley.controller.register', {
    extend: 'Ext.app.Controller',
    stores: [
        'register',
        'Clubs',
        'Teams',
        'Gymnasiums',
        'Competitions',
    ],
    models: [
        'register',
        'Club',
        'Team',
        'Gymnasium',
        'Competition',
    ],
    views: [
        'grid.register',
        'form.register',
        'form.field.combo.club',
        'form.field.combo.court',
        'form.field.combo.day',
        'form.field.combo.hour',
        'form.field.combo.team',
        'form.field.combo.competition',
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
            'grid_register': {
                selectionchange: this.manage_display
            },
        });
    },
    manage_display: function (selection_model, selected) {
        var form = selection_model.view.ownerCt.up('viewport').down('form');
        if (selected.length === 1) {
            Ext.Msg.prompt("Identification",
                "Pour modifier votre inscription, veuillez saisir l'adresse email du responsable d'équipe:",
                function (btn, text) {
                    if (btn == 'ok') {
                        if (text == selected[0].get('leader_email')) {
                            form.loadRecord(selected[0]);
                        } else {
                            Ext.Msg.alert("Erreur",
                                "Ce n'est pas la bonne adresse email.");
                        }
                    }
                });
        } else {
            form.getForm().getFields().each(function (f) {
                f.originalValue = undefined;
            });
            form.getForm().reset();
        }
    },
    cancel_form: function (button) {
        var form = button.up('form');
        form.getForm().getFields().each(function (f) {
            f.originalValue = undefined;
        });
        form.getForm().reset();
    },
    save_form: function (button) {
        var viewport = button.up('viewport');
        var grid = viewport.down('grid');
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
                    grid.getStore().load();
                }, failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }

    },
});