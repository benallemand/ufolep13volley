Ext.define('Ufolep13Volley.view.mobile.LastResults', {
    extend: 'Ext.dataview.List',
    requires: [
        'Ext.dataview.List'
    ],
    xtype: 'listlastresults',
    config: {
        title : 'Derniers Resultats',
        itemTpl: '{competition} - {division_journee} | {equipe_domicile} vs {equipe_exterieur} | {score_equipe_dom}-{score_equipe_ext}',
        store: 'LastResults',
        flex: 1
    }
});
