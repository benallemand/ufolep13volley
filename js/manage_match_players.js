Ext.application({
    requires: ['Ext.container.Viewport'],
    controllers: [
        'manage_match_players',
        'Administration',
    ],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function () {
        var viewport = Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                layout: 'border',
                items: [
                    {
                        xtype: 'form_match_players',
                        region: 'center',
                        flex: 1,
                        trackResetOnLoad: true
                    },
                    {
                        xtype: 'panel',
                        region: 'south',
                        autoScroll: true,
                        flex: 2,
                        title: 'Actuellement',
                        layout: 'fit',
                        items: {
                            xtype: 'view_match_players'
                        }
                    }
                ]
            }
        });
        var params = Ext.urlDecode(location.search.substring(1));
        var id_match = params['id_match'];
        viewport.down('form').down('hidden[name=id_match]').setValue(id_match);
        viewport.down('form').down('button[action=cancel]').hide();
        viewport.down('view_match_players').getStore().load({
            params: {
                id_match: id_match
            }
        });
        // filter available match players by id_match (known teams)
        viewport.down('tagfield').getStore().load({
            params: {
                id_match: id_match
            }
        });

    }
});