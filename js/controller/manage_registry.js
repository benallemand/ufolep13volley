Ext.define('Ufolep13Volley.controller.manage_registry', {
    extend: 'Ext.app.Controller',
    stores: [
        'registry',
    ],
    models: [
        'registry',
    ],
    views: [
        'grid.registry',
        'form.registry',
    ],
    refs: [],
    init: function () {
        this.control(
            {
                'button[text=administration]': {
                    added: this.add_menu_registry
                },
                'menuitem[action=display_registry]': {
                    click: this.show_grid
                },
                'grid_registry > toolbar > button[action=create]': {
                    click: this.create
                },
                'grid_registry > toolbar > button[action=edit]': {
                    click: this.edit
                },
                'grid_registry > toolbar > button[action=delete]': {
                    click: this.delete
                },
                'grid_registry': {
                    added: this.add_action_buttons,
                    selectionchange: this.manage_display,
                    itemdblclick: this.trigger_edit,
                },
            }
        );
    },
    trigger_edit: function (tableview) {
        var grid = tableview.ownerCt;
        var button = grid.down('button[action=edit]');
        this.edit(button);
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
            ]
        })
    },
    create: function (button) {
        var this_window = Ext.create('Ext.window.Window', {
            title: "créer",
            height: 500,
            width: 700,
            maximizable: true,
            layout: 'fit',
            items: {
                xtype: 'form_registry',
            }
        });
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length !== 0) {
            var record = records[0];
            this_window.down('form').loadRecord(record);
            this_window.down('form').getForm().findField('id').setValue("");
        }
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
            title: Ext.String.format("{0}", record.get('registry_key')),
            height: 500,
            width: 700,
            maximizable: true,
            layout: 'fit',
            items: {
                xtype: 'form_registry',
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
            ids.push(record.get('id'));
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
                        url: 'rest/action.php/registry/delete',
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
    manage_display: function (selection_model, selected) {
        var button = selection_model.view.ownerCt.down('button[action=delete]');
        var is_hidden = false;
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
    add_menu_registry: function (button) {
        button.menu.add({
            text: 'base de registres',
            action: 'display_registry'
        });
    },
    show_grid: function (button) {
        var tab = button.up('tabpanel').add({
            xtype: 'grid_registry',
            layout: 'fit'
        });
        button.up('tabpanel').setActiveTab(tab);
    },
});