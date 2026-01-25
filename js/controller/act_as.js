Ext.define('Ufolep13Volley.controller.act_as', {
    extend: 'Ext.app.Controller',
    stores: ['ActAs'],
    init: function () {
        this.control({
            'button[action=showActAsSelector]': {
                click: this.showActAsSelector
            }
        });
    },

    showActAsSelector: function () {
        var store = Ext.create('Ufolep13Volley.store.ActAs');
        store.load({
            callback: function (records, operation, success) {
                if (!success) {
                    Ext.Msg.alert('Erreur', 'Impossible de charger la liste des utilisateurs');
                    return;
                }
                
                var win = Ext.create('Ext.window.Window', {
                    title: 'Agir en tant que...',
                    modal: true,
                    width: 500,
                    height: 400,
                    layout: 'fit',
                    items: [{
                        xtype: 'grid',
                        store: store,
                        columns: [
                            {text: 'Login', dataIndex: 'login', flex: 1},
                            {text: 'Email', dataIndex: 'email', flex: 1},
                            {text: 'Profil', dataIndex: 'profile_name', width: 120},
                            {text: 'Équipes', dataIndex: 'equipes', flex: 1}
                        ],
                        listeners: {
                            itemdblclick: function (grid, record) {
                                Ext.Msg.confirm(
                                    'Confirmation',
                                    'Voulez-vous agir en tant que <b>' + record.get('login') + '</b> ?',
                                    function (btn) {
                                        if (btn === 'yes') {
                                            Ext.Ajax.request({
                                                url: '/rest/action.php/usermanager/switch_to_user',
                                                method: 'POST',
                                                params: {
                                                    target_user_id: record.get('id')
                                                },
                                                success: function (response) {
                                                    var result = Ext.decode(response.responseText);
                                                    if (result.success) {
                                                        Ext.Msg.alert('Succès', 'Vous agissez maintenant en tant que ' + record.get('login'), function () {
                                                            window.location.href = '/pages/home.html';
                                                        });
                                                    } else {
                                                        Ext.Msg.alert('Erreur', result.message);
                                                    }
                                                },
                                                failure: function () {
                                                    Ext.Msg.alert('Erreur', 'Erreur de communication avec le serveur');
                                                }
                                            });
                                            win.close();
                                        }
                                    }
                                );
                            }
                        },
                        dockedItems: [{
                            xtype: 'toolbar',
                            dock: 'top',
                            items: [{
                                xtype: 'textfield',
                                emptyText: 'Rechercher un utilisateur...',
                                width: 300,
                                listeners: {
                                    change: function (field, newValue) {
                                        var grid = field.up('grid');
                                        var store = grid.getStore();
                                        store.clearFilter();
                                        if (newValue) {
                                            store.filterBy(function (record) {
                                                var login = record.get('login') || '';
                                                var email = record.get('email') || '';
                                                var equipes = record.get('equipes') || '';
                                                var searchValue = newValue.toLowerCase();
                                                return login.toLowerCase().indexOf(searchValue) !== -1 ||
                                                       email.toLowerCase().indexOf(searchValue) !== -1 ||
                                                       equipes.toLowerCase().indexOf(searchValue) !== -1;
                                            });
                                        }
                                    }
                                }
                            }]
                        }]
                    }],
                    buttons: [{
                        text: 'Annuler',
                        handler: function () {
                            win.close();
                        }
                    }]
                });
                win.show();
            }
        });
    }
});
