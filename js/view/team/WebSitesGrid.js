Ext.define('Ufolep13Volley.view.team.WebSitesGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.WebSitesGrid',
    title: 'Liens vers sites web des équipes',
    columns: [
        {
            header: 'Equipe',
            dataIndex: 'nom_equipe',
            width: 200
        },
        {
            header: 'Site Web',
            dataIndex: 'site_web',
            renderer: function(val) {
                return Ext.String.format("<a href='{0}' target='blank'>{0}</a>", val);
            },
            width: 400
        }
    ],
    store: 'WebSites'
});