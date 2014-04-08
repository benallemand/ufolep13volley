Ext.define('Ufolep13Mobile.store.Teams', {
    extend: 'Ext.data.Store',
    config: {
        model: 'Ufolep13Mobile.model.Team',
        proxy: {
            type: 'ajax',
            url: '/ajax/getAnnuaires.php'
        },
        sorters: 'nom_equipe',
        grouper: {
            groupFn: function(record) {
                var nommageDivision = 'Division';
                if (record.get('libelle_competition').indexOf('Coupe') >= 0) {
                    nommageDivision = 'Poule';
                }
                return nommageDivision + ' ' + record.get('division');
            }
        },
        autoLoad: true
    }

});