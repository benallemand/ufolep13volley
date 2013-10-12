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
                dataIndex: 'pubdate'
            },
            {
                xtype: 'actioncolumn',
                flex: 1,
                items: [
                    {
                        icon: 'images/file.gif',
                        tooltip: 'Voir',
                        handler: function(grid, rowIndex, colIndex) {
                            var rec = grid.getStore().getAt(rowIndex);
                            Ext.Msg.alert(rec.get('title'), rec.get('description'));
                        }
                    }
                ]
            }
        ],
        store: Ext.create('Ext.data.Store', {
            fields: [
                'title',
                'creator',
                'category',
                'pubdate',
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
            autoLoad: true
        })
    });
});