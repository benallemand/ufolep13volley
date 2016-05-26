Ext.define('Ufolep13Volley.store.Phonebooks', Sencha.storeCompatibility({
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Volley.model.Phonebook',
        proxy: {
            type: 'ajax',
            url: 'ajax/getAnnuaires.php'
        },
        sorters: 'nom_equipe',
        grouper: {
            groupFn: function (record) {
                var nommageDivision = 'Division';
                if (record.get('libelle_competition').indexOf('Coupe') >= 0) {
                    nommageDivision = 'Poule';
                }
                return record.get('libelle_competition') + ' - ' + nommageDivision + ' ' + record.get('division');
            }
        },
        autoLoad: false
    }
}));