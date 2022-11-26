Ext.define('Ufolep13Volley.controller.Administration', {
    extend: 'Ext.app.Controller',
    stores: [
        'Players',
        'Clubs',
        'Teams',
        'RankTeams',
        'Competitions',
        'ParentCompetitions',
        'Profiles',
        'Users',
        'Gymnasiums',
        'Activity',
        'WeekSchedule',
        'AdminMatches',
        'AdminDays',
        'LimitDates',
        'AdminRanks',
        'HallOfFame',
        'Timeslots',
        'BlacklistGymnase',
        'BlacklistTeam',
        'BlacklistTeams',
        'BlacklistDate'
    ],
    models: [
        'Player',
        'Club',
        'Team',
        'RankTeam',
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
        'Rank',
        'HallOfFame',
        'Timeslot',
        'BlacklistGymnase',
        'BlacklistTeam',
        'BlacklistTeams',
        'BlacklistDate'
    ],
    views: [
        'player.Grid',
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
        'rank.Edit',
        'grid.HallOfFame',
        'window.HallOfFame',
        'grid.Competitions',
        'window.Competition',
        'grid.BlacklistGymnase',
        'window.BlacklistGymnase',
        'grid.BlacklistTeam',
        'window.BlacklistTeam',
        'grid.BlacklistTeams',
        'window.BlacklistTeams',
        'grid.BlacklistDate',
        'window.BlacklistDate',
        'grid.Timeslots',
        'window.Timeslot'
    ],
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
            selector: 'tabpanel'
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
                'checkbox[action=filterPlayersEngaged]': {
                    change: this.filterPlayersEngaged
                },
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
                'competitions_grid button[action=generateHallOfFame]': {
                    click: this.generateHallOfFame
                },
                'competitions_grid button[action=resetCompetition]': {
                    click: this.resetCompetition
                },
                'competitions_grid button[action=generateDays]': {
                    click: this.generateDays
                },
                'competitions_grid button[action=generateMatches]': {
                    click: this.generateMatches
                },
                'competitions_grid button[action=generateAll]': {
                    click: this.generateAll
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
                'button[action=cancel]': {
                    click: this.cancel
                },
                'button[action=save]': {
                    click: this.save
                },
                'menuitem[action=displayActivity]': {
                    click: this.showActivityGrid
                },
                'menuitem[action=managePlayers]': {
                    click: this.showPlayersGrid
                },
                'menuitem[action=manageProfiles]': {
                    click: this.showProfilesGrid
                },
                'menuitem[action=manageUsers]': {
                    click: this.showUsersGrid
                },
                'menuitem[action=manageGymnasiums]': {
                    click: this.showGymnasiumsGrid
                },
                'menuitem[action=manageClubs]': {
                    click: this.showClubsGrid
                },
                'menuitem[action=manageTeams]': {
                    click: this.showTeamsGrid
                },
                'menuitem[action=manageMatches]': {
                    click: this.showMatchesGrid
                },
                'menuitem[action=manageRanks]': {
                    click: this.showRanksGrid
                },
                'menuitem[action=manageDays]': {
                    click: this.showDaysGrid
                },
                'menuitem[action=manageLimitDates]': {
                    click: this.showLimitDatesGrid
                },
                'menuitem[action=displayWeekSchedule]': {
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
                'playersgrid button[action=delete]': {
                    click: this.deletePlayers
                },
                'menuitem[action=displayIndicators]': {
                    click: this.displayIndicators
                },
                'grid > toolbar[dock=top] > textfield[fieldLabel=Recherche]': {
                    change: this.searchInGrid
                },
                'menuitem[action=displayHallOfFame]': {
                    click: this.displayHallOfFame
                },
                'hall_of_fame_grid': {
                    added: this.addToolbarHallOfFame
                },
                'button[action=addHallOfFame]': {
                    click: this.addHallOfFame
                },
                'button[action=editHallOfFame]': {
                    click: this.editHallOfFame
                },
                'button[action=deleteHallOfFame]': {
                    click: this.deleteHallOfFame
                },
                'menuitem[action=displayTimeslots]': {
                    click: this.displayTimeslots
                },
                'timeslots_grid': {
                    added: this.addToolbarTimeslots
                },
                'button[action=addTimeslot]': {
                    click: this.addTimeslot
                },
                'button[action=editTimeslot]': {
                    click: this.editTimeslot
                },
                'button[action=deleteTimeslot]': {
                    click: this.deleteTimeslot
                },
                'menuitem[action=displayCompetitions]': {
                    click: this.displayCompetitions
                },
                'competitions_grid': {
                    added: this.addToolbarCompetitions
                },
                'button[action=addCompetition]': {
                    click: this.addCompetition
                },
                'button[action=editCompetition]': {
                    click: this.editCompetition
                },
                'button[action=deleteCompetition]': {
                    click: this.deleteCompetition
                },
                'menuitem[action=displayBlacklistGymnase]': {
                    click: this.displayBlacklistGymnase
                },
                'blacklistgymnase_grid': {
                    added: this.addToolbarBlacklistGymnase
                },
                'button[action=addBlacklistGymnase]': {
                    click: this.addBlacklistGymnase
                },
                'button[action=editBlacklistGymnase]': {
                    click: this.editBlacklistGymnase
                },
                'button[action=deleteBlacklistGymnase]': {
                    click: this.deleteBlacklistGymnase
                },
                'menuitem[action=displayBlacklistTeam]': {
                    click: this.displayBlacklistTeam
                },
                'menuitem[action=displayBlacklistTeams]': {
                    click: this.displayBlacklistTeams
                },
                'blacklistteam_grid': {
                    added: this.addToolbarBlacklistTeam
                },
                'blacklistteams_grid': {
                    added: this.addToolbarBlacklistTeams
                },
                'button[action=addBlacklistTeam]': {
                    click: this.addBlacklistTeam
                },
                'button[action=editBlacklistTeam]': {
                    click: this.editBlacklistTeam
                },
                'button[action=deleteBlacklistTeam]': {
                    click: this.deleteBlacklistTeam
                },
                'button[action=addBlacklistTeams]': {
                    click: this.addBlacklistTeams
                },
                'button[action=editBlacklistTeams]': {
                    click: this.editBlacklistTeams
                },
                'button[action=deleteBlacklistTeams]': {
                    click: this.deleteBlacklistTeams
                },
                'menuitem[action=displayBlacklistDate]': {
                    click: this.displayBlacklistDate
                },
                'blacklistdate_grid': {
                    added: this.addToolbarBlacklistDate
                },
                'button[action=addBlacklistDate]': {
                    click: this.addBlacklistDate
                },
                'button[action=editBlacklistDate]': {
                    click: this.editBlacklistDate
                },
                'button[action=deleteBlacklistDate]': {
                    click: this.deleteBlacklistDate
                },
                'button[action=archiveMatch]': {
                    click: this.archiveMatch
                },
                'button[action=confirmMatch]': {
                    click: this.confirmMatch
                },
                'button[action=unconfirmMatch]': {
                    click: this.unconfirmMatch
                }
            }
        );
    },
    displayIndicators: function () {
        var mainPanel = this.getMainPanel();
        mainPanel.setAutoScroll(true);
        var tab = mainPanel.add({
            title: 'Indicateurs',
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            autoScroll: true,
            items: []
        });
        mainPanel.setActiveTab(tab);
        var storeIndicators = Ext.create('Ext.data.Store', {
            fields: [
                'fieldLabel',
                'value',
                'details'
            ],
            proxy: {
                type: 'rest',
                url: 'ajax/indicators.php',
                reader: {
                    type: 'json',
                    root: 'results'
                }
            }
        });
        storeIndicators.load({
            callback: function (records) {
                Ext.each(records, function (record) {
                    var detailsData = record.get('details');
                    if (!detailsData) {
                        return;
                    }
                    var fields = [];
                    var columns = [];
                    for (var k in detailsData[0]) {
                        fields.push(k);
                        columns.push({
                            header: k,
                            dataIndex: k,
                            flex: 1
                        });
                    }
                    var indicatorPanel = Ext.ComponentQuery.query('panel[title=Indicateurs]')[0];
                    if (record.get('value') === 0) {
                        return;
                    }
                    indicatorPanel.add(
                        {
                            layout: 'hbox',
                            items: [
                                {
                                    xtype: 'displayfield',
                                    margin: 10,
                                    fieldLabel: record.get('fieldLabel'),
                                    labelWidth: 250,
                                    value: record.get('value'),
                                    width: 300
                                },
                                {
                                    xtype: 'button',
                                    margin: 10,
                                    text: 'Détails',
                                    handler: function () {
                                        Ext.create('Ext.window.Window', {
                                            title: 'Détails',
                                            height: 500,
                                            width: 700,
                                            maximizable: true,
                                            layout: 'fit',
                                            items: {
                                                xtype: 'exportablegrid',
                                                viewConfig: {
                                                    enableTextSelection: true
                                                },
                                                autoScroll: true,
                                                store: Ext.create('Ext.data.Store', {
                                                    fields: fields,
                                                    data: {
                                                        'items': detailsData
                                                    },
                                                    proxy: {
                                                        type: 'memory',
                                                        reader: {
                                                            type: 'json',
                                                            root: 'items'
                                                        }
                                                    }
                                                }),
                                                columns: columns
                                            },
                                            dockedItems: [
                                                {
                                                    xtype: 'toolbar',
                                                    dock: 'bottom',
                                                    items: [
                                                        {
                                                            text: 'Télécharger',
                                                            handler: function (button) {
                                                                button.up('window').down('grid').export('Rapport');
                                                            }
                                                        }
                                                    ]
                                                }
                                            ]
                                        }).show();
                                    }
                                }
                            ]
                        });
                });
            }
        });
    },
    filterPlayersWithoutClub: function (checkbox, newValue) {
        var store = this.getPlayersStore();
        if (newValue !== true) {
            store.removeFilter('filterPlayersWithoutClub');
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.filter(
            {
                id: 'filterPlayersWithoutClub',
                filterFn: function (item) {
                    return (!Ext.isEmpty(item.get('teams_list'))) && (Ext.isEmpty(item.get('id_club')));
                }
            }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    filterPlayersWithoutLicence: function (checkbox, newValue) {
        var store = this.getPlayersStore();
        if (newValue !== true) {
            store.removeFilter('filterPlayersWithoutLicence');
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.filter(
            {
                id: 'filterPlayersWithoutLicence',
                filterFn: function (item) {
                    return (!Ext.isEmpty(item.get('teams_list'))) && (Ext.isEmpty(item.get('num_licence')));
                }
            }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    filterPlayersWith2TeamsSameCompetition: function (checkbox, newValue) {
        var store = this.getPlayersStore();
        if (newValue !== true) {
            store.removeFilter('filterPlayersWith2TeamsSameCompetition');
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.filter(
            {
                id: 'filterPlayersWith2TeamsSameCompetition',
                filterFn: function (item) {
                    if (item.get('teams_list') !== null) {
                        var countM = (item.get('teams_list').match(/\(m\)/g) || []).length;
                        var countF = (item.get('teams_list').match(/\(f\)/g) || []).length;
                        var countKH = (item.get('teams_list').match(/\(kh\)/g) || []).length;
                        var countC = (item.get('teams_list').match(/\(c\)/g) || []).length;
                        return ((countM > 1) || (countF > 1) || (countKH > 1) || (countC > 1));
                    }
                    return false;
                }
            }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    filterPlayersEngaged: function (checkbox, newValue) {
        var store = this.getPlayersStore();
        if (newValue !== true) {
            store.removeFilter('filterPlayersEngaged');
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.filter(
            {
                id: 'filterPlayersEngaged',
                filterFn: function (item) {
                    return !Ext.isEmpty(item.get('teams_list'));
                }
            }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    filterInactivePlayers: function (checkbox, newValue) {
        var store = this.getPlayersStore();
        if (newValue !== true) {
            store.removeFilter('filterInactivePlayers');
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.filter(
            {
                id: 'filterInactivePlayers',
                filterFn: function (item) {
                    return (!item.get('est_actif')) && (!Ext.isEmpty(item.get('teams_list')));
                }
            }
        );
        this.getDisplayFilteredCount().setValue(store.getCount());
    },
    searchInGrid: function (textfield, searchText) {
        var searchTerms = searchText.split(',');
        var store = textfield.up('grid').getStore();
        if (Ext.isEmpty(searchText)) {
            store.removeFilter('searchInGrid');
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        var model = store.first();
        if (!model) {
            this.getDisplayFilteredCount().setValue(store.getCount());
            return;
        }
        store.filter(
            {
                id: 'searchInGrid',
                filterFn: function (item) {
                    var fields = model.getFields();
                    var queribleFields = [];
                    Ext.each(fields, function (field) {
                        if (field.getType() === 'string' || field.getType() === 'auto') {
                            Ext.Array.push(queribleFields, field.getName());
                        }
                    });
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
        var record = this.getManageMatchesGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        this.getFormPanelEditMatch().loadRecord(record);
        this.getFormPanelEditMatch().getForm().findField('id_match').setValue("");
        this.getFormPanelEditMatch().getForm().findField('code_match').setValue("");
    },
    addRank: function (button) {
        var widget = Ext.widget('rankedit');
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        widget.down('form').loadRecord(record);
        widget.down('form').getForm().findField('id').setValue("");
    },
    addDay: function () {
        Ext.widget('dayedit');
    },
    addLimitDate: function () {
        Ext.widget('limitdateedit');
    },
    addHallOfFame: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        var windowEdit = Ext.widget('hall_of_fame_edit');
        if (!record) {
            return;
        }
        record.set('id', null);
        windowEdit.down('form').loadRecord(record);
    },
    editHallOfFame: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var windowEdit = Ext.widget('hall_of_fame_edit');
        windowEdit.down('form').loadRecord(record);
    },
    deleteHallOfFame: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
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
                    url: '/rest/action.php/halloffame/deleteHallOfFame',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        grid.getStore().load();
                    }
                });
            }
        });
    },
    addTimeslot: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        var windowEdit = Ext.widget('timeslot_edit');
        if (!record) {
            return;
        }
        record.set('id', null);
        windowEdit.down('form').loadRecord(record);
    },
    editTimeslot: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var windowEdit = Ext.widget('timeslot_edit');
        windowEdit.down('form').loadRecord(record);
    },
    deleteTimeslot: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
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
                    url: '/rest/action.php/timeslot/deleteTimeslot',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        grid.getStore().load();
                    }
                });
            }
        });
    },
    addCompetition: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        var windowEdit = Ext.widget('competition_edit');
        if (!record) {
            return;
        }
        record.set('id', null);
        windowEdit.down('form').loadRecord(record);
    },
    editCompetition: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var windowEdit = Ext.widget('competition_edit');
        windowEdit.down('form').loadRecord(record);
    },
    deleteCompetition: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
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
                    url: '/rest/action.php/competition/deleteCompetition',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        grid.getStore().load();
                    }
                });
            }
        });
    },
    addBlacklistGymnase: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        var windowEdit = Ext.widget('blacklistgymnase_edit');
        if (!record) {
            return;
        }
        record.set('id', null);
        windowEdit.down('form').loadRecord(record);
    },
    editBlacklistGymnase: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var windowEdit = Ext.widget('blacklistgymnase_edit');
        windowEdit.down('form').loadRecord(record);
    },
    deleteBlacklistGymnase: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
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
                    url: '/rest/action.php/blacklistcourt/deleteBlacklistGymnase',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        grid.getStore().load();
                    }
                });
            }
        });
    },
    addBlacklistTeam: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        var windowEdit = Ext.widget('blacklistteam_edit');
        if (!record) {
            return;
        }
        record.set('id', null);
        windowEdit.down('form').loadRecord(record);
    },
    editBlacklistTeam: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var windowEdit = Ext.widget('blacklistteam_edit');
        windowEdit.down('form').loadRecord(record);
    },
    deleteBlacklistTeam: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
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
                    url: '/rest/action.php/blacklistteam/deleteBlacklistTeam',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        grid.getStore().load();
                    }
                });
            }
        });
    },
    addBlacklistTeams: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        var windowEdit = Ext.widget('blacklistteams_edit');
        if (!record) {
            return;
        }
        record.set('id', null);
        windowEdit.down('form').loadRecord(record);
    },
    editBlacklistTeams: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var windowEdit = Ext.widget('blacklistteams_edit');
        windowEdit.down('form').loadRecord(record);
    },
    deleteBlacklistTeams: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
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
                    url: '/rest/action.php/blacklistteams/delete',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        grid.getStore().load();
                    }
                });
            }
        });
    },
    addBlacklistDate: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        var windowEdit = Ext.widget('blacklistdate_edit');
        if (!record) {
            return;
        }
        record.set('id', null);
        windowEdit.down('form').loadRecord(record);
    },
    editBlacklistDate: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var windowEdit = Ext.widget('blacklistdate_edit');
        windowEdit.down('form').loadRecord(record);
    },
    deleteBlacklistDate: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
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
                    url: '/rest/action.php/blacklistdate/deleteBlacklistDate',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        grid.getStore().load();
                    }
                });
            }
        });
    },
    deleteUsers: function () {
        var me = this;
        var records = this.getManageUsersGrid().getSelectionModel().getSelection();
        if (records.length === 0) {
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
                    url: '/rest/action.php/usermanager/deleteUsers',
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
        if (records.length === 0) {
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
                    url: '/rest/action.php/court/deleteGymnasiums',
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
        if (records.length === 0) {
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
                    url: '/rest/action.php/club/deleteClubs',
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
        if (records.length === 0) {
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
                    url: '/rest/action.php/team/delete',
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
        if (records.length === 0) {
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
                    url: '/rest/action.php/matchmgr/delete',
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
        if (records.length === 0) {
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
                    url: '/rest/action.php/rank/delete',
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
    genericRequest: function (button, title, url, is_one_record_allowed) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
            return;
        }
        if (records.length > 1 && is_one_record_allowed === true) {
            Ext.Msg.alert('Erreur', "Cette action n'est utilisable que pour une seule entrée !");
            return;
        }
        Ext.Msg.show({
            title: title,
            msg: 'Etes-vous certain de vouloir effectuer cette action ?',
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
                    url: url,
                    params: {
                        ids: ids.join(',')
                    },
                    timeout: 600000,
                    success: function () {
                        Ext.Msg.alert('Succès', "L'opération a été réalisée avec succès.");
                        grid.getStore().load();
                    },
                    failure: function (response) {
                        if (response.status === '404') {
                            Ext.Msg.alert('Erreur', "La page n'a pas été trouvée !");
                            return;
                        }
                        var response_json = Ext.decode(response.responseText);
                        Ext.create('Ext.window.Window', {
                            title: 'Erreur (copiable)',
                            height: 500,
                            width: 700,
                            maximizable: true,
                            layout: 'fit',
                            items: {
                                xtype: 'textarea',
                                value: response_json.message
                            }
                        }).show();
                    }
                });
            }
        });
    },
    generateHallOfFame: function (button) {
        this.genericRequest(button, 'Générer le palmarès', '/rest/action.php/halloffame/generateHallOfFame');
    },
    resetCompetition: function (button) {
        this.genericRequest(button, 'Reset compétition', '/rest/action.php/competition/resetCompetition');
    },
    generateDays: function (button) {
        this.genericRequest(button, 'Générer les journées', '/rest/action.php/day/generateDays');
    },
    generateMatches: function (button) {
        this.genericRequest(button, 'Générer les matches', '/rest/action.php/matchmgr/generateMatches', true);
    },
    generateAll: function (button) {
        this.genericRequest(button, 'Générer tout', '/rest/action.php/matchmgr/generateAll', true);
    },
    deleteDays: function () {
        var me = this;
        var records = this.getManageDaysGrid().getSelectionModel().getSelection();
        if (records.length === 0) {
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
                    url: '/rest/action.php/day/deleteDays',
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
        if (records.length === 0) {
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
                    url: '/rest/action.php/limitdate/deleteLimitDates',
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
        if (records.length === 0) {
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
                    url: '/rest/action.php/player/delete_players',
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
    cancel: function (button) {
        if (!Ext.isEmpty(button.up('window'))) {
            button.up('window').close();
            return;
        }
    },
    save: function (button) {
        var viewport = Ext.ComponentQuery.query('viewport')[0];
        var form = button.up('form').getForm();
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
                    if (viewport.down('tabpanel')) {
                        viewport.down('tabpanel').getActiveTab().getStore().load();
                        button.up('window').close();
                        return;
                    }
                    window.close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    showAdministrationGrid: function (xtype_name) {
        if (Ext.ComponentQuery.query(xtype_name).length > 0) {
            return;
        }
        var tab = this.getMainPanel().add({
            xtype: xtype_name
        });
        this.getMainPanel().setActiveTab(tab);
    },
    showActivityGrid: function () {
        this.showAdministrationGrid('activitygrid');
    },
    showPlayersGrid: function () {
        this.showAdministrationGrid('playersgrid');
    },
    showProfilesGrid: function () {
        this.showAdministrationGrid('profilesgrid');
    },
    showUsersGrid: function () {
        this.showAdministrationGrid('usersgrid');
    },
    showWeekScheduleGrid: function () {
        this.showAdministrationGrid('weekschedulegrid');
    },
    showGymnasiumsGrid: function () {
        this.showAdministrationGrid('gymnasiumsgrid');
    },
    showClubsGrid: function () {
        this.showAdministrationGrid('clubsgrid');
    },
    showTeamsGrid: function () {
        this.showAdministrationGrid('teamsgrid');
    },
    showMatchesGrid: function () {
        this.showAdministrationGrid('matchesgrid');
    },
    showRanksGrid: function () {
        this.showAdministrationGrid('rankgrid');
    },
    showDaysGrid: function () {
        this.showAdministrationGrid('daysgrid');
    },
    showLimitDatesGrid: function () {
        this.showAdministrationGrid('limitdatesgrid');
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
    displayHallOfFame: function () {
        this.showAdministrationGrid('hall_of_fame_grid');
    },
    addToolbarHallOfFame: function (grid) {
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'ACTIONS',
                {
                    xtype: 'tbseparator'
                },
                {
                    text: 'Ajouter',
                    action: 'addHallOfFame'
                },
                {
                    text: 'Modifier',
                    action: 'editHallOfFame'
                },
                {
                    text: 'Supprimer',
                    action: 'deleteHallOfFame'
                }
            ]
        });
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'FILTRES',
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Total',
                    action: 'displayFilteredCount'
                }
            ]
        });
    },
    displayTimeslots: function () {
        this.showAdministrationGrid('timeslots_grid');
    },
    addToolbarTimeslots: function (grid) {
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'ACTIONS',
                {
                    xtype: 'tbseparator'
                },
                {
                    text: 'Ajouter',
                    action: 'addTimeslot'
                },
                {
                    text: 'Modifier',
                    action: 'editTimeslot'
                },
                {
                    text: 'Supprimer',
                    action: 'deleteTimeslot'
                }
            ]
        });
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'FILTRES',
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Total',
                    action: 'displayFilteredCount'
                }
            ]
        });
    },
    displayCompetitions: function () {
        this.showAdministrationGrid('competitions_grid');
    },
    addToolbarCompetitions: function (grid) {
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'ACTIONS',
                {
                    xtype: 'tbseparator'
                },
                {
                    text: 'Ajouter',
                    action: 'addCompetition'
                },
                {
                    text: 'Modifier',
                    action: 'editCompetition'
                },
                {
                    text: 'Supprimer',
                    action: 'deleteCompetition'
                },
                {
                    text: 'Générer le palmarès...',
                    action: 'generateHallOfFame'
                },
                {
                    text: 'Reset compétition...',
                    action: 'resetCompetition'
                },
                {
                    text: 'Générer les journées...',
                    action: 'generateDays'
                },
                {
                    text: 'Générer les matches...',
                    action: 'generateMatches'
                },
                {
                    text: "Générer tout d'un coup",
                    action: 'generateAll'
                }
            ]
        });
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'FILTRES',
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Total',
                    action: 'displayFilteredCount'
                }
            ]
        });
    },
    displayBlacklistGymnase: function () {
        this.showAdministrationGrid('blacklistgymnase_grid');
    },
    addToolbarBlacklistGymnase: function (grid) {
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'ACTIONS',
                {
                    xtype: 'tbseparator'
                },
                {
                    text: 'Ajouter',
                    action: 'addBlacklistGymnase'
                },
                {
                    text: 'Modifier',
                    action: 'editBlacklistGymnase'
                },
                {
                    text: 'Supprimer',
                    action: 'deleteBlacklistGymnase'
                }
            ]
        });
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'FILTRES',
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Total',
                    action: 'displayFilteredCount'
                }
            ]
        });
    },
    displayBlacklistTeam: function () {
        this.showAdministrationGrid('blacklistteam_grid');
    },
    displayBlacklistTeams: function () {
        this.showAdministrationGrid('blacklistteams_grid');
    },
    addToolbarBlacklistTeam: function (grid) {
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'ACTIONS',
                {
                    xtype: 'tbseparator'
                },
                {
                    text: 'Ajouter',
                    action: 'addBlacklistTeam'
                },
                {
                    text: 'Modifier',
                    action: 'editBlacklistTeam'
                },
                {
                    text: 'Supprimer',
                    action: 'deleteBlacklistTeam'
                }
            ]
        });
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'FILTRES',
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Total',
                    action: 'displayFilteredCount'
                }
            ]
        });
    },
    addToolbarBlacklistTeams: function (grid) {
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'ACTIONS',
                {
                    xtype: 'tbseparator'
                },
                {
                    text: 'Ajouter',
                    action: 'addBlacklistTeams'
                },
                {
                    text: 'Modifier',
                    action: 'editBlacklistTeams'
                },
                {
                    text: 'Supprimer',
                    action: 'deleteBlacklistTeams'
                }
            ]
        });
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'FILTRES',
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Total',
                    action: 'displayFilteredCount'
                }
            ]
        });
    },
    displayBlacklistDate: function () {
        this.showAdministrationGrid('blacklistdate_grid');
    },
    addToolbarBlacklistDate: function (grid) {
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'ACTIONS',
                {
                    xtype: 'tbseparator'
                },
                {
                    text: 'Ajouter',
                    action: 'addBlacklistDate'
                },
                {
                    text: 'Modifier',
                    action: 'editBlacklistDate'
                },
                {
                    text: 'Supprimer',
                    action: 'deleteBlacklistDate'
                }
            ]
        });
        grid.addDocked({
            xtype: 'toolbar',
            dock: 'top',
            items: [
                'FILTRES',
                {
                    xtype: 'tbseparator'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: 'Recherche'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Total',
                    action: 'displayFilteredCount'
                }
            ]
        });
    },
    archiveMatch: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Archiver?',
            msg: 'Etes-vous certain de vouloir archiver ces matchs ?',
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
                    url: '/rest/action.php/matchmgr/archiveMatch',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        grid.getStore().load();
                    }
                });
            }
        });
    },
    confirmMatch: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Archiver?',
            msg: 'Etes-vous certain de vouloir confirmer ces matchs ?',
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
                    url: '/rest/action.php/matchmgr/confirmMatch',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        grid.getStore().load();
                    }
                });
            }
        });
    },
    unconfirmMatch: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length === 0) {
            return;
        }
        Ext.Msg.show({
            title: 'Archiver?',
            msg: 'Etes-vous certain de vouloir infirmer ces matchs ?',
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
                    url: '/rest/action.php/matchmgr/unconfirmMatch',
                    params: {
                        ids: ids.join(',')
                    },
                    success: function () {
                        grid.getStore().load();
                    }
                });
            }
        });
    }
});