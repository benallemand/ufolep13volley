Ext.define('Ufolep13Volley.view.forum.LastPostsGrid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.LastPostsGrid',
    text: 'Derniers posts du forum...',
    title: 'Derniers posts',
    flex: 1,
    autoScroll: true,
    columns: [
        {
            header: 'Titre',
            flex: 4,
            dataIndex: 'title',
            renderer: function(value, meta, record) {
                return Ext.String.format('<a href="{0}" target="_blank">{1}</a>', record.get('guid'), value);
            }
        },
        {
            header: 'Auteur',
            flex: 1,
            hidden: true,
            dataIndex: 'creator'
        },
        {
            header: 'Catégorie',
            flex: 1,
            hidden: true,
            dataIndex: 'category'
        },
        {
            header: 'Date',
            flex: 1,
            dataIndex: 'pubdate',
            xtype: 'datecolumn',
            format: 'd/m h:i'
        }
    ],
    store: 'LastPosts'
});