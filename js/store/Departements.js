Ext.define('Ufolep13Volley.store.Departements', {
    extend: 'Ext.data.Store',
    alias: 'store.Departements',
    storeId: 'Departements',
    fields: [{name: 'abbr', type: 'int'}, 'name'],
    data: [
        {"abbr": 7, "name": "Ardèche"},
        {"abbr": 13, "name": "Bouches du Rhône"},
        {"abbr": 83, "name": "Var"},
        {"abbr": 84, "name": "Vaucluse"},
        {"abbr": 0, "name": "Autres"}
    ]
});
