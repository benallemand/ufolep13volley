Ext.define('Ufolep13Volley.view.news.Edit', {
    extend: 'Ext.window.Window',
    alias: 'widget.newsedit',
    title: "Modification de la news",
    height: 500,
    width: 700,
    modal: true,
    layout: 'fit',
    autoShow: true,
    items: {
        xtype: 'form',
        trackResetOnLoad: true,
        layout: 'anchor',
        defaults: {
            anchor: '90%',
            margins: 10
        },
        url: '/rest/action.php/news/saveNews',
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id',
                name: 'id'
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Titre',
                name: 'title',
                allowBlank: false
            },
            {
                xtype: 'textarea',
                fieldLabel: 'Texte',
                name: 'text',
                height: 150,
                allowBlank: true
            },
            {
                xtype: 'textfield',
                fieldLabel: 'Chemin fichier',
                name: 'file_path',
                allowBlank: true
            },
            {
                xtype: 'datefield',
                fieldLabel: 'Date',
                name: 'news_date',
                allowBlank: false,
                format: 'Y-m-d'
            },
            {
                xtype: 'combo',
                fieldLabel: 'Désactivé',
                name: 'is_disabled',
                store: [[0, 'Non'], [1, 'Oui']],
                editable: false,
                value: 0
            }
        ],
        buttons: [
            {
                text: 'Annuler',
                action: 'cancel',
            },
            {
                text: 'Sauver',
                formBind: true,
                disabled: true,
                action: 'save'
            }
        ]
    }
});
