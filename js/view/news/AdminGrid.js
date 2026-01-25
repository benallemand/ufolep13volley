Ext.define('Ufolep13Volley.view.news.AdminGrid', {
    extend: 'Ufolep13Volley.view.grid.ufolep',
    alias: 'widget.newsgrid',
    title: 'Gestion des News',
    store: {type: 'AdminNews'},
    plugins: [{
        ptype: 'rowediting',
        clicksToEdit: 2,
        pluginId: 'rowediting'
    }],
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
                flex: 1,
                editor: {
                    xtype: 'textfield',
                    allowBlank: false
                }
            },
            {
                header: 'Texte',
                dataIndex: 'text',
                flex: 2,
                editor: {
                    xtype: 'textarea',
                    allowBlank: true
                }
            },
            {
                header: 'Fichier',
                dataIndex: 'file_path',
                width: 150,
                editor: {
                    xtype: 'textfield',
                    allowBlank: true
                }
            },
            {
                header: 'Date',
                dataIndex: 'news_date',
                width: 120,
                renderer: Ext.util.Format.dateRenderer('d/m/Y'),
                editor: {
                    xtype: 'datefield',
                    format: 'd/m/Y',
                    allowBlank: false
                }
            },
            {
                header: 'Désactivé',
                dataIndex: 'is_disabled',
                width: 100,
                renderer: function(value) {
                    return value == 1 ? 'Oui' : 'Non';
                },
                editor: {
                    xtype: 'combobox',
                    store: [[0, 'Non'], [1, 'Oui']],
                    editable: false
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
                    text: 'Supprimer',
                    glyph: 'xf1f8@FontAwesome',
                    action: 'deleteNews'
                }
            ]
        }
    ]
});
