Ext.define('Ufolep13Volley.controller.Administration', {
    extend: 'Ext.app.Controller',
    stores: ['Players', 'Clubs'],
    models: ['Player', 'Club'],
    views: ['player.Grid', 'player.Edit', 'club.Select'],
    refs: [
        {
            ref: 'managePlayersGrid',
            selector: 'grid[title=Gestion des joueurs]'
        },
        {
            ref: 'mainPanel',
            selector: 'panel[title=Panneau Principal]'
        },
        {
            ref: 'formPanelSelectClub',
            selector: 'clubselect form'
        },
        {
            ref: 'formPanelEditPlayer',
            selector: 'playeredit form'
        },
        {
            ref: 'windowSelectClub',
            selector: 'clubselect'
        },
        {
            ref: 'windowEditPlayer',
            selector: 'playeredit'
        }
    ],
    init: function() {
        this.control(
                {
                    'button[text=Créer un joueur]': {
                        click: this.addPlayer
                    },
                    'button[text=Editer joueur]': {
                        click: this.editPlayer
                    },
                    'grid[title=Gestion des joueurs]': {
                        itemdblclick: this.editPlayer
                    },
                    'playeredit button[action=save]': {
                        click: this.updatePlayer
                    },
                    'button[text=Gestion des joueurs]': {
                        click: this.showPlayersGrid
                    },
                    'button[text=Associer à un club]': {
                        click: this.showClubSelect
                    },
                    'clubselect button[action=save]': {
                        click: this.linkPlayerToClub
                    }
                }
        );
    },
    editPlayer: function() {
        var record = this.getManagePlayersGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('playeredit');
        this.getFormPanelEditPlayer().loadRecord(record);
    },
    addPlayer: function() {
        Ext.widget('playeredit');
    },
    updatePlayer: function() {
        var thisController = this;
        var form = this.getFormPanelEditPlayer().getForm();
        if (form.isValid()) {
            form.submit({
                success: function() {
                    thisController.getManagePlayersGrid().getStore().load();
                    thisController.getWindowEditPlayer().close();
                    thisController.getManagePlayersGrid().getSelectionModel().deselectAll();
                },
                failure: function(form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    showPlayersGrid: function() {
        this.getMainPanel().removeAll();
        this.getMainPanel().setAutoScroll(true);
        this.getMainPanel().add({
            xtype: 'playersgrid'
        });
        this.getManagePlayersGrid().getStore().load();
    },
    showClubSelect: function() {
        var records = this.getManagePlayersGrid().getSelectionModel().getSelection();
        var idPlayers = [];
        Ext.each(records, function(record) {
            idPlayers.push(record.get('id'));
        });
        if (idPlayers.length === 0) {
            return;
        }
        Ext.widget('clubselect');
        this.getFormPanelSelectClub().getForm().setValues({
            id_players: idPlayers.join(',')
        });
    },
    linkPlayerToClub: function() {
        var thisController = this;
        var form = this.getFormPanelSelectClub().getForm();
        if (form.isValid()) {
            form.submit({
                success: function() {
                    thisController.getManagePlayersGrid().getStore().load();
                    thisController.getWindowSelectClub().close();
                },
                failure: function(form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    }
});