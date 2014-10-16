Ext.define('Ufolep13Volley.controller.Administration', {
    extend: 'Ext.app.Controller',
    stores: ['Players', 'Clubs', 'Teams', 'Competitions', 'Profiles', 'Users', 'Gymnasiums', 'Activity', 'WeekSchedule'],
    models: ['Player', 'Club', 'Team', 'Competition', 'Profile', 'User', 'Gymnasium', 'Activity', 'WeekSchedule'],
    views: ['player.Grid', 'player.Edit', 'club.Select', 'team.Select', 'team.Grid', 'team.Edit', 'profile.Grid', 'profile.Edit', 'profile.Select', 'user.Grid', 'user.Edit', 'gymnasium.Grid', 'gymnasium.Edit', 'club.Grid', 'club.Edit', 'activity.Grid', 'timeslot.WeekScheduleGrid'],
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
            ref: 'manageUsersGrid',
            selector: 'usersgrid'
        },
        {
            ref: 'manageGymnasiumsGrid',
            selector: 'gymnasiumsgrid'
        },
        {
            ref: 'manageClubsGrid',
            selector: 'clubsgrid'
        },
        {
            ref: 'manageTeamsGrid',
            selector: 'teamsgrid'
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
            ref: 'formPanelSelectProfile',
            selector: 'profileselect form'
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
            ref: 'formPanelEditUser',
            selector: 'useredit form'
        },
        {
            ref: 'formPanelEditGymnasium',
            selector: 'gymnasiumedit form'
        },
        {
            ref: 'formPanelEditClub',
            selector: 'clubedit form'
        },
        {
            ref: 'formPanelEditTeam',
            selector: 'teamedit form'
        },
        {
            ref: 'windowSelectClub',
            selector: 'clubselect'
        },
        {
            ref: 'windowSelectProfile',
            selector: 'profileselect'
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
        },
        {
            ref: 'windowEditUser',
            selector: 'useredit'
        },
        {
            ref: 'windowEditGymnasium',
            selector: 'gymnasiumedit'
        },
        {
            ref: 'windowEditClub',
            selector: 'clubedit'
        },
        {
            ref: 'windowEditTeam',
            selector: 'teamedit'
        },
        {
            ref: 'displayFilteredCount',
            selector: 'displayfield[action=displayFilteredCount]'
        }
    ],
    init: function () {
        this.control(
                {
                    'checkbox[action=filterPlayersWithoutPhoto]': {
                        change: this.filterPlayersWithoutPhoto
                    },
                    'checkbox[action=filterPlayersWithoutClub]': {
                        change: this.filterPlayersWithoutClub
                    },
                    'checkbox[action=filterInactivePlayers]': {
                        change: this.filterInactivePlayers
                    },
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
                    'usersgrid button[action=add]': {
                        click: this.addUser
                    },
                    'gymnasiumsgrid button[action=add]': {
                        click: this.addGymnasium
                    },
                    'clubsgrid button[action=add]': {
                        click: this.addClub
                    },
                    'teamsgrid button[action=add]': {
                        click: this.addTeam
                    },
                    'usersgrid button[action=edit]': {
                        click: this.editUser
                    },
                    'gymnasiumsgrid button[action=edit]': {
                        click: this.editGymnasium
                    },
                    'clubsgrid button[action=edit]': {
                        click: this.editClub
                    },
                    'teamsgrid button[action=edit]': {
                        click: this.editTeam
                    },
                    'usersgrid button[action=delete]': {
                        click: this.deleteUsers
                    },
                    'gymnasiumsgrid button[action=delete]': {
                        click: this.deleteGymnasiums
                    },
                    'clubsgrid button[action=delete]': {
                        click: this.deleteClubs
                    },
                    'teamsgrid button[action=delete]': {
                        click: this.deleteTeams
                    },
                    'playersgrid': {
                        itemdblclick: this.editPlayer
                    },
                    'profilesgrid': {
                        itemdblclick: this.editProfile
                    },
                    'usersgrid': {
                        itemdblclick: this.editUser
                    },
                    'gymnasiumsgrid': {
                        itemdblclick: this.editGymnasium
                    },
                    'clubsgrid': {
                        itemdblclick: this.editClub
                    },
                    'teamsgrid': {
                        itemdblclick: this.editTeam
                    },
                    'playeredit button[action=save]': {
                        click: this.updatePlayer
                    },
                    'profileedit button[action=save]': {
                        click: this.updateProfile
                    },
                    'useredit button[action=save]': {
                        click: this.updateUser
                    },
                    'gymnasiumedit button[action=save]': {
                        click: this.updateGymnasium
                    },
                    'clubedit button[action=save]': {
                        click: this.updateClub
                    },
                    'teamedit button[action=save]': {
                        click: this.updateTeam
                    },
                    'button[action=displayActivity]': {
                        click: this.showActivityGrid
                    },
                    'button[action=managePlayers]': {
                        click: this.showPlayersGrid
                    },
                    'button[action=manageProfiles]': {
                        click: this.showProfilesGrid
                    },
                    'button[action=manageUsers]': {
                        click: this.showUsersGrid
                    },
                    'button[action=manageGymnasiums]': {
                        click: this.showGymnasiumsGrid
                    },
                    'button[action=manageClubs]': {
                        click: this.showClubsGrid
                    },
                    'button[action=manageTeams]': {
                        click: this.showTeamsGrid
                    },
                    'button[action=displayWeekSchedule]': {
                        click: this.showWeekScheduleGrid
                    },
                    'button[action=showClubSelect]': {
                        click: this.showClubSelect
                    },
                    'button[action=showProfileSelect]': {
                        click: this.showProfileSelect
                    },
                    'button[action=showTeamSelect]': {
                        click: this.showTeamSelect
                    },
                    'clubselect button[action=save]': {
                        click: this.linkPlayerToClub
                    },
                    'profileselect button[action=save]': {
                        click: this.linkUsersToProfile
                    },
                    'teamselect button[action=save]': {
                        click: this.linkPlayerToTeam
                    },
                    'playersgrid > toolbar[dock=top] > textfield[fieldLabel=Recherche]': {
                        change: this.searchPlayer
                    },
                    'button[action=showCheckLicence]': {
                        click: this.showCheckLicence
                    },
                    'playersgrid button[action=delete]': {
                        click: this.deletePlayers
                    }
                }
        );
    },
    filterPlayersWithoutPhoto: function (checkbox, newValue) {
        var store = this.getPlayersStore();
        if (newValue !== true) {
            store.clearFilter();
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.clearFilter(true);
        store.filter(
                {
                    filterFn: function (item) {
                        var regExp = new RegExp('Missing', "i");
                        if (regExp.test(item.get('path_photo'))) {
                            return true;
                        }
                        return false;
                    }
                }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    filterPlayersWithoutClub: function (checkbox, newValue) {
        var store = this.getPlayersStore();
        if (newValue !== true) {
            store.clearFilter();
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.clearFilter(true);
        store.filter(
                {
                    filterFn: function (item) {
                        return item.get('id_club') === 0;
                    }
                }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    filterInactivePlayers: function (checkbox, newValue) {
        var store = this.getPlayersStore();
        if (newValue !== true) {
            store.clearFilter();
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.clearFilter(true);
        store.filter(
                {
                    filterFn: function (item) {
                        return ((item.get('est_actif') === false) && (item.get('teams_list').length > 0));
                    }
                }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    searchPlayer: function (textfield, searchText) {
        var searchTerms = searchText.split(',');
        var store = this.getPlayersStore();
        store.clearFilter(true);
        store.filter(
                {
                    filterFn: function (item) {
                        var queribleFields = ['nom', 'prenom', 'num_licence', 'club', 'teams_list'];
                        var found = false;
                        Ext.each(searchTerms, function (searchTerm) {
                            var regExp = new RegExp(searchTerm, "i");
                            Ext.each(queribleFields, function (queribleField) {
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
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    editPlayer: function () {
        var record = this.getManagePlayersGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('playeredit');
        this.getFormPanelEditPlayer().loadRecord(record);
        this.getFormPanelEditPlayer().down('textfield[name=prenom]').focus();
    },
    editProfile: function () {
        var record = this.getManageProfilesGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('profileedit');
        this.getFormPanelEditProfile().loadRecord(record);
    },
    editUser: function () {
        var record = this.getManageUsersGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('useredit');
        this.getFormPanelEditUser().loadRecord(record);
    },
    editGymnasium: function () {
        var record = this.getManageGymnasiumsGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('gymnasiumedit');
        this.getFormPanelEditGymnasium().loadRecord(record);
    },
    editClub: function () {
        var record = this.getManageClubsGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('clubedit');
        this.getFormPanelEditClub().loadRecord(record);
    },
    editTeam: function () {
        var record = this.getManageTeamsGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('teamedit');
        this.getFormPanelEditTeam().loadRecord(record);
    },
    addPlayer: function () {
        Ext.widget('playeredit');
        this.getFormPanelEditPlayer().down('textfield[name=prenom]').focus();
    },
    addProfile: function () {
        Ext.widget('profileedit');
    },
    addUser: function () {
        Ext.widget('useredit');
    },
    addGymnasium: function () {
        Ext.widget('gymnasiumedit');
    },
    addClub: function () {
        Ext.widget('clubedit');
    },
    addTeam: function () {
        Ext.widget('teamedit');
    },
    deleteUsers: function () {
        var me = this;
        var records = this.getManageUsersGrid().getSelectionModel().getSelection();
        if (!records) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteUsers.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function (response) {
                        me.getUsersStore().load();
                    }
                });
            }
        });
    },
    deleteGymnasiums: function () {
        var me = this;
        var records = this.getManageGymnasiumsGrid().getSelectionModel().getSelection();
        if (!records) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteGymnasiums.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function (response) {
                        me.getGymnasiumsStore().load();
                    }
                });
            }
        });
    },
    deleteClubs: function () {
        var me = this;
        var records = this.getManageClubsGrid().getSelectionModel().getSelection();
        if (!records) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteClubs.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function (response) {
                        me.getClubsStore().load();
                    }
                });
            }
        });
    },
    deleteTeams: function () {
        var me = this;
        var records = this.getManageTeamsGrid().getSelectionModel().getSelection();
        if (!records) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id_equipe'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteTeams.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function (response) {
                        me.getTeamsStore().load();
                    }
                });
            }
        });
    },
    deletePlayers: function () {
        var me = this;
        var records = this.getManagePlayersGrid().getSelectionModel().getSelection();
        if (!records) {
            return;
        }
        Ext.Msg.show({
            title: 'Supprimer?',
            msg: 'Etes-vous certain de vouloir supprimer ces lignes?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                var ids = [];
                Ext.each(records, function (record) {
                    ids.push(record.get('id'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deletePlayers.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function (response) {
                        me.getPlayersStore().load();
                    }
                });
            }
        });
    },
    updatePlayer: function () {
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
                    thisController.getWindowEditPlayer().close();
                    thisController.getManagePlayersGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateProfile: function () {
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
                success: function () {
                    thisController.getProfilesStore().load();
                    thisController.getWindowEditProfile().close();
                    thisController.getManageProfilesGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateUser: function () {
        var thisController = this;
        var form = this.getFormPanelEditUser().getForm();
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
                    thisController.getUsersStore().load();
                    thisController.getWindowEditUser().close();
                    thisController.getManageUsersGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateGymnasium: function () {
        var thisController = this;
        var form = this.getFormPanelEditGymnasium().getForm();
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
                    thisController.getGymnasiumsStore().load();
                    thisController.getWindowEditGymnasium().close();
                    thisController.getManageGymnasiumsGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateClub: function () {
        var thisController = this;
        var form = this.getFormPanelEditClub().getForm();
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
                    thisController.getClubsStore().load();
                    thisController.getWindowEditClub().close();
                    thisController.getManageClubsGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateTeam: function () {
        var thisController = this;
        var form = this.getFormPanelEditTeam().getForm();
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
                    thisController.getTeamsStore().load();
                    thisController.getWindowEditTeam().close();
                    thisController.getManageTeamsGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    showActivityGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'activitygrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showPlayersGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'playersgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showProfilesGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'profilesgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showUsersGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'usersgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showWeekScheduleGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'weekschedulegrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showGymnasiumsGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'gymnasiumsgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showClubsGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'clubsgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showTeamsGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'teamsgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showClubSelect: function () {
        var records = this.getManagePlayersGrid().getSelectionModel().getSelection();
        var idPlayers = [];
        Ext.each(records, function (record) {
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
    showCheckLicence: function () {
        var me = this;
        var records = this.getManagePlayersGrid().getSelectionModel().getSelection();
        if(records[0].get('est_actif')) {
            Ext.Msg.alert('Infos licence', 'Joueur actif');
            return;
        }
        var licence = '0' + records[0].get('departement_affiliation') + '_' + records[0].get('num_licence');
        
        Ext.Ajax.request({
            url: 'ajax/checkLicence.php',
            params: {
                licence_number: licence
            },
            success: function (response) {
                var el = document.createElement('div');
                el.innerHTML = response.responseText;
                var infos = el.getElementsByTagName('td');
                var displayMessage = "";
                Ext.each(infos, function (info, index) {
                    if (index === 6) {
                        return false;
                    }
                    displayMessage = displayMessage + info.innerHTML.trim() + ' ';
                });
                var resultMessage = displayMessage.trim();
                var dt = new Date();
                var currentYear = Ext.Date.format(dt, 'Y');
                dt = Ext.Date.add(dt, Ext.Date.YEAR, 1);
                var nextYear = Ext.Date.format(dt, 'Y');
                if (Ext.String.endsWith(resultMessage, currentYear) || Ext.String.endsWith(resultMessage, nextYear)) {
                    Ext.Msg.show({
                        title: 'Infos licence',
                        message: 'Trouvé : ' + displayMessage.trim() + ', Voulez vous activer le joueur ?',
                        buttons: Ext.Msg.YESNO,
                        icon: Ext.Msg.QUESTION,
                        fn: function (btn) {
                            if (btn !== 'yes') {
                                return;
                            }
                            var ids = [];
                            Ext.each(records, function (record) {
                                ids.push(record.get('id'));
                            });
                            Ext.Ajax.request({
                                url: 'ajax/activatePlayers.php',
                                params: {
                                    ids: ids.join(',')
                                },
                                success: function (response) {
                                    me.getPlayersStore().load();
                                }
                            });
                        }
                    });
                }
                else {
                    Ext.Msg.alert('Infos licence', displayMessage.trim());
                }

            }
        });
    },
    showProfileSelect: function () {
        var records = this.getManageUsersGrid().getSelectionModel().getSelection();
        var idUsers = [];
        Ext.each(records, function (record) {
            idUsers.push(record.get('id'));
        });
        if (idUsers.length === 0) {
            return;
        }
        Ext.widget('profileselect');
        this.getFormPanelSelectProfile().getForm().setValues({
            id_users: idUsers.join(',')
        });
    },
    showTeamSelect: function () {
        var records = this.getManagePlayersGrid().getSelectionModel().getSelection();
        var idPlayers = [];
        Ext.each(records, function (record) {
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
    linkPlayerToClub: function () {
        var thisController = this;
        var form = this.getFormPanelSelectClub().getForm();
        if (form.isValid()) {
            form.submit({
                success: function () {
                    thisController.getPlayersStore().load();
                    thisController.getWindowSelectClub().close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    linkUsersToProfile: function () {
        var thisController = this;
        var form = this.getFormPanelSelectProfile().getForm();
        if (form.isValid()) {
            form.submit({
                success: function () {
                    thisController.getUsersStore().load();
                    thisController.getWindowSelectProfile().close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    linkPlayerToTeam: function () {
        var thisController = this;
        var form = this.getFormPanelSelectTeam().getForm();
        if (form.isValid()) {
            form.submit({
                success: function () {
                    thisController.getPlayersStore().load();
                    thisController.getWindowSelectTeam().close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    }
});