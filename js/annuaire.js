Ext.onReady(function() {
    var panel = Ext.create('Ext.panel.Panel', {
        layout: 'vbox',
        items: [],
        renderTo: 'annuaire_complet'
    });
    Ext.define('Annuaire', {
        extend: 'Ext.data.Model',
        fields: [
            'code_competition',
            'libelle_competition',
            'division',
            'id_equipe',
            'nom_equipe'
        ]
    });
    var storeAnnuaires = Ext.create('Ext.data.Store', {
        model: 'Annuaire',
        proxy: {
            type: 'ajax',
            url: 'ajax/getAnnuaires.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: false
    });
    function deepCloneStore(source) {
        var target = Ext.create('Ext.data.Store', {
            model: source.model
        });

        Ext.each(source.getRange(), function(record) {
            var newRecordData = Ext.clone(record.copy().data);
            var model = new source.model(newRecordData, newRecordData.id);

            target.add(model);
        });

        return target;
    }
    storeAnnuaires.load(function(store, records) {
        var competitions = Ext.Array.sort(storeAnnuaires.collect('libelle_competition'));
        Ext.each(competitions, function(competition) {
            var panelCompetition = Ext.create('Ext.panel.Panel', {
                margins: 10,
                layout: 'hbox',
                width: '100%',
                flex: 1,
                title: competition,
                items: []
            });
            storeAnnuaires.clearFilter(true);
            storeAnnuaires.filter('libelle_competition', competition);
            var divisions = storeAnnuaires.collect('division');
            Ext.each(divisions, function(division) {
                var panelDivision = Ext.create('Ext.panel.Panel', {
                    flex: 1,
                    header: false,
                    items: []
                });
                var storeDiv = deepCloneStore(storeAnnuaires);
                storeDiv.clearFilter(true);
                storeDiv.filter('libelle_competition', competition);
                storeDiv.filter('division', division);
                storeDiv.sort('nom_equipe', 'ASC');
                var libelle_division = 'Division ';
                if (competition.indexOf('Coupe') !== -1) {
                    libelle_division = 'Poule ';
                }
                panelDivision.add({
                    xtype: 'grid',
                    store: storeDiv,
                    columns: [
                        {
                            header: libelle_division + division,
                            dataIndex: 'nom_equipe',
                            flex: 1,
                            renderer: function(value, meta, record) {
                                return Ext.String.format("<a href='annuaire.php?id={0}&c={1}' target='_self'>{2}</a>", record.get('id_equipe'), record.get('code_competition'), record.get('nom_equipe'));
                            }
                        }
                    ]
                });
                panelCompetition.add(panelDivision);
            });
            panel.add(panelCompetition);
        });
    });

});