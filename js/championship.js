Ext.application({
    requires: ['Ext.panel.Panel'],
    views: ['site.Banner', 'site.MainMenu', 'site.MainPanel', 'site.HeaderPanel', 'site.TitlePanel',  'site.LimitDatePanel', 'site.ChampionshipPanel', 'rank.Grid', 'match.Grid'],
    controllers: ['Matches', 'Classement'],
    stores: ['Matches', 'Classement'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                xtype: 'championshipPanel'
            }
        });
        Ext.Ajax.request({
            url: 'ajax/getSessionRights.php',
            success: function(response) {
                var responseJson = Ext.decode(response.responseText);
                if (responseJson.message === 'admin') {
                    var adminColumns = Ext.ComponentQuery.query('actioncolumn[text=Administration]');
                    Ext.each(adminColumns, function(adminColumn) {
                        adminColumn.show();
                    });
                }
            }
        });
    }
});


