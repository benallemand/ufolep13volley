Ext.application({
    requires: [],
    views: ['site.Banner', 'site.MainMenu', 'site.MainPanel', 'site.HeaderPanel', 'site.TitlePanel', 'match.LastResultsGrid', 'forum.LastPostsGrid', 'team.WebSitesGrid'],
    controllers: [],
    stores: ['LastResults', 'LastPosts', 'WebSites'],
    models: ['LastResult', 'LastPost', 'WebSite'],
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