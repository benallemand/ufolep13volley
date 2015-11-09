Ext.define('Ufolep13Volley.store.WeekDays', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.WeekDay',
        data: [
            {"name": "Lundi"},
            {"name": "Mardi"},
            {"name": "Mercredi"},
            {"name": "Jeudi"},
            {"name": "Vendredi"}
        ]
    }
}));