Ext.define('Ufolep13Volley.controller.mobile.Teams', {
    extend: 'Ext.app.Controller',
    requires: [
        'Ext.form.Panel',
        'Ext.field.Hidden',
        'Ext.util.Geolocation',
        'Ufolep13Volley.view.mobile.Players'
    ],
    config: {
        refs: {
            teamsList: 'listteams',
            playersList: 'listplayers',
            mainPanel: 'navigationview',
            formPanel: 'formpanel[title=Details]',
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
    doPhoneCall: function () {
        window.open('tel:' + this.getFormPanel().getValues().telephone_1, '_self');
    },
    doViewPlayers: function () {
        this.getMainPanel().push({
            xtype: 'listplayers'
        });
        this.getPlayersList().getStore().load({
            params: {
                idTeam: this.getFormPanel().getValues().id_equipe
            }
        });
    },
    doMap: function () {
        var controller = this;
        var geo = Ext.create('Ext.util.Geolocation', {
            autoUpdate: false,
            listeners: {
                locationupdate: function (geo) {
                    var currentLat = geo.getLatitude();
                    var currentLong = geo.getLongitude();
                    var currentLoc = currentLat + ',' + currentLong;
                    if (Ext.os.is.iOS) {
                        var gps = ((((((controller.getFormPanel().getValues().gymnasiums_list).split('\n'))[0]).split(' ('))[0]).split(' - '))[3];
                        window.open('http://maps.apple.com?daddr=' + gps + '&saddr=' + currentLoc);
                    }
                    else if (Ext.os.is.Android) {
                        window.open('geo:' + currentLoc);
                    }
                    else {
                        var gps = ((((((controller.getFormPanel().getValues().gymnasiums_list).split('\n'))[0]).split(' ('))[0]).split(' - '))[3];
                        window.open('http://www.google.com/maps/dir/' + gps + '/' + currentLoc);
                    }
                },
                locationerror: function (geo, bTimeout, bPermissionDenied, bLocationUnavailable, message) {
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
    doSelectTeam: function (list, index, item, record) {
        this.getMainPanel().push({
            title: record.get('nom_equipe'),
            layout: 'fit',
            items: [
                {
                    title: 'Details',
                    xtype: 'formpanel',
                    url: 'ajax/getQuickDetails.php?id_equipe=' + record.get('id_equipe'),
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
                                    icon: 'images/phone.png'
                                },
                                {
                                    text: 'Carte',
                                    action: 'map',
                                    icon: 'images/map.png'
                                },
                                {
                                    text: 'Joueurs',
                                    action: 'viewPlayers',
                                    icon: 'images/man.png'
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
                            xtype: 'textareafield',
                            label: 'Creneaux',
                            name: 'gymnasiums_list'
                        }
                    ]
                }
            ]
        });
        this.getFormPanel().load({
            success: function (form, result, data) {
                form.setValues(result.data);
            }
        });
    }
}
);
