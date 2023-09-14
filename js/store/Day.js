Ext.define('Ufolep13Volley.store.Day', {
    extend: 'Ext.data.Store',
    alias: 'store.Day',
    config: {
        fields: ['name'],
        data: [
            {
                "name": "Lundi"
            },
            {
                "name": "Mardi"
            },
            {
                "name": "Mercredi"
            },
            {
                "name": "Jeudi"
            },
            {
                "name": "Vendredi"
            }
        ]
    }
});