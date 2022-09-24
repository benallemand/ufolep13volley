Ext.define('Ufolep13Volley.model.Activity', {
    extend: 'Ext.data.Model',
    fields: [
        {
            name: 'date',
            type: 'date',
            dateFormat: 'd/m/Y H:i:s'
        },
        {
            name: 'nom_equipe',
            type: 'string'
        },
        {
            name: 'competition',
            type: 'string'
        },
        {
            name: 'description',
            type: 'string'
        },
        {
            name: 'utilisateur',
            type: 'string'
        },
        {
            name: 'email_utilisateur',
            type: 'string'
        }
    ]
});
