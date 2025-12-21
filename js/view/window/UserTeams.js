Ext.define('Ufolep13Volley.view.window.UserTeams', {
    extend: 'Ext.window.Window',
    alias: 'widget.window_user_teams',
    title: 'Équipes liées',
    layout: 'fit',
    modal: true,
    width: 700,
    height: 500,
    autoShow: true,
    
    initComponent: function() {
        var me = this;
        
        me.teamsStore = Ext.create('Ufolep13Volley.store.Teams');
        
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
                    itemId: 'teamsGrid',
                    store: me.teamsStore,
                    selModel: {
                        type: 'checkboxmodel',
                        mode: 'SIMPLE'
                    },
                    columns: [
                        {
                            header: 'Club',
                            dataIndex: 'club',
                            flex: 1
                        },
                        {
                            header: 'Équipe',
                            dataIndex: 'team_full_name',
                            flex: 2
                        }
                    ]
                }
            ],
            buttons: [
                {
                    text: 'Sauver',
                    action: 'save_user_teams'
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
    
    loadUserTeams: function(userId) {
        var me = this;
        var form = me.down('form');
        var grid = me.down('#teamsGrid');
        
        form.getForm().findField('user_id').setValue(userId);
        
        Ext.Ajax.request({
            url: '/rest/action.php/usermanager/getUserTeamIds',
            method: 'GET',
            params: {user_id: userId},
            success: function(response) {
                var linkedTeamIds = Ext.decode(response.responseText);
                me.teamsStore.on('load', function() {
                    var selModel = grid.getSelectionModel();
                    me.teamsStore.each(function(record) {
                        if (linkedTeamIds.indexOf(record.get('id_equipe')) !== -1) {
                            selModel.select(record, true);
                        }
                    });
                }, me, {single: true});
            }
        });
    }
});
