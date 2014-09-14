Ext.define('Ufolep13Volley.store.Days', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Day',
        data: [
            {"name": "Lundi"},
            {"name": "Mardi"},
            {"name": "Mercredi"},
            {"name": "Jeudi"},
            {"name": "Vendredi"}
        ]
    }
}));