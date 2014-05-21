Ext.define('Ufolep13Volley.controller.TeamManagement', {
    extend: 'Ext.app.Controller',
    stores: ['Clubs', 'MyTeam', 'Players', 'MyPlayers'],
    models: ['Club', 'Team', 'Player'],
    views: ['team.Edit', 'team.ModifyPassword', 'team.PlayersManage', 'team.PlayerAddToMyTeam', 'team.SetMyTeamCaptain'],
    refs: [
        {
            ref: 'teamDetailsForm',
            selector: "form[title=Vos Détails]"
        },
        {
            ref: 'teamEditForm',
            selector: "window[title=Modification de l'équipe] > form"
        },
        {
            ref: 'teamEditWindow',
            selector: "window[title=Modification de l'équipe]"
        },
        {
            ref: 'addPlayerToMyTeamForm',
            selector: "window[title=Ajout d'un joueur] > form"
        },
        {
            ref: 'addPlayerToMyTeamWindow',
            selector: "window[title=Ajout d'un joueur]"
        },
        {
            ref: 'setMyTeamCaptainForm',
            selector: "window[title=Modifier le responsable d'équipe] > form"
        },
        {
            ref: 'setMyTeamCaptainWindow',
            selector: "window[title=Modifier le responsable d'équipe]"
        },
        {
            ref: 'connectedTeamNameToolbarText',
            selector: "tbtext"
        }
    ],
    init: function() {
        this.control(
                {
                    'button[text=Gestions des joueurs/joueuses]': {
                        click: this.showManagePlayers
                    },
                    'button[text=Modifier les informations]': {
                        click: this.showTeamEdit
                    },
                    'button[text=Changer de mot de passe]': {
                        click: this.showModifyPassword
                    },
                    'button[text=Ajouter un joueur]': {
                        click: this.showAddPlayerToMyTeam
                    },
                    'form[url=ajax/addPlayerToMyTeam.php] > toolbar > button[text=Sauver]': {
                        click: this.saveAddPlayerToMyTeam
                    },
                    "button[text=Modifier le responsable d'équipe]": {
                        click: this.showSetMyTeamCaptain
                    },
                    'form[url=ajax/updateMyTeamCaptain.php] > toolbar > button[text=Sauver]': {
                        click: this.saveSetMyTeamCaptain
                    },
                    'form[title=Vos Détails]': {
                        render: this.loadTeamDetails
                    },
                    "window[title=Modification de l'équipe] > form > toolbar > button[action=save]": {
                        click: this.saveTeamDetails
                    }
                });
    },
    loadTeamDetails: function() {
        var me = this;
        var form = this.getTeamDetailsForm();
        this.getMyTeamStore().load({
            callback: function(records, operation, success) {
                form.getForm().loadRecord(records[0]);
                me.getConnectedTeamNameToolbarText().setText(records[0].get('team_full_name'));
            }
        });
    },
    saveTeamDetails: function() {
        var me = this;
        var form = this.getTeamEditForm().getForm();
        if (form.isValid()) {
            var dirtyFieldsJson = form.getFieldValues(true);
            var dirtyFieldsArray = [];
            for (var key in dirtyFieldsJson) {
                dirtyFieldsArray.push(key);
            }
            form.submit({
                params: {
                    dirtyFields: dirtyFieldsArray.join(',')
                },
                success: function(form, action) {
                    me.loadTeamDetails();
                    me.getTeamEditWindow().close();
                },
                failure: function(form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    saveSetMyTeamCaptain: function() {
        var windowSetMyTeamCaptain = this.getSetMyTeamCaptainWindow();
        var form = this.getSetMyTeamCaptainForm().getForm();
        var storeMyPlayers = this.getMyPlayersStore();
        if (form.isValid()) {
            form.submit({
                success: function(form, action) {
                    storeMyPlayers.load();
                    windowSetMyTeamCaptain.close();
                },
                failure: function(form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    showSetMyTeamCaptain: function() {
        Ext.widget('setmyteamcaptain');
    },
    saveAddPlayerToMyTeam: function() {
        var windowAddPlayerToMyTeam = this.getAddPlayerToMyTeamWindow();
        var form = this.getAddPlayerToMyTeamForm().getForm();
        var storeMyPlayers = this.getMyPlayersStore();
        if (form.isValid()) {
            form.submit({
                success: function(form, action) {
                    storeMyPlayers.load();
                    windowAddPlayerToMyTeam.close();
                },
                failure: function(form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    showAddPlayerToMyTeam: function() {
        Ext.widget('playeraddtomyteam');
    },
    showManagePlayers: function() {
        Ext.widget('playersmanage');
    },
    showTeamEdit: function() {
        Ext.widget('teamedit');
        var form = this.getTeamEditForm();
        this.getMyTeamStore().load({
            callback: function(records, operation, success) {
                form.getForm().loadRecord(records[0]);
            }
        });
    },
    showModifyPassword: function() {
        Ext.widget('modifypassword');
    }
});