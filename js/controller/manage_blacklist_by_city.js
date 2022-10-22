Ext.define('Ufolep13Volley.controller.manage_blacklist_by_city', {
    extend: 'Ext.app.Controller',
    stores: [
        'BlacklistByCity',
        'City'
    ],
    models: [
        'BlacklistByCity',
        'City'
    ],
    views: [
        'grid.BlacklistByCity'
    ],
    refs: [],
    init: function () {
        this.control(
            {
                'button[text=Menu]': {
                    added: this.add_menu_blacklist_by_city
                },
                'menuitem[action=display_blacklist_by_city]': {
                    click: this.show_grid
                },
                'blacklist_by_city_grid > toolbar > button[action=add]': {
                    click: this.display_edit
                },
                'blacklist_by_city_grid > toolbar > button[action=edit]': {
                    click: this.display_edit
                },
                'blacklist_by_city_grid': {
                    itemdblclick: this.display_edit
                },
                'blacklist_by_city_grid > toolbar > button[action=delete]': {
                    click: this.display_delete
                }
            }
        );
    },
    add_menu_blacklist_by_city: function (button) {
        button.menu.add({
            text: 'Dates interdites par ville (spécial COVID)',
            action: 'display_blacklist_by_city'
        });
    },
    show_grid: function (button) {
        button.up('tabpanel').add({
            xtype: 'blacklist_by_city_grid',
            layout: 'fit'
        });
    },
    display_edit: function (button_or_tableview) {
        var is_edit = true;
        var current_record = null;
        var store = null;
        switch (button_or_tableview.xtype) {
            case 'button':
                current_record = button_or_tableview.up('grid').getSelectionModel().getSelection()[0];
                store = button_or_tableview.up('grid').getStore();
                switch (button_or_tableview.action) {
                    case 'add':
                        is_edit = false;
                        break;
                    case 'edit':
                        break;
                    default:
                        return;
                }
                break;
            case 'tableview':
                current_record = button_or_tableview.getSelectionModel().getSelection()[0];
                store = button_or_tableview.getStore();
                break;
            default:
                return;
        }
        if (is_edit && Ext.isEmpty(current_record)) {
            return;
        }
        var window_edit = Ext.create('Ext.window.Window', {
            title: is_edit ? 'Modification' : 'Création',
            height: 500,
            width: 700,
            maximizable: true,
            layout: 'fit',
            items: {
                xtype: 'form',
                trackResetOnLoad: true,
                layout: 'form',
                url: '/rest/action.php/competition/save_blacklist_by_city',
                items: [
                    {
                        xtype: 'hidden',
                        fieldLabel: 'id',
                        name: 'id'
                    },
                    {
                        xtype: 'combo',
                        fieldLabel: 'Ville',
                        name: 'city',
                        displayField: 'name',
                        valueField: 'name',
                        store: 'City',
                        queryMode: 'local',
                        allowBlank: false,
                        forceSelection: true
                    },
                    {
                        xtype: 'datefield',
                        fieldLabel: 'Du',
                        name: 'from_date',
                        allowBlank: false,
                        startDay: 1,
                        format: 'd/m/Y'
                    },
                    {
                        xtype: 'datefield',
                        fieldLabel: 'Au',
                        name: 'to_date',
                        allowBlank: false,
                        startDay: 1,
                        format: 'd/m/Y'
                    }
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
                                var dirtyFieldsJson = form.getFieldValues(true);
                                var dirtyFieldsArray = [];
                                for (var key in dirtyFieldsJson) {
                                    dirtyFieldsArray.push(key);
                                }
                                form.submit({
                                    params: {
                                        dirtyFields: dirtyFieldsArray.join(',')
                                    },
                                    success: function () {
                                        store.load();
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
        if (is_edit) {
            window_edit.down('form').loadRecord(current_record);
        }
        window_edit.show();
    },
    display_delete: function (button) {
        var records = button.up('grid').getSelectionModel().getSelection();
        if (records.length === 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
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
                    url: '/rest/action.php/competition/delete_blacklist_by_city',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        button.up('grid').getStore().load();
                    }
                });
            }
        });
    }
});