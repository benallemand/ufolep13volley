Ext.define('Ufolep13Volley.controller.manage_register', {
    extend: 'Ext.app.Controller',
    stores: [
        'register',
    ],
    models: [
        'register',
    ],
    views: [
        'grid.register'
    ],
    refs: [],
    init: function () {
        this.control(
            {
                'button[text=administration]': {
                    added: this.add_menu_register
                },
                'menuitem[action=display_register]': {
                    click: this.show_grid
                },
                'button[action=fill_ranks]': {
                    click: this.fill_ranks
                },
                'button[action=delete_register]': {
                    click: this.delete_register
                },
                'grid_register': {
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
                    text: 'Remplir les divisions/rangs',
                    action: 'fill_ranks',
                    hidden: true,
                },
                {
                    xtype: 'button',
                    text: 'Supprimer',
                    action: 'delete_register',
                    hidden: true,
                },
            ]
        })
    },
    fill_ranks: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Remplir division/rang ?',
            msg: 'Veuillez confirmer cette action.',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'rest/action.php/register/fill_ranks',
                    params: {
                        ids: ids.join(',')
                    },
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
    delete_register: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer inscription(s) ?',
            msg: 'Veuillez confirmer cette action.',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.WARNING,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'rest/action.php/register/delete',
                    params: {
                        ids: ids.join(',')
                    },
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
        var button_fill_ranks = selection_model.view.ownerCt.down('button[action=fill_ranks]');
        var button_delete = selection_model.view.ownerCt.down('button[action=delete_register]');
        var is_hidden = false;
        if (Ext.isEmpty(selected)) {
            is_hidden = true;
        }
        if (!Ext.isArray(selected)) {
            is_hidden = true;
        }
        button_fill_ranks.setHidden(is_hidden);
        button_delete.setHidden(is_hidden);
    },
    add_menu_register: function (button) {
        button.menu.add({
            text: 'Inscriptions',
            action: 'display_register'
        });
    },
    show_grid: function (button) {
        button.up('tabpanel').add({
            xtype: 'grid_register',
            layout: 'fit',
            selModel: 'checkboxmodel',
        });
    },
});