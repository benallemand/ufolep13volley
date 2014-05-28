Ext.define('Ufolep13.controller.Teams', {
    extend: 'Ext.app.Controller',
    requires: [
        'Ext.form.Panel',
        'Ext.field.Hidden',
        'Ext.util.Geolocation',
        'Ufolep13.view.Players'
    ],
    config: {
        refs: {
            teamsList: 'listteams',
            playersList: 'listplayers',
            mainPanel: 'navigationview',
            formPanel: 'formpanel',
            callButton: 'button[action=call]',
            mapButton: 'button[action=map]',
            viewPlayersButton: 'button[action=viewPlayers]'
        },
        control: {
            teamsList: {
                itemtap: 'doSelectTeam'
            },
            callButton: {
                tap: 'doPhoneCall'
            },
            mapButton: {
                tap: 'doMap'
            },
            viewPlayersButton: {
                tap: 'doViewPlayers'
            }
        }
    },
    doPhoneCall: function() {
        window.open('tel:' + this.getFormPanel().getValues().telephone_1, '_self');
    },
    doViewPlayers: function() {
        this.getMainPanel().push({
            xtype: 'listplayers'
        });
        this.getPlayersList().getStore().load({
            params: {
                idTeam: this.getFormPanel().getValues().id_equipe
            }
        });
    },
    doMap: function() {
        var controller = this;
        var geo = Ext.create('Ext.util.Geolocation', {
            autoUpdate: false,
            listeners: {
                locationupdate: function(geo) {
                    var currentLat = geo.getLatitude();
                    var currentLong = geo.getLongitude();
                    var currentLoc = currentLat + ',' + currentLong;
                    window.open('http://maps.apple.com?daddr=' + controller.getFormPanel().getValues().localisation + '&saddr=' + currentLoc);
                },
                locationerror: function(geo, bTimeout, bPermissionDenied, bLocationUnavailable, message) {
                    if (bTimeout) {
                        alert('Erreur : Temps de reponse trop important.');
                    } else {
                        alert('Erreur : ' + message);
                    }
                }
            }
        });
        geo.updateLocation();
    },
    doSelectTeam: function(list, index, item, record) {
        this.getMainPanel().push({
            title: record.get('nom_equipe'),
            layout: 'fit',
            items: [
                {
                    xtype: 'formpanel',
                    url: '../ajax/getQuickDetails.php?id_equipe=' + record.get('id_equipe'),
                    defaults: {
                        xtype: 'textfield',
                        readOnly: true
                    },
                    items: [
                        {
                            xtype: 'toolbar',
                            docked: 'top',
                            items: [
                                {
                                    text: 'Appel',
                                    action: 'call',
                                    icon: '../images/phone.png'
                                },
                                {
                                    text: 'Carte',
                                    action: 'map',
                                    icon: '../images/map.png'
                                },
                                {
                                    text: 'Joueurs',
                                    action: 'viewPlayers',
                                    icon: '../images/man.png'
                                }
                            ]
                        },
                        {
                            xtype: 'hiddenfield',
                            name: 'id_equipe'
                        },
                        {
                            label: 'Responsable',
                            name: 'responsable'
                        },
                        {
                            label: 'Telephone',
                            name: 'telephone_1'
                        },
                        {
                            label: 'Telephone (2)',
                            name: 'telephone_2'
                        },
                        {
                            label: 'Mail',
                            name: 'email'
                        },
                        {
                            label: 'Gymnase',
                            name: 'gymnase'
                        },
                        {
                            label: 'GPS',
                            name: 'localisation'
                        },
                        {
                            label: 'Jour',
                            name: 'jour_reception'
                        },
                        {
                            label: 'Heure',
                            name: 'heure_reception'
                        }
                    ]
                }
            ]
        });
        this.getFormPanel().load();
    }
}
);
