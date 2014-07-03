Ext.define('Ufolep13Volley.controller.Administration', {
    extend: 'Ext.app.Controller',
    stores: ['Players', 'Clubs', 'Teams', 'Profiles'],
    models: ['Player', 'Club', 'Team', 'Profile'],
    views: ['player.Grid', 'player.Edit', 'club.Select', 'team.Select', 'profile.Grid', 'profile.Edit'],
    refs: [
        {
            ref: 'managePlayersGrid',
            selector: 'playersgrid'
        },
        {
            ref: 'manageProfilesGrid',
            selector: 'profilesgrid'
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
            ref: 'formPanelSelectTeam',
            selector: 'teamselect form'
        },
        {
            ref: 'formPanelEditPlayer',
            selector: 'playeredit form'
        },
        {
            ref: 'formPanelEditProfile',
            selector: 'profileedit form'
        },
        {
            ref: 'windowSelectClub',
            selector: 'clubselect'
        },
        {
            ref: 'windowSelectTeam',
            selector: 'teamselect'
        },
        {
            ref: 'windowEditPlayer',
            selector: 'playeredit'
        },
        {
            ref: 'windowEditProfile',
            selector: 'profileedit'
        }
    ],
    init: function() {
        this.control(
                {
                    'button[action=addPlayer]': {
                        click: this.addPlayer
                    },
                    'button[action=editPlayer]': {
                        click: this.editPlayer
                    },
                    'button[action=addProfile]': {
                        click: this.addProfile
                    },
                    'button[action=editProfile]': {
                        click: this.editProfile
                    },
                    'playersgrid': {
                        itemdblclick: this.editPlayer
                    },
                    'profilesgrid': {
                        itemdblclick: this.editProfile
                    },
                    'playeredit button[action=save]': {
                        click: this.updatePlayer
                    },
                    'profileedit button[action=save]': {
                        click: this.updateProfile
                    },
                    'button[action=managePlayers]': {
                        click: this.showPlayersGrid
                    },
                    'button[action=manageProfiles]': {
                        click: this.showProfilesGrid
                    },
                    'button[action=showClubSelect]': {
                        click: this.showClubSelect
                    },
                    'button[action=showTeamSelect]': {
                        click: this.showTeamSelect
                    },
                    'clubselect button[action=save]': {
                        click: this.linkPlayerToClub
                    },
                    'teamselect button[action=save]': {
                        click: this.linkPlayerToTeam
                    },
                    'playersgrid > toolbar[dock=top] > textfield[fieldLabel=Recherche]': {
                        change: this.searchPlayer
                    }
                }
        );
    },
    searchPlayer: function(textfield, searchText) {
        var searchTerms = searchText.split(',');
        var store = this.getPlayersStore();
        store.clearFilter(true);
        store.filter(
                {
                    filterFn: function(item) {
                        var queribleFields = ['nom', 'prenom', 'num_licence', 'club', 'teams_list'];
                        var found = false;
                        Ext.each(searchTerms, function(searchTerm) {
                            var regExp = new RegExp(searchTerm, "i");
                            Ext.each(queribleFields, function(queribleField) {
                                if (!item.get(queribleField)) {
                                    return true;
                                }
                                if (regExp.test(item.get(queribleField))) {
                                    found = true;
                                    return false;
                                }
                            });
                            if (found) {
                                return false;
                            }
                            return true;
                        });
                        return found;
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
    editProfile: function() {
        var record = this.getManageProfilesGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('profileedit');
        this.getFormPanelEditProfile().loadRecord(record);
    },
    addPlayer: function() {
        Ext.widget('playeredit');
    },
    addProfile: function() {
        Ext.widget('profileedit');
    },
    updatePlayer: function() {
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
                success: function() {
                    thisController.getPlayersStore().load();
                    thisController.getWindowEditPlayer().close();
                    thisController.getManagePlayersGrid().getSelectionModel().deselectAll();
                },
                failure: function(form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateProfile: function() {
        var thisController = this;
        var form = this.getFormPanelEditProfile().getForm();
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
                success: function() {
                    thisController.getProfilesStore().load();
                    thisController.getWindowEditProfile().close();
                    thisController.getManageProfilesGrid().getSelectionModel().deselectAll();
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
        this.getPlayersStore().load();
    },
    showProfilesGrid: function() {
        this.getMainPanel().removeAll();
        this.getMainPanel().setAutoScroll(true);
        this.getMainPanel().add({
            xtype: 'profilesgrid'
        });
        this.getProfilesStore().load();
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
    showTeamSelect: function() {
        var records = this.getManagePlayersGrid().getSelectionModel().getSelection();
        var idPlayers = [];
        Ext.each(records, function(record) {
            idPlayers.push(record.get('id'));
        });
        if (idPlayers.length === 0) {
            return;
        }
        Ext.widget('teamselect');
        this.getFormPanelSelectTeam().getForm().setValues({
            id_players: idPlayers.join(',')
        });
    },
    linkPlayerToClub: function() {
        var thisController = this;
        var form = this.getFormPanelSelectClub().getForm();
        if (form.isValid()) {
            form.submit({
                success: function() {
                    thisController.getPlayersStore().load();
                    thisController.getWindowSelectClub().close();
                },
                failure: function(form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    linkPlayerToTeam: function() {
        var thisController = this;
        var form = this.getFormPanelSelectTeam().getForm();
        if (form.isValid()) {
            form.submit({
                success: function() {
                    thisController.getPlayersStore().load();
                    thisController.getWindowSelectTeam().close();
                },
                failure: function(form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    }
});