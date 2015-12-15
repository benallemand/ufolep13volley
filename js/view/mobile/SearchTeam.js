Ext.define('Ufolep13Volley.view.mobile.SearchTeam', {
    extend: 'Ext.field.Search',
    requires: [
        'Ext.field.Search'
    ],
    xtype: 'searchfieldteam',
    config: {
        label: 'Recherche',
        name: 'query',
        listeners: {
            keyup: function(search) {
                var newVal = search.getValue();
                if (newVal === '') {
                    var filtersAux = [];
                    var store = Ext.getStore('Phonebooks');
                    Ext.Array.each(store.getFilters(), function (element) {
                        if (element.getProperty() === 'nom_equipe') {
                            return true;
                        }
                        filtersAux.push(element);
                    });
                    store.clearFilter();
                    Ext.Array.each(filtersAux, function (element) {
                        store.filter(element.getProperty(), element.getValue());
                    });
                }
                else {
                    Ext.getStore('Phonebooks').filter('nom_equipe', newVal, true);
                }
            }
        }
    }
}
);
