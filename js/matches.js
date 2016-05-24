Ext.application({
    requires: ['Ext.panel.Panel'],
    views: ['site.Banner', 'site.MainMenu', 'site.MainPanel', 'site.HeaderPanel', 'site.TitlePanel', 'site.LimitDatePanel', 'site.MatchesPanel', 'match.Grid'],
    controllers: controllers,
    stores: ['Matches'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function () {
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                xtype: 'matchesPanel'
            }
        });
    }
});