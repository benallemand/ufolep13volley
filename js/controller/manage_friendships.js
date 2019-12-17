Ext.define('Ufolep13Volley.controller.manage_friendships', {
    extend: 'Ext.app.Controller',
    stores: [
        'Clubs',
        'Friendships'
    ],
    models: [
        'Club',
        'Friendships'
    ],
    views: [
        'grid.Friendships',
    ],
    refs: [],
    init: function () {
        this.control(
            {
                'button[text=Menu]': {
                    added: this.add_menu_friendships
                },
                'menuitem[action=display_friendships]': {
                    click: this.show_grid
                },
                'friendships_grid > toolbar > button[action=add]': {
                    click: this.display_edit
                },
                'friendships_grid > toolbar > button[action=edit]': {
                    click: this.display_edit
                },
                'friendships_grid': {
                    itemdblclick: this.display_edit
                },
                'friendships_grid > toolbar > button[action=delete]': {
                    click: this.display_delete
                },
                'button[action=save]': {
                    click: this.save
                }
            }
        );
    },
    add_menu_friendships: function (button) {
        button.menu.add({
            text: 'Ententes entre clubs',
            action: 'display_friendships'
        });
    },
    show_grid: function (button) {
        button.up('tabpanel').add({
            xtype: 'friendships_grid',
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
                url: 'ajax/save_friendships.php',
                items: [
                    {
                        xtype: 'hidden',
                        fieldLabel: 'id',
                        name: 'id'
                    },
                    {
                        xtype: 'combo',
                        fieldLabel: 'Club 1',
                        name: 'id_club_1',
                        displayField: 'nom',
                        valueField: 'id',
                        store: 'Clubs',
                        queryMode: 'local',
                        allowBlank: false,
                        forceSelection: true
                    },
                    {
                        xtype: 'combo',
                        fieldLabel: 'Club 2',
                        name: 'id_club_2',
                        displayField: 'nom',
                        valueField: 'id',
                        store: 'Clubs',
                        queryMode: 'local',
                        allowBlank: false,
                        forceSelection: true
                    }
                ],
                buttons: [
                    {
                        text: 'Annuler',
                        handler: function () {
                            this.up('window').close();
                        }
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
                    url: 'ajax/delete_friendships.php',
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