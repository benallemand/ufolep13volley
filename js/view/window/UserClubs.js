Ext.define('Ufolep13Volley.view.window.UserClubs', {
    extend: 'Ext.window.Window',
    alias: 'widget.window_user_clubs',
    title: 'Clubs liés',
    layout: 'fit',
    modal: true,
    width: 500,
    height: 500,
    autoShow: true,

    initComponent: function() {
        var me = this;

        me.clubsStore = Ext.create('Ufolep13Volley.store.Clubs');

        me.items = [{
            xtype: 'form',
            layout: 'fit',
            items: [
                {
                    xtype: 'hidden',
                    name: 'user_id'
                },
                {
                    xtype: 'grid_ufolep',
                    itemId: 'clubsGrid',
                    store: me.clubsStore,
                    selModel: {
                        type: 'checkboxmodel',
                        mode: 'SIMPLE'
                    },
                    columns: [
                        {
                            header: 'Club',
                            dataIndex: 'nom',
                            flex: 1
                        }
                    ]
                }
            ],
            buttons: [
                {
                    text: 'Sauver',
                    action: 'save_user_clubs'
                },
                {
                    text: 'Annuler',
                    action: 'cancel',
                    handler: function() {
                        me.close();
                    }
                }
            ]
        }];

        me.callParent(arguments);
    },

    loadUserClubs: function(userId) {
        var me = this;
        var form = me.down('form');
        var grid = me.down('#clubsGrid');

        form.getForm().findField('user_id').setValue(userId);

        Ext.Ajax.request({
            url: '/rest/action.php/usermanager/getUserClubIds',
            method: 'GET',
            params: {user_id: userId},
            success: function(response) {
                var linkedClubIds = Ext.decode(response.responseText);
                var applySelection = function() {
                    var selModel = grid.getSelectionModel();
                    me.clubsStore.each(function(record) {
                        if (linkedClubIds.indexOf(record.get('id')) !== -1) {
                            selModel.select(record, true);
                        }
                    });
                };
                // le store Clubs est en autoLoad : il peut déjà être chargé
                if (me.clubsStore.isLoaded()) {
                    applySelection();
                } else {
                    me.clubsStore.on('load', applySelection, me, {single: true});
                }
            }
        });
    }
});
