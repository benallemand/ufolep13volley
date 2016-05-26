Ext.define('Ufolep13Volley.controller.mobile.Tournaments', {
        extend: 'Ext.app.Controller',
        requires: [
            'Ufolep13Volley.view.mobile.SearchTeam',
            'Ufolep13Volley.view.mobile.Teams'
        ],
        config: {
            refs: {
                tournamentsList: 'listtournaments',
                mainPanel: 'navigationview'
            },
            control: {
                tournamentsList: {
                    itemtap: 'doSelectTournament'
                }
            }
        },
        doSelectTournament: function (list, index, item, record) {
            this.getMainPanel().push({
                title: record.get('libelle'),
                layout: 'vbox',
                items: [
                    {
                        xtype: 'searchfieldteam'
                    },
                    {
                        xtype: 'listteams'
                    }
                ]
            });
            Ext.getStore('Phonebooks').clearFilter(true);
            Ext.getStore('Phonebooks').filter([{
                property: 'code_competition',
                value: record.get('code_competition'),
                exactMatch: true
            }]);
        }
    }
);
