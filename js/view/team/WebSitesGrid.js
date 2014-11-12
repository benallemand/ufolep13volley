Ext.define('Ufolep13Volley.view.team.WebSitesGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.WebSitesGrid',
    title: 'Sites web des clubs',
    columns: [
        {
            header: 'Club',
            dataIndex: 'nom_club',
            width: 200
        },
        {
            header: 'Site Web',
            dataIndex: 'web_site',
            renderer: function(val) {
                return Ext.String.format("<a href='{0}' target='blank'>{0}</a>", val);
            },
            width: 400
        }
    ],
    store: 'WebSites'
});