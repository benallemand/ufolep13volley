Ext.application({
    requires: ['Ext.panel.Panel'],
    views: ['site.Banner', 'site.MainMenu', 'site.MainPanel', 'site.HeaderPanel', 'site.TitlePanel',  'site.LimitDatePanel', 'site.ChampionshipPanel', 'rank.Grid', 'match.Grid'],
    controllers: controllers,
    stores: ['Matches', 'Classement'],
    models: ['Match', 'Classement'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                xtype: 'championshipPanel'
            }
        });
    }
});


