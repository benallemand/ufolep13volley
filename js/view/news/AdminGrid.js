Ext.define('Ufolep13Volley.view.news.AdminGrid', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.newsgrid',
    title: 'Gestion des News',
    store: {type: 'AdminNews'},
    columns: {
        items: [
            {
                header: 'ID',
                dataIndex: 'id',
                width: 60
            },
            {
                header: 'Titre',
                dataIndex: 'title',
                flex: 1
            },
            {
                header: 'Texte',
                dataIndex: 'text',
                flex: 2
            },
            {
                header: 'Fichier',
                dataIndex: 'file_path',
                width: 150
            },
            {
                header: 'Date',
                dataIndex: 'news_date',
                width: 120,
                renderer: Ext.util.Format.dateRenderer('d/m/Y')
            },
            {
                header: 'Désactivé',
                dataIndex: 'is_disabled',
                width: 100,
                renderer: function(value) {
                    return value == 1 ? 'Oui' : 'Non';
                }
            }
        ]
    },
    dockedItems: [
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'ACTIONS',
                {
                    xtype: 'tbseparator'
                },
                {
                    text: 'Ajouter une news',
                    glyph: 'xf067@FontAwesome',
                    action: 'addNews'
                },
                {
                    text: 'Editer',
                    glyph: 'xf044@FontAwesome',
                    action: 'editNews'
                },
                {
                    text: 'Supprimer',
                    glyph: 'xf1f8@FontAwesome',
                    action: 'deleteNews'
                }
            ]
        }
    ]
});
