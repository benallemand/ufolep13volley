Ext.onReady(function() {
    Ext.create('Ext.grid.Panel', {
        text: 'Derniers posts du forum...',
        title: 'Derniers posts',
        renderTo: Ext.get('lastposts'),
        autoScroll: true,
        columns: [
            {
                header: 'Titre',
                flex: 7,
                dataIndex: 'title',
                renderer: function(value, meta, record) {
                    return Ext.String.format('<a href="{0}" target="_blank">{1}</a>', record.get('guid'), value);
                }
            },
            {
                header: 'Auteur',
                flex: 4,
                dataIndex: 'creator'
            },
            {
                header: 'Catégorie',
                flex: 3,
                dataIndex: 'category'
            },
            {
                header: 'Date',
                flex: 3,
                dataIndex: 'pubdate',
                xtype: 'datecolumn',
                format: 'd/m/Y h:i'
            }
        ],
        store: Ext.create('Ext.data.Store', {
            fields: [
                'title',
                'creator',
                'category',
                {
                    name: 'pubdate',
                    type: 'date'
                },
                'description',
                'guid'
            ],
            proxy: {
                type: 'ajax',
                url: 'ajax/getLastPosts.php',
                reader: {
                    type: 'json',
                    root: 'results'
                }
            },
            filters: [
                function(item) {
                    return item.get('pubdate') > Date.parse('01/01/2013');
                }
            ],
            autoLoad: true
        })
    });
});