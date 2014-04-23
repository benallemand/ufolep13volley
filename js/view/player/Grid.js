Ext.define('Ufolep13Volley.view.player.Grid', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.playersgrid',
    title: 'Gestion des joueurs',
    autoScroll: true,
    selType: 'checkboxmodel',
    store: 'Players',
    columns: {
        items: [
            {
                header: 'Photo',
                dataIndex: 'path_photo',
                width: 120,
                renderer: function(val) {
                    return '<img src="' + val + '" width="80px" height="100px">';
                }
            },
            {
                header: 'Nom',
                dataIndex: 'nom'
            },
            {
                header: 'Prenom',
                dataIndex: 'prenom'
            },
            {
                header: 'Sexe',
                dataIndex: 'sexe'
            },
            {
                header: 'Numéro de licence',
                dataIndex: 'num_licence'
            },
            {
                header: 'Club',
                dataIndex: 'club',
                flex: 1
            },
            {
                header: 'Valide',
                dataIndex: 'est_licence_valide',
                xtype: 'checkcolumn',
                listeners: {
                    beforecheckchange: function() {
                        return false;
                    }
                }
            }
        ]
    },
    dockedItems: [
        {
            xtype: 'toolbar',
            dock: 'top',
            items: [
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche',
                    listeners: {
                        change: function(textfield, newValue) {
                            var store = textfield.up('grid').getStore();
                            store.clearFilter(true);
                            store.filter(
                                    {
                                        filterFn: function(item) {
                                            var queribleFields = ['nom', 'prenom', 'num_licence', 'club'];
                                            var found = false;
                                            var regExp = new RegExp(newValue, "i");
                                            Ext.each(queribleFields, function(queribleField) {
                                                if (!item.get(queribleField)) {
                                                    return true;
                                                }
                                                if (regExp.test(item.get(queribleField))) {
                                                    found = true;
                                                    return false;
                                                }
                                            });
                                            return found;
                                        }
                                    }
                            );
                        }
                    }
                },
                {
                    text: 'Associer à un club'
                },
                {
                    text: 'Créer un joueur'
                },
                {
                    text: 'Editer joueur'
                }
            ]
        }
    ]
});