Ext.define('Ufolep13Mobile.view.SearchTeam', {
    extend: 'Ext.field.Search',
    requires: [
        'Ext.field.Search'
    ],
    xtype: 'searchfieldteam',
    config: {
        label: 'Recherche',
        name: 'query',
        listeners: {
            change: function(search, newVal) {
                if (newVal === '') {
                    var filtersAux = [];
                    var store = Ext.getStore('Teams');
                    Ext.Array.each(store.getFilters(), function(element, pos, array) {
                        if (element.getProperty() === 'nom_equipe') {
                            return true;
                        }
                        filtersAux.push(element);
                    });
                    store.clearFilter();
                    Ext.Array.each(filtersAux, function(element, pos, array) {
                        store.filter(element.getProperty(), element.getValue());
                    });
                }
                else {
                    Ext.getStore('Teams').filter('nom_equipe', newVal);
                }
            }
        }
    }
}
);
