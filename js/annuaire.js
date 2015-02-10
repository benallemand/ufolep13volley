Ext.application({
    requires: ['Ext.panel.Panel'],
    views: ['site.Banner', 'site.MainMenu', 'site.MainPanel', 'site.HeaderPanel', 'site.TitlePanel', 'site.PhonebookPanel'],
    controllers: [],
    stores: ['Phonebooks'],
    models: ['Phonebook'],
    name: 'Ufolep13Volley',
    appFolder: 'js',
    launch: function () {
        Ext.create('Ext.container.Viewport', {
            layout: 'fit',
            items: {
                xtype: 'phonebookPanel'
            }
        });
        var phonebooksStore = this.getPhonebooksStore();
        phonebooksStore.load(function (store, records) {
            var competitions = Ext.Array.sort(phonebooksStore.collect('libelle_competition'));
            Ext.each(competitions, function (competition) {
                var panelCompetition = Ext.create('Ext.panel.Panel', {
                    layout: {
                        type: 'table',
                        columns: 4
                    },
                    title: competition,
                    autoScroll: true,
                    items: []
                });
                phonebooksStore.clearFilter(true);
                phonebooksStore.filter('libelle_competition', competition);
                var divisions = phonebooksStore.collect('division');
                Ext.each(divisions, function (division) {
                    var panelDivision = Ext.create('Ext.panel.Panel', {
                        width: 180,
                        height: 250,
                        header: false,
                        border: false,
                        items: []
                    });
                    var storeDiv = Ext.create('Ext.data.Store', {
                        model: 'Ufolep13Volley.model.Phonebook',
                        proxy: {
                            type: 'ajax',
                            url: 'ajax/getAnnuaires.php',
                            reader: {
                                type: 'json',
                                root: 'results'
                            }
                        },
                        autoLoad: true
                    });
                    storeDiv.clearFilter(true);
                    storeDiv.filter([
                        {
                            property: 'libelle_competition',
                            value: competition
                        },
                        {
                            filterFn: function (item) {
                                return item.get("division") === division;
                            }
                        }
                    ]);
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
                                renderer: function (value, meta, record) {
                                    var competition = record.get('code_competition');
                                    if (competition === 'c') {
                                        competition = 'm';
                                    }
                                    return Ext.String.format("<a href='annuaire.php?id={0}&c={1}' target='_self'>{2}</a>", record.get('id_equipe'), competition, record.get('nom_equipe'));
                                }
                            }
                        ]
                    });
                    panelCompetition.add(panelDivision);
                });
                var panel = Ext.ComponentQuery.query('panel[id=phonebooksContainer]')[0];
                panel.add(panelCompetition);
                panel.setActiveTab(0);
            });
        });
    }
});