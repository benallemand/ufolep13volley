Ext.define('Ufolep13Volley.view.forum.LastPostsGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.LastPostsGrid',
    title: 'Derniers posts',
    columns: [
        {
            header: 'Titre',
            width: 500,
            dataIndex: 'title',
            renderer: function(value, meta, record) {
                return Ext.String.format('<a href="{0}" target="_blank">{1}</a>', record.get('guid'), value);
            }
        },
        {
            header: 'Date',
            width: 100,
            dataIndex: 'pubdate',
            xtype: 'datecolumn',
            format: 'd/m H:i'
        }
    ],
    store: 'LastPosts'
});