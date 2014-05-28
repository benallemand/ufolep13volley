Ext.define('Ufolep13.view.Matches', {
    extend: 'Ext.dataview.List',
    requires: [
        'Ext.dataview.List'
    ],
    xtype: 'listmatches',
    config: {
        itemTpl: '{equipe_domicile} vs {equipe_exterieur} | {score_equipe_dom}-{score_equipe_ext}',
        grouped: true,
        store: 'Matches',
        flex: 1
    }
});
