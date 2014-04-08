Ext.define('Ufolep13Mobile.view.SearchTournament', {
    extend: 'Ext.field.Search',
    requires: [
        'Ext.field.Search'
    ],
    xtype: 'searchfieldtournament',
    config: {
        label: 'Recherche',
        name: 'query',
        listeners: {
            change: function(search, newVal) {
                if (newVal === '') {
                    var filtersAux = [];
                    var store = Ext.getStore('Tournaments');
                    Ext.Array.each(store.getFilters(), function(element, pos, array) {
                        if (element.getProperty() === 'libelle') {
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
                    Ext.getStore('Tournaments').filter('libelle', newVal, true);
                }
            }
        }
    }
}
);
