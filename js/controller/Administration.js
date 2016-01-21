Ext.define('Ufolep13Volley.controller.Administration', {
    extend: 'Ext.app.Controller',
    stores: ['Players',
        'Clubs',
        'Teams',
        'Competitions',
        'Profiles',
        'Users',
        'Gymnasiums',
        'Activity',
        'WeekSchedule',
        'AdminMatches',
        'AdminDays',
        'LimitDates',
        'AdminRanks'],
    models: ['Player',
        'Club',
        'Team',
        'Competition',
        'Profile',
        'User',
        'Gymnasium',
        'Activity',
        'WeekSchedule',
        'Match',
        'WeekDay',
        'Day',
        'LimitDate',
        'Rank'
    ],
    views: ['player.Grid',
        'player.Edit',
        'club.Select',
        'team.Select',
        'team.Grid',
        'team.Edit',
        'match.AdminGrid',
        'match.Edit',
        'day.AdminGrid',
        'day.Edit',
        'limitdate.Grid',
        'limitdate.Edit',
        'profile.Grid',
        'profile.Edit',
        'profile.Select',
        'user.Grid',
        'user.Edit',
        'gymnasium.Grid',
        'gymnasium.Edit',
        'club.Grid',
        'club.Edit',
        'activity.Grid',
        'timeslot.WeekScheduleGrid',
        'rank.AdminGrid',
        'rank.Edit'],
    refs: [
        {
            ref: 'ImagePlayer',
            selector: 'playeredit image'
        },
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
            ref: 'manageMatchesGrid',
            selector: 'matchesgrid'
        },
        {
            ref: 'manageRanksGrid',
            selector: 'rankgrid'
        },
        {
            ref: 'manageDaysGrid',
            selector: 'daysgrid'
        },
        {
            ref: 'manageLimitDatesGrid',
            selector: 'limitdatesgrid'
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
            ref: 'formPanelEditMatch',
            selector: 'matchedit form'
        },
        {
            ref: 'formPanelEditRank',
            selector: 'rankedit form'
        },
        {
            ref: 'formPanelEditDay',
            selector: 'dayedit form'
        },
        {
            ref: 'formPanelEditLimitDate',
            selector: 'limitdateedit form'
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
            ref: 'windowEditMatch',
            selector: 'matchedit'
        },
        {
            ref: 'windowEditRank',
            selector: 'rankedit'
        },
        {
            ref: 'windowEditDay',
            selector: 'dayedit'
        },
        {
            ref: 'windowEditLimitDate',
            selector: 'limitdateedit'
        },
        {
            ref: 'displayFilteredCount',
            selector: 'displayfield[action=displayFilteredCount]'
        }
    ],
    init: function () {
        this.control(
            {
                'checkbox[action=filterPlayersWith2TeamsSameCompetition]': {
                    change: this.filterPlayersWith2TeamsSameCompetition
                },
                'checkbox[action=filterPlayersWithoutLicence]': {
                    change: this.filterPlayersWithoutLicence
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
                'matchesgrid button[action=add]': {
                    click: this.addMatch
                },
                'rankgrid button[action=add]': {
                    click: this.addRank
                },
                'daysgrid button[action=add]': {
                    click: this.addDay
                },
                'limitdatesgrid button[action=add]': {
                    click: this.addLimitDate
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
                'matchesgrid button[action=edit]': {
                    click: this.editMatch
                },
                'daysgrid button[action=edit]': {
                    click: this.editDay
                },
                'limitdatesgrid button[action=edit]': {
                    click: this.editLimitDate
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
                'matchesgrid button[action=delete]': {
                    click: this.deleteMatches
                },
                'rankgrid button[action=delete]': {
                    click: this.deleteRanks
                },
                'daysgrid button[action=delete]': {
                    click: this.deleteDays
                },
                'limitdatesgrid button[action=delete]': {
                    click: this.deleteLimitDates
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
                'matchesgrid': {
                    itemdblclick: this.editMatch
                },
                'rankgrid': {
                    itemdblclick: this.editRank
                },
                'daysgrid': {
                    itemdblclick: this.editDay
                },
                'limitdatesgrid': {
                    itemdblclick: this.editLimitDate
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
                'matchedit button[action=save]': {
                    click: this.updateMatch
                },
                'rankedit button[action=save]': {
                    click: this.updateRank
                },
                'dayedit button[action=save]': {
                    click: this.updateDay
                },
                'limitdateedit button[action=save]': {
                    click: this.updateLimitDate
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
                'button[action=manageMatches]': {
                    click: this.showMatchesGrid
                },
                'button[action=manageRanks]': {
                    click: this.showRanksGrid
                },
                'button[action=manageDays]': {
                    click: this.showDaysGrid
                },
                'button[action=manageLimitDates]': {
                    click: this.showLimitDatesGrid
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
                    return ((item.get('teams_list').length > 0) && (item.get('id_club') === 0));
                }
            }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    filterPlayersWithoutLicence: function (checkbox, newValue) {
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
                    return ((item.get('teams_list').length > 0) && (item.get('num_licence').length === 0));
                }
            }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    filterPlayersWith2TeamsSameCompetition: function (checkbox, newValue) {
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
                    var countM = (item.get('teams_list').match(/\(m\)/g) || []).length;
                    var countF = (item.get('teams_list').match(/\(f\)/g) || []).length;
                    var countKH = (item.get('teams_list').match(/\(kh\)/g) || []).length;
                    var countC = (item.get('teams_list').match(/\(c\)/g) || []).length;

                    return ((countM > 1) || (countF > 1) || (countKH > 1) || (countC > 1));
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
                    return ((item.get('num_licence').length > 0) && (item.get('est_actif') === false) && (item.get('teams_list').length > 0));
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
                        return !found;
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
        this.getImagePlayer().show();
        this.getImagePlayer().setSrc(record.get('path_photo'));
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
    editMatch: function () {
        var record = this.getManageMatchesGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('matchedit');
        this.getFormPanelEditMatch().loadRecord(record);
    },
    editRank: function () {
        var record = this.getManageRanksGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('rankedit');
        this.getFormPanelEditRank().loadRecord(record);
    },
    editDay: function () {
        var record = this.getManageDaysGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('dayedit');
        this.getFormPanelEditDay().loadRecord(record);
    },
    editLimitDate: function () {
        var record = this.getManageLimitDatesGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        Ext.widget('limitdateedit');
        this.getFormPanelEditLimitDate().loadRecord(record);
    },
    addPlayer: function () {
        Ext.widget('playeredit');
        this.getImagePlayer().hide();
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
    addMatch: function () {
        Ext.widget('matchedit');
    },
    addRank: function () {
        Ext.widget('rankedit');
    },
    addDay: function () {
        Ext.widget('dayedit');
    },
    addLimitDate: function () {
        Ext.widget('limitdateedit');
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
                    success: function () {
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
                    success: function () {
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
                    success: function () {
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
                    success: function () {
                        me.getTeamsStore().load();
                    }
                });
            }
        });
    },
    deleteMatches: function () {
        var me = this;
        var records = this.getManageMatchesGrid().getSelectionModel().getSelection();
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
                    ids.push(record.get('id_match'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteMatches.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getAdminMatchesStore().load();
                    }
                });
            }
        });
    },
    deleteRanks: function () {
        var me = this;
        var records = this.getManageRanksGrid().getSelectionModel().getSelection();
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
                    url: 'ajax/deleteRanks.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getAdminRanksStore().load();
                    }
                });
            }
        });
    },
    deleteDays: function () {
        var me = this;
        var records = this.getManageDaysGrid().getSelectionModel().getSelection();
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
                    url: 'ajax/deleteDays.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getAdminDaysStore().load();
                    }
                });
            }
        });
    },
    deleteLimitDates: function () {
        var me = this;
        var records = this.getManageLimitDatesGrid().getSelectionModel().getSelection();
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
                    ids.push(record.get('id_date'));
                });
                Ext.Ajax.request({
                    url: 'ajax/deleteLimitDates.php',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        me.getLimitDatesStore().load();
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
                    success: function () {
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
    updateMatch: function () {
        var thisController = this;
        var form = this.getFormPanelEditMatch().getForm();
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
                    thisController.getAdminMatchesStore().load();
                    thisController.getWindowEditMatch().close();
                    thisController.getManageMatchesGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateRank: function () {
        var thisController = this;
        var form = this.getFormPanelEditRank().getForm();
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
                    thisController.getAdminRanksStore().load();
                    thisController.getWindowEditRank().close();
                    thisController.getManageRanksGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateDay: function () {
        var thisController = this;
        var form = this.getFormPanelEditDay().getForm();
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
                    thisController.getAdminDaysStore().load();
                    thisController.getWindowEditDay().close();
                    thisController.getManageDaysGrid().getSelectionModel().deselectAll();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    updateLimitDate: function () {
        var thisController = this;
        var form = this.getFormPanelEditLimitDate().getForm();
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
                    thisController.getLimitDatesStore().load();
                    thisController.getWindowEditLimitDate().close();
                    thisController.getManageLimitDatesGrid().getSelectionModel().deselectAll();
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
    showMatchesGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'matchesgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showRanksGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'rankgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showDaysGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'daysgrid'
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showLimitDatesGrid: function () {
        var tab = this.getMainPanel().add({
            xtype: 'limitdatesgrid'
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
        if (records[0].get('est_actif')) {
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
                                success: function () {
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