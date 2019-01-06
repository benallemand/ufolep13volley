Ext.define('Ufolep13Volley.view.window.Timeslot', {
    extend: 'Ext.window.Window',
    alias: 'widget.timeslot_edit',
    title: "Edition de créneau",
    height: 400,
    width: 700,
    modal: true,
    layout: 'fit',
    autoShow: true,
    items: {
        xtype: 'form',
        trackResetOnLoad: true,
        layout: 'anchor',
        defaults: {
            anchor: '90%',
            margins: 10
        },
        url: 'ajax/saveTimeSlot.php',
        items: [
            {
                xtype: 'hidden',
                fieldLabel: 'id',
                name: 'id'
            },
            {
                xtype: 'combo',
                fieldLabel: 'Equipe',
                name: 'id_equipe',
                displayField: 'team_full_name',
                valueField: 'id_equipe',
                store: 'Teams',
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true
            },
            {
                xtype: 'combo',
                fieldLabel: 'Gymnase',
                name: 'id_gymnase',
                displayField: 'full_name',
                valueField: 'id',
                store: 'Gymnasiums',
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true
            },
            {
                xtype: 'combo',
                fieldLabel: "Jour de réception",
                name: 'jour',
                displayField: 'name',
                valueField: 'name',
                store: Ext.create('Ext.data.Store', {
                    fields: ['name'],
                    data: [
                        {
                            "name": "Lundi"
                        },
                        {
                            "name": "Mardi"
                        },
                        {
                            "name": "Mercredi"
                        },
                        {
                            "name": "Jeudi"
                        },
                        {
                            "name": "Vendredi"
                        }
                    ]
                }),
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true
            },
            {
                xtype: 'combo',
                fieldLabel: "Heure de réception",
                name: 'heure',
                displayField: 'name',
                valueField: 'name',
                store: Ext.create('Ext.data.Store', {
                    fields: ['name'],
                    data: [
                        {
                            "name": "18:00"
                        },
                        {
                            "name": "18:15"
                        },
                        {
                            "name": "18:30"
                        },
                        {
                            "name": "18:45"
                        },
                        {
                            "name": "19:00"
                        },
                        {
                            "name": "19:15"
                        },
                        {
                            "name": "19:30"
                        },
                        {
                            "name": "19:45"
                        },
                        {
                            "name": "20:00"
                        },
                        {
                            "name": "20:15"
                        },
                        {
                            "name": "20:30"
                        },
                        {
                            "name": "20:45"
                        },
                        {
                            "name": "21:00"
                        },
                        {
                            "name": "21:15"
                        },
                        {
                            "name": "21:30"
                        },
                        {
                            "name": "21:45"
                        }
                    ]
                }),
                queryMode: 'local',
                allowBlank: false,
                forceSelection: true
            },
            {
                name: 'has_time_constraint',
                xtype: 'checkboxfield',
                fieldLabel: 'Contrainte horaire ?',
                boxLabel: 'Oui',
                uncheckedValue: 'off'
            },
            {
                name: 'usage_priority',
                xtype: 'numberfield',
                fieldLabel: "Priorité d'utilisation",
                value: 1,
                minValue: 1,
                allowDecimals: false,
                allowBlank: false,
                allowExponential: false
            }
        ],
        buttons: [
            {
                text: 'Annuler',
                handler: function () {
                    this.up('window').close();
                }
            },
            {
                text: 'Sauver',
                formBind: true,
                disabled: true,
                action: 'save'
            }
        ]
    }
});