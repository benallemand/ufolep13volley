Ext.define('Ufolep13Volley.view.site.ChampionshipPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.championshipPanel',
    layout: Ext.is.Phone ? 'accordion' : 'anchor',
    autoScroll: true,
    border: false,
    margin: Ext.is.Phone ? 0 : '0 50 0 50',
    items: [
        {
            xtype: 'gridRanking'
        },
        {
            xtype: 'gridMatches'
        }
    ],
    dockedItems: [
        {
            dock: 'top',
            xtype: 'headerPanel'
        },
        {
            dock: 'top',
            xtype: 'limitDatePanel'
        }
    ]
});