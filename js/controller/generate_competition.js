Ext.define('Ufolep13Volley.controller.generate_competition', {
    extend: 'Ext.app.Controller',
    stores: [],
    models: [],
    views: [],
    refs: [],
    init: function () {
        this.control(
            {
                'button[action=generate_competition]': {
                    click: this.display_generate_competition
                },
                'competitions_grid': {
                    added: this.add_generate_competition_button,
                    selectionchange: this.manage_display
                },
            }
        );
    },
    add_generate_competition_button: function (grid) {
        grid.down('toolbar').add(
            {
                xtype: 'button',
                text: 'Génération...',
                action: 'generate_competition',
                hidden: true,
            })
    },
    display_generate_competition: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
            return;
        }
        var ids = [];
        Ext.each(records, function (record) {
            ids.push(record.get('id'));
        });
        var window_form = Ext.create('Ext.window.Window', {
            title: "Génération",
            height: 500,
            width: 700,
            maximizable: true,
            layout: 'fit',
            items: {
                xtype: 'form',
                trackResetOnLoad: true,
                layout: 'form',
                url: '/rest/action.php/matchmgr/generateAll',
                items: [
                    {
                        xtype: 'hidden',
                        fieldLabel: 'ids',
                        name: 'ids',
                        value: ids.join(',')
                    },
                    {
                        xtype: 'checkbox',
                        fieldLabel: "Recréer les comptes ?",
                        name: 'do_reinit',
                        value: true,
                        uncheckedValue: 'off',
                        checkedValue: 'on',
                    },
                    {
                        xtype: 'checkbox',
                        fieldLabel: "Générer les journée ?",
                        name: 'generate_days',
                        value: true,
                        uncheckedValue: 'off',
                        checkedValue: 'on',
                    },
                    {
                        xtype: 'checkbox',
                        fieldLabel: "Générer les matchs ?",
                        name: 'generate_matches',
                        value: true,
                        uncheckedValue: 'off',
                        checkedValue: 'on',
                    },
                ],
                buttons: [
                    {
                        text: 'Annuler',
                        action: 'cancel',
                    },
                    {
                        text: 'Sauver',
                        formBind: true,
                        disabled: true,
                        handler: function (button) {
                            var form = button.up('form').getForm();
                            if (form.isValid()) {
                                form.submit({
                                    success: function () {
                                        button.up('window').close();
                                    },
                                    failure: function (form, action) {
                                        Ext.Msg.alert('Erreur', action.result.message);
                                    }
                                });
                            }
                        }
                    }
                ]
            }
        });
        window_form.show();

    },
    manage_display: function (selection_model, selected) {
        var button = selection_model.view.ownerCt.down('button[action=generate_competition]');
        var is_hidden = false;
        if (Ext.isEmpty(selected)) {
            is_hidden = true;
        }
        if (!Ext.isArray(selected)) {
            is_hidden = true;
        }
        button.setHidden(is_hidden);
    },
});