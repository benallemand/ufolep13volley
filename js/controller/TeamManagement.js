Ext.define('Ufolep13Volley.controller.TeamManagement', {
    extend: 'Ext.app.Controller',
    stores: ['Clubs', 'MyTeam', 'Players', 'MyPlayers', 'MyPreferences', 'TimeSlots', 'Gymnasiums', 'Teams', 'Days', 'Alerts'],
    models: ['Club', 'Team', 'Player', 'Preference', 'TimeSlot', 'Gymnasium', 'Day', 'Alert'],
    views: ['team.Edit', 'team.ModifyPassword', 'team.PlayersManage', 'team.TimeSlotsManage', 'team.PlayerAddToMyTeam', 'team.SetMyTeamCaptain', 'team.SetMyTeamLeader', 'team.SetMyTeamViceLeader', 'player.Edit', 'timeslot.Edit', 'team.EditPreferences'],
    refs: [
        {
            ref: 'teamDetailsForm',
            selector: "formTeamDetails"
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
            selector: "setmyteamcaptain > form"
        },
        {
            ref: 'setMyTeamLeaderForm',
            selector: "setmyteamleader > form"
        },
        {
            ref: 'setMyTeamViceLeaderForm',
            selector: "setmyteamviceleader > form"
        },
        {
            ref: 'setMyTeamCaptainWindow',
            selector: "setmyteamcaptain"
        },
        {
            ref: 'setMyTeamLeaderWindow',
            selector: "setmyteamleader"
        },
        {
            ref: 'setMyTeamViceLeaderWindow',
            selector: "setmyteamviceleader"
        },
        {
            ref: 'connectedTeamNameToolbarText',
            selector: "tbtext"
        },
        {
            ref: 'formPanelEditPlayer',
            selector: 'playeredit form'
        },
        {
            ref: 'formPanelEditTimeSlot',
            selector: 'timeslotedit form'
        },
        {
            ref: 'WindowEditPlayer',
            selector: 'playeredit'
        },
        {
            ref: 'WindowEditTimeSlot',
            selector: 'timeslotedit'
        },
        {
            ref: 'EditPreferencesForm',
            selector: 'editpreferences form'
        },
        {
            ref: 'EditPreferencesWindow',
            selector: 'editpreferences'
        },
        {
            ref: 'MyPlayersGrid',
            selector: 'playersmanage > grid'
        },
        {
            ref: 'TimeSlotsGrid',
            selector: 'timeslotsmanage > grid'
        },
        {
            ref: 'SelectPlayerCombo',
            selector: 'playeraddtomyteam combo[name=id_joueur]'
        },
        {
            ref: 'SelectPlayerImage',
            selector: 'playeraddtomyteam image'
        },
        {
            ref: 'SelectPlayerSubmitButton',
            selector: 'playeraddtomyteam button[action=save]'
        }
    ],
    init: function () {
        this.control(
                {
                    'playeraddtomyteam combo[name=id_joueur]': {
                        select: this.setPlayerImage
                    },
                    'button[action=showTimeSlotsManage]': {
                        click: this.showManageTimeSlots
                    },
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
                    'playeraddtomyteam button[action=save]': {
                        click: this.saveAddPlayerToMyTeam
                    },
                    "button[action=modifyCaptain]": {
                        click: this.showSetMyTeamCaptain
                    },
                    "button[action=modifyLeader]": {
                        click: this.showSetMyTeamLeader
                    },
                    "button[action=modifyViceLeader]": {
                        click: this.showSetMyTeamViceLeader
                    },
                    'setmyteamcaptain button[action=save]': {
                        click: this.saveSetMyTeamCaptain
                    },
                    'setmyteamleader button[action=save]': {
                        click: this.saveSetMyTeamLeader
                    },
                    'setmyteamviceleader button[action=save]': {
                        click: this.saveSetMyTeamViceLeader
                    },
                    'formTeamDetails': {
                        render: this.loadTeamDetails
                    },
                    "window[title=Modification de l'équipe] > form > toolbar > button[action=save]": {
                        click: this.saveTeamDetails
                    },
                    'button[action=createPlayer]': {
                        click: this.createPlayer
                    },
                    'button[action=createTimeSlot]': {
                        click: this.createTimeSlot
                    },
                    'playersmanage > grid': {
                        itemdblclick: this.editPlayer
                    },
                    'timeslotsmanage > grid': {
                        itemdblclick: this.editTimeSlot
                    },
                    'button[action=editPlayer]': {
                        click: this.editPlayer
                    },
                    'button[action=editTimeSlot]': {
                        click: this.editTimeSlot
                    },
                    'playeredit button[action=save]': {
                        click: this.savePlayer
                    },
                    'timeslotedit button[action=save]': {
                        click: this.saveTimeSlot
                    },
                    'button[action=editPreferences]': {
                        click: this.showEditPreferences
                    },
                    'editpreferences button[action=save]': {
                        click: this.savePreferences
                    },
                    "button[action=removePlayerFromMyTeam]": {
                        click: this.removePlayerFromMyTeam
                    },
                    "button[action=removeTimeSlot]": {
                        click: this.removeTimeSlot
                    },
                    "gridAlerts actioncolumn": {
                        itemclick: this.getAlertResolution
                    }
                });
    },
    setPlayerImage: function (combo, records) {
        this.getSelectPlayerImage().setSrc(records[0].get('path_photo'));
        this.getSelectPlayerSubmitButton().focus(false, 100);
    },
    getAlertResolution: function (column, action, view, rowIndex, colIndex, item, e) {
        var record = this.getAlertsStore().getAt(rowIndex);
        switch (record.get('expected_action')) {
            case 'showHelpSelectLeader':
                this.showSetMyTeamLeader();
                break;
            case 'showHelpSelectViceLeader':
                this.showSetMyTeamViceLeader();
                break;
            case 'showHelpSelectCaptain':
                this.showSetMyTeamCaptain();
                break;
            case 'showHelpSelectTimeSlot':
                this.showManageTimeSlots();
                Ext.Msg.alert('Ajout de créneau de gymnase', "Merci d'indiquer les créneaux auxquels vous pouvez recevoir les matches.");
                break;
            case 'showHelpAddPhoneNumber':
                this.showManagePlayers();
                Ext.Msg.alert('Numéro de téléphone', 'Editer le capitaine ou le suppléant, et ajouter au moins un numéro de téléphone.');
                break;
            case 'showHelpAddEmail':
                this.showManagePlayers();
                Ext.Msg.alert('Adresse email', 'Editer le capitaine ou le suppléant, et ajouter au moins une adresse email.');
                break;
            case 'showHelpAddPlayer':
                this.showManagePlayers();
                Ext.Msg.alert('Ajout de joueur', "Cliquer sur 'Ajouter un joueur' pour sélectionner l'un des joueurs connus du système. Si ce joueur n'existe pas, cliquer sur 'Créer un joueur'. Les joueurs n'apparaissent pas immédiatement sur la fiche équipe, ils doivent être activés par les responsables UFOLEP.");
                break;
            case 'showHelpInactivePlayers':
                this.showManagePlayers();
                Ext.Msg.alert('Joueurs inactifs', "Les joueurs en rouge sont inactifs. Ils n'apparaitront sur la fiche équipe qu'une fois actifs. Pour ce faire, les responsables UFOLEP doivent vérifier la validité de ces joueurs. Si le délai de prise en compte vous semble long, merci de relancer le responsable UFOLEP du championnat/division/coupe/poule concerné.");
                break;
        }
    },
    savePreferences: function () {
        var thisController = this;
        var form = this.getEditPreferencesForm().getForm();
        if (form.isValid()) {
            form.submit({
                success: function () {
                    thisController.getEditPreferencesWindow().close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    showEditPreferences: function () {
        Ext.widget('editpreferences');
        var form = this.getEditPreferencesForm();
        this.getMyPreferencesStore().load({
            callback: function (records) {
                form.getForm().loadRecord(records[0]);
            }
        });
    },
    savePlayer: function () {
        var thisController = this;
        var form = this.getFormPanelEditPlayer().getForm();
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
                success: function () {
                    thisController.getPlayersStore().load();
                    thisController.getMyPlayersStore().load();
                    thisController.getAlertsStore().load();
                    thisController.getWindowEditPlayer().close();
                    thisController.getAddPlayerToMyTeamWindow().close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    saveTimeSlot: function () {
        var thisController = this;
        var form = this.getFormPanelEditTimeSlot().getForm();
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
                success: function () {
                    thisController.getTimeSlotsStore().load();
                    thisController.getAlertsStore().load();
                    thisController.getWindowEditTimeSlot().close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    createPlayer: function () {
        Ext.widget('playeredit');
        var idClub = this.getMyTeamStore().getAt(0).get('id_club');
        var idTeam = this.getMyTeamStore().getAt(0).get('id_equipe');
        this.getFormPanelEditPlayer().getForm().findField('id_team').setValue(idTeam);
        this.getFormPanelEditPlayer().getForm().findField('id_club').setValue(idClub);
        this.getFormPanelEditPlayer().down('checkboxfield[name=est_actif]').setValue(false);
        this.getFormPanelEditPlayer().down('checkboxfield[name=est_actif]').hide();
        this.getFormPanelEditPlayer().down('textfield[name=prenom]').focus();
    },
    createTimeSlot: function () {
        Ext.widget('timeslotedit');
        var idTeam = this.getMyTeamStore().getAt(0).get('id_equipe');
        this.getFormPanelEditTimeSlot().getForm().findField('id_equipe').setValue(idTeam);
        this.getFormPanelEditTimeSlot().down('combo[name=id_equipe]').hide();
    },
    editTimeSlot: function () {
        var record = this.getTimeSlotsGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('timeslotedit');
        this.getFormPanelEditTimeSlot().loadRecord(record);
        this.getFormPanelEditTimeSlot().down('combo[name=id_equipe]').hide();
    },
    editPlayer: function () {
        var record = this.getMyPlayersGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('playeredit');
        this.getFormPanelEditPlayer().loadRecord(record);
        this.getFormPanelEditPlayer().down('checkboxfield[name=est_actif]').hide();
        this.getFormPanelEditPlayer().down('datefield[name=date_homologation]').hide();
        this.getFormPanelEditPlayer().down('displayfield[name=team_leader_list]').hide();
        this.getFormPanelEditPlayer().down('displayfield[name=teams_list]').hide();
        this.getFormPanelEditPlayer().down('checkboxfield[name=est_responsable_club]').hide();
        this.getFormPanelEditPlayer().down('textfield[name=prenom]').focus();
    },
    loadTeamDetails: function () {
        var me = this;
        var form = this.getTeamDetailsForm();
        this.getMyTeamStore().load({
            callback: function (records, operation, success) {
                form.getForm().loadRecord(records[0]);
                me.getConnectedTeamNameToolbarText().setText(records[0].get('team_full_name'));
            }
        });
    },
    saveTeamDetails: function () {
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
                success: function (form, action) {
                    me.loadTeamDetails();
                    me.getTeamEditWindow().close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    saveSetMyTeamCaptain: function () {
        var thisController = this;
        var windowSetMyTeamCaptain = this.getSetMyTeamCaptainWindow();
        var form = this.getSetMyTeamCaptainForm().getForm();
        var storeMyPlayers = this.getMyPlayersStore();
        if (form.isValid()) {
            form.submit({
                success: function (form, action) {
                    storeMyPlayers.load();
                    thisController.getAlertsStore().load();
                    windowSetMyTeamCaptain.close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    saveSetMyTeamLeader: function () {
        var thisController = this;
        var windowSetMyTeamLeader = this.getSetMyTeamLeaderWindow();
        var form = this.getSetMyTeamLeaderForm().getForm();
        var storeMyPlayers = this.getMyPlayersStore();
        if (form.isValid()) {
            form.submit({
                success: function (form, action) {
                    storeMyPlayers.load();
                    thisController.getAlertsStore().load();
                    windowSetMyTeamLeader.close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    saveSetMyTeamViceLeader: function () {
        var thisController = this;
        var windowSetMyTeamViceLeader = this.getSetMyTeamViceLeaderWindow();
        var form = this.getSetMyTeamViceLeaderForm().getForm();
        var storeMyPlayers = this.getMyPlayersStore();
        if (form.isValid()) {
            form.submit({
                success: function (form, action) {
                    thisController.getAlertsStore().load();
                    storeMyPlayers.load();
                    windowSetMyTeamViceLeader.close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    showSetMyTeamCaptain: function () {
        Ext.widget('setmyteamcaptain');
    },
    showSetMyTeamLeader: function () {
        Ext.widget('setmyteamleader');
    },
    showSetMyTeamViceLeader: function () {
        Ext.widget('setmyteamviceleader');
    },
    saveAddPlayerToMyTeam: function () {
        var thisController = this;
        var windowAddPlayerToMyTeam = this.getAddPlayerToMyTeamWindow();
        var form = this.getAddPlayerToMyTeamForm().getForm();
        var storeMyPlayers = this.getMyPlayersStore();
        if (form.isValid()) {
            form.submit({
                success: function (form, action) {
                    storeMyPlayers.load();
                    thisController.getAlertsStore().load();
                    windowAddPlayerToMyTeam.close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    showAddPlayerToMyTeam: function () {
        Ext.widget('playeraddtomyteam');
        this.getSelectPlayerCombo().focus();
    },
    showManagePlayers: function () {
        Ext.widget('playersmanage');
    },
    showManageTimeSlots: function () {
        Ext.widget('timeslotsmanage');
    },
    showTeamEdit: function () {
        Ext.widget('teamedit');
        var form = this.getTeamEditForm();
        this.getMyTeamStore().load({
            callback: function (records, operation, success) {
                form.getForm().loadRecord(records[0]);
            }
        });
    },
    showModifyPassword: function () {
        Ext.widget('modifypassword');
    },
    removePlayerFromMyTeam: function () {
        var thisController = this;
        var gridMyPlayers = this.getMyPlayersGrid();
        var currentRecord = gridMyPlayers.getSelectionModel().getSelection()[0];
        if (!currentRecord) {
            return;
        }
        var storeMyPlayers = gridMyPlayers.getStore();
        Ext.Msg.show({
            title: 'Retirer un joueur',
            msg: 'Voulez-vous retirer ' + currentRecord.get('prenom') + ' ' + currentRecord.get('nom') + ' de votre équipe ?',
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn === 'ok') {
                    Ext.Ajax.request({
                        url: 'ajax/removePlayerFromMyTeam.php',
                        params: {
                            id: currentRecord.get('id')
                        },
                        success: function (response) {
                            var responseJson = Ext.decode(response.responseText);
                            Ext.Msg.alert('Info', responseJson.message);
                            storeMyPlayers.load();
                            thisController.getAlertsStore().load();
                        }
                    });
                }
            }
        });
    },
    removeTimeSlot: function () {
        var thisController = this;
        var gridTimeSlots = this.getTimeSlotsGrid();
        var currentRecord = gridTimeSlots.getSelectionModel().getSelection()[0];
        if (!currentRecord) {
            return;
        }
        var storeTimeSlots = gridTimeSlots.getStore();
        Ext.Msg.show({
            title: 'Retirer un créneau',
            msg: 'Voulez-vous retirer ce créneau de gymnase pour votre équipe ?',
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn === 'ok') {
                    Ext.Ajax.request({
                        url: 'ajax/removeTimeSlot.php',
                        params: {
                            id: currentRecord.get('id')
                        },
                        success: function (response) {
                            var responseJson = Ext.decode(response.responseText);
                            Ext.Msg.alert('Info', responseJson.message);
                            storeTimeSlots.load();
                            thisController.getAlertsStore().load();
                        }
                    });
                }
            }
        });
    }
});