Ext.define('Ufolep13Volley.model.New', {
    extend: 'Ext.data.Model',
    fields: [
        'id_news',
        {
            name: 'date_news',
            type: 'date',
            dateFormat: 'd/m/Y'
        },
        'titre_news',
        'texte_news'
    ]
});