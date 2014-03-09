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
    Ext.create('Ext.data.Store', {
        model: 'Annuaire',
        proxy: {
            type: 'ajax',
            url: 'ajax/getAnnuaires.php',
            reader: {
                type: 'json',
                root: 'results'
            }
        },
        autoLoad: true,
        listeners: {
            load: function(storeCompet, records) {
                var competitions = storeCompet.collect('libelle_competition');
                Ext.each(competitions, function(competition) {
                    var panelCompetition = Ext.create('Ext.panel.Panel', {
                        margins: 10,
                        layout: 'hbox',
                        width: '100%',
                        flex: 1,
                        title: competition,
                        items: []
                    });
                    Ext.create('Ext.data.Store', {
                        model: 'Annuaire',
                        proxy: {
                            type: 'ajax',
                            url: 'ajax/getAnnuaires.php',
                            reader: {
                                type: 'json',
                                root: 'results'
                            }
                        },
                        autoLoad: true,
                        listeners: {
                            load: function(storeDiv, records) {
                                storeDiv.filter('libelle_competition', competition);
                                var divisions = storeDiv.collect('division');
                                Ext.each(divisions, function(division) {
                                    var panelDivision = Ext.create('Ext.panel.Panel', {
                                        flex: 1,
                                        header: false,
                                        items: []
                                    });
                                    Ext.create('Ext.data.Store', {
                                        model: 'Annuaire',
                                        proxy: {
                                            type: 'ajax',
                                            url: 'ajax/getAnnuaires.php',
                                            reader: {
                                                type: 'json',
                                                root: 'results'
                                            }
                                        },
                                        autoLoad: true,
                                        listeners: {
                                            load: function(storeGrid, records) {
                                                storeGrid.filter('libelle_competition', competition);
                                                storeGrid.filter('division', division);
                                                storeGrid.sort('nom_equipe', 'ASC');
                                                var libelle_division = 'Division ';
                                                if (competition.indexOf('Coupe') !== -1) {
                                                    libelle_division = 'Poule ';
                                                }
                                                panelDivision.add({
                                                    xtype: 'grid',
                                                    store: storeGrid,
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
                                            }
                                        }
                                    });
                                    panelCompetition.add(panelDivision);
                                });

                            }
                        }
                    });
                    panel.add(panelCompetition);
                });
            }
        }
    });
});