Ext.define('Ufolep13Volley.controller.manage_commission', {
    extend: 'Ext.app.Controller',
    stores: [
        'commission',
    ],
    models: [
        'commission',
    ],
    views: [
        'grid.commission',
        'form.commission',
    ],
    refs: [],
    init: function () {
        this.control(
            {
                'button[text=Menu]': {
                    added: this.add_menu_commission
                },
                'menuitem[action=display_commission]': {
                    click: this.show_grid
                },
                'button[action=set_attribution]': {
                    click: this.set_attribution
                },
                'button[action=create]': {
                    click: this.create
                },
                'button[action=edit]': {
                    click: this.edit
                },
                'button[action=delete]': {
                    click: this.delete
                },
                'grid_commission': {
                    added: this.add_action_buttons,
                    selectionchange: this.manage_display
                },
            }
        );
    },
    add_action_buttons: function (grid) {
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                {
                    xtype: 'button',
                    text: 'créer',
                    action: 'create',
                    hidden: false,
                },
                {
                    xtype: 'button',
                    text: 'modifier',
                    action: 'edit',
                    hidden: true,
                },
                {
                    xtype: 'button',
                    text: 'supprimer',
                    action: 'delete',
                    hidden: true,
                },
                {
                    xtype: 'button',
                    text: 'attribuer les divisions',
                    action: 'set_attribution',
                    hidden: true,
                },
            ]
        })
    },
    create: function () {
        var this_window = Ext.create('Ext.window.Window', {
            title: "créer",
            height: 500,
            width: 700,
            maximizable: true,
            layout: 'fit',
            items: {
                xtype: 'form_commission',
            }
        });
        this_window.show();
    },
    edit: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length !== 1) {
            return;
        }
        var record = records[0];
        var this_window = Ext.create('Ext.window.Window', {
            title: Ext.String.format("{0} {1}", record.get('prenom'), record.get('nom')),
            height: 500,
            width: 700,
            maximizable: true,
            layout: 'fit',
            items: {
                xtype: 'form_commission',
            }
        });
        this_window.show();
        this_window.down('form').loadRecord(record);

    },
    delete: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
            return;
        }
        var ids = [];
        Ext.each(records, function (record) {
            ids.push(record.get('id_commission'));
        });
        Ext.Msg.show({
            title: 'Supprimer ?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn === 'yes') {
                    var params = {
                        ids: ids.join(','),
                    }
                    Ext.Ajax.request({
                        url: 'rest/action.php/commission/delete',
                        params: params,
                        success: function () {
                            grid.getStore().load();
                        },
                        failure: function (response) {
                            Ext.Msg.alert('Erreur', Ext.decode(response.responseText).message);
                        },
                    });
                }
            }
        });
    },
    set_attribution: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
            return;
        }
        var ids = [];
        Ext.each(records, function (record) {
            ids.push(record.get('id_commission'));
        });
        Ext.Msg.prompt('Divisions', 'Indiquer les divisions à attribuer (ex. d1m,d2f,d3mo):', function (btn, text) {
            if (btn == 'ok') {
                var params = {
                    ids: ids.join(','),
                }
                if (!Ext.isEmpty(text)) {
                    params['divisions'] = text;
                }
                Ext.Ajax.request({
                    url: 'rest/action.php/commission/attribution',
                    params: params,
                    success: function () {
                        grid.getStore().load();
                    },
                    failure: function (response) {
                        Ext.Msg.alert('Erreur', Ext.decode(response.responseText).message);
                    },
                });
            }
        });
    },
    manage_display: function (selection_model, selected) {
        var button = selection_model.view.ownerCt.down('button[action=set_attribution]');
        var is_hidden = false;
        if (Ext.isEmpty(selected)) {
            is_hidden = true;
        }
        if (!Ext.isArray(selected)) {
            is_hidden = true;
        }
        button.setHidden(is_hidden);
        button = selection_model.view.ownerCt.down('button[action=delete]');
        is_hidden = false;
        if (Ext.isEmpty(selected)) {
            is_hidden = true;
        }
        if (!Ext.isArray(selected)) {
            is_hidden = true;
        }
        button.setHidden(is_hidden);
        button = selection_model.view.ownerCt.down('button[action=edit]');
        is_hidden = false;
        if (Ext.isEmpty(selected)) {
            is_hidden = true;
        }
        if (!Ext.isArray(selected)) {
            is_hidden = true;
        }
        if (selected.length !== 1) {
            is_hidden = true;
        }
        button.setHidden(is_hidden);
    },
    add_menu_commission: function (button) {
        button.menu.add({
            text: 'Commission',
            action: 'display_commission'
        });
    },
    show_grid: function (button) {
        var tab = button.up('tabpanel').add({
            xtype: 'grid_commission',
            selType: 'checkboxmodel',
            layout: 'fit'
        });
        button.up('tabpanel').setActiveTab(tab);
    },
});