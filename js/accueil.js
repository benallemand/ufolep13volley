Ext.application({
    requires: [],
    views: ['site.Banner', 'site.MainMenu', 'site.MainPanel', 'site.HeaderPanel', 'site.TitlePanel', 'new.Grid', 'match.LastResultsGrid', 'forum.LastPostsGrid', 'team.WebSitesGrid'],
    controllers: [],
    stores: ['News', 'LastResults', 'LastPosts', 'WebSites'],
    models: ['New', 'LastResult', 'LastPost', 'WebSite'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function() {
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                xtype: 'mainPanel'
            }
        });
    }
});