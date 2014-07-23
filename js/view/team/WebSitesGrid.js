Ext.define('Ufolep13Volley.view.team.WebSitesGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.WebSitesGrid',
    flex: 1,
    autoScroll: true,
    title: 'Liens vers sites web des équipes',
    forceFit: true,
    columns: [
        {
            header: 'Equipe',
            dataIndex: 'nom_equipe'
        },
        {
            header: 'Site Web',
            dataIndex: 'site_web',
            renderer: function(val) {
                return Ext.String.format("<a href='{0}' target='blank'>{0}</a>", val);
            }
        }
    ],
    store: 'WebSites'
});