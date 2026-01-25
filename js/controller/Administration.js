Ext.define('Ufolep13Volley.controller.Administration', {
    extend: 'Ext.app.Controller',
    stores: ['Players', 'Clubs', 'Teams', 'RankTeams', 'Competitions', 'ParentCompetitions', 'Profiles', 'Users', 'Gymnasiums', 'Activity', 'WeekSchedule', 'AdminMatches', 'AdminDays', 'LimitDates', 'AdminRanks', 'HallOfFame', 'Timeslots', 'BlacklistGymnase', 'BlacklistTeam', 'BlacklistTeams', 'BlacklistDate', 'Departements', 'AdminNews'],
    models: ['Player', 'Club', 'Team', 'RankTeam', 'Competition', 'Profile', 'User', 'Gymnasium', 'Activity', 'WeekSchedule', 'Match', 'WeekDay', 'Day', 'LimitDate', 'Rank', 'HallOfFame', 'Timeslot', 'BlacklistGymnase', 'BlacklistTeam', 'BlacklistTeams', 'BlacklistDate', 'News'],
    views: ['player.Grid', 'player.Edit', 'club.Select', 'team.Select', 'team.Grid', 'team.Edit', 'match.AdminGrid', 'match.Edit', 'day.AdminGrid', 'day.Edit', 'limitdate.Grid', 'limitdate.Edit', 'profile.Grid', 'profile.Edit', 'profile.Select', 'user.Grid', 'user.Edit', 'gymnasium.Grid', 'gymnasium.Edit', 'club.Grid', 'club.Edit', 'activity.Grid', 'timeslot.WeekScheduleGrid', 'rank.AdminGrid', 'rank.Edit', 'rank.DragDropPanel', 'grid.HallOfFame', 'window.HallOfFame', 'grid.Competitions', 'window.Competition', 'grid.BlacklistGymnase', 'window.BlacklistGymnase', 'grid.BlacklistTeam', 'window.BlacklistTeam', 'grid.BlacklistTeams', 'window.BlacklistTeams', 'grid.BlacklistDate', 'window.BlacklistDate', 'grid.Timeslots', 'window.Timeslot', 'view.Indicators', 'news.AdminGrid', 'news.Edit'],
    refs: [{
        ref: 'ImagePlayer', selector: 'playeredit image'
    }, {
        ref: 'managePlayersGrid', selector: 'playersgrid'
    }, {
        ref: 'manageProfilesGrid', selector: 'profilesgrid'
    }, {
        ref: 'manageUsersGrid', selector: 'usersgrid'
    }, {
        ref: 'manageGymnasiumsGrid', selector: 'gymnasiumsgrid'
    }, {
        ref: 'manageClubsGrid', selector: 'clubsgrid'
    }, {
        ref: 'manageTeamsGrid', selector: 'teamsgrid'
    }, {
        ref: 'manageMatchesGrid', selector: 'matchesgrid'
    }, {
        ref: 'manageRanksGrid', selector: 'rankgrid'
    }, {
        ref: 'manageDaysGrid', selector: 'daysgrid'
    }, {
        ref: 'manageLimitDatesGrid', selector: 'limitdatesgrid'
    }, {
        ref: 'mainPanel', selector: 'tabpanel'
    }, {
        ref: 'formPanelSelectClub', selector: 'clubselect form'
    }, {
        ref: 'formPanelSelectProfile', selector: 'profileselect form'
    }, {
        ref: 'formPanelSelectTeam', selector: 'teamselect form'
    }, {
        ref: 'formPanelEditPlayer', selector: 'playeredit form'
    }, {
        ref: 'formPanelEditProfile', selector: 'profileedit form'
    }, {
        ref: 'formPanelEditUser', selector: 'useredit form'
    }, {
        ref: 'formPanelEditGymnasium', selector: 'gymnasiumedit form'
    }, {
        ref: 'formPanelEditClub', selector: 'clubedit form'
    }, {
        ref: 'formPanelEditTeam', selector: 'teamedit form'
    }, {
        ref: 'formPanelEditMatch', selector: 'matchedit form'
    }, {
        ref: 'formPanelEditRank', selector: 'rankedit form'
    }, {
        ref: 'formPanelEditDay', selector: 'dayedit form'
    }, {
        ref: 'formPanelEditLimitDate', selector: 'limitdateedit form'
    }, {
        ref: 'windowSelectClub', selector: 'clubselect'
    }, {
        ref: 'windowSelectProfile', selector: 'profileselect'
    }, {
        ref: 'windowSelectTeam', selector: 'teamselect'
    }, {
        ref: 'windowEditPlayer', selector: 'playeredit'
    }, {
        ref: 'windowEditProfile', selector: 'profileedit'
    }, {
        ref: 'windowEditUser', selector: 'useredit'
    }, {
        ref: 'windowEditGymnasium', selector: 'gymnasiumedit'
    }, {
        ref: 'windowEditClub', selector: 'clubedit'
    }, {
        ref: 'windowEditTeam', selector: 'teamedit'
    }, {
        ref: 'windowEditMatch', selector: 'matchedit'
    }, {
        ref: 'windowEditRank', selector: 'rankedit'
    }, {
        ref: 'windowEditDay', selector: 'dayedit'
    }, {
        ref: 'windowEditLimitDate', selector: 'limitdateedit'
    }, {
        ref: 'formPanelEditNews', selector: 'newsedit form'
    }, {
        ref: 'windowEditNews', selector: 'newsedit'
    }, {
        ref: 'manageNewsGrid', selector: 'newsgrid'
    }],
    init: function () {
        this.control({
            'checkbox[action=filterPlayersEngaged]': {
                change: this.filterPlayersEngaged
            }, 'checkbox[action=filterPlayersWith2TeamsSameCompetition]': {
                change: this.filterPlayersWith2TeamsSameCompetition
            }, 'checkbox[action=filterPlayersWithoutLicence]': {
                change: this.filterPlayersWithoutLicence
            }, 'checkbox[action=filterPlayersWithoutClub]': {
                change: this.filterPlayersWithoutClub
            }, 'checkbox[action=filterInactivePlayers]': {
                change: this.filterInactivePlayers
            }, 'button[action=addPlayer]': {
                click: this.addPlayer
            }, 'button[action=editPlayer]': {
                click: this.editPlayer
            }, 'button[action=display_import_licence_file]': {
                click: this.display_import_licence_file
            }, 'button[action=addProfile]': {
                click: this.addProfile
            }, 'button[action=editProfile]': {
                click: this.editProfile
            }, 'usersgrid button[action=add]': {
                click: this.addUser
            }, 'gymnasiumsgrid button[action=add]': {
                click: this.addGymnasium
            }, 'clubsgrid button[action=add]': {
                click: this.addClub
            }, 'teamsgrid button[action=add]': {
                click: this.addTeam
            }, 'matchesgrid button[action=add]': {
                click: this.addMatch
            }, 'rankgrid button[action=add]': {
                click: this.addRank
            }, 'daysgrid button[action=add]': {
                click: this.addDay
            }, 'limitdatesgrid button[action=add]': {
                click: this.addLimitDate
            }, 'usersgrid button[action=edit]': {
                click: this.editUser
            }, 'gymnasiumsgrid button[action=edit]': {
                click: this.editGymnasium
            }, 'clubsgrid button[action=edit]': {
                click: this.editClub
            }, 'teamsgrid button[action=edit]': {
                click: this.editTeam
            }, 'matchesgrid button[action=edit]': {
                click: this.editMatch
            }, 'daysgrid button[action=edit]': {
                click: this.editDay
            }, 'limitdatesgrid button[action=edit]': {
                click: this.editLimitDate
            }, 'usersgrid button[action=delete]': {
                click: this.deleteUsers
            }, 'usersgrid button[action=reset_password]': {
                click: this.reset_password
            }, 'gymnasiumsgrid button[action=delete]': {
                click: this.deleteGymnasiums
            }, 'clubsgrid button[action=delete]': {
                click: this.deleteClubs
            }, 'teamsgrid button[action=delete]': {
                click: this.deleteTeams
            }, 'teamsgrid button[action=setLeader]': {
                click: this.setTeamLeader
            }, 'matchesgrid button[action=delete]': {
                click: this.deleteMatches
            }, 'rankgrid button[action=delete]': {
                click: this.deleteRanks
            }, 'competitions_grid menuitem[action=generateHallOfFame]': {
                click: this.generateHallOfFame
            }, 'competitions_grid menuitem[action=resetCompetition]': {
                click: this.resetCompetition
            }, 'competitions_grid menuitem[action=generateDays]': {
                click: this.generateDays
            }, 'competitions_grid menuitem[action=generateMatches]': {
                click: this.generateMatches
            }, 'competitions_grid menuitem[action=generate_matches_final_phase_cup_8]': {
                click: this.generate_matches_final_phase_cup_8
            }, 'competitions_grid menuitem[action=generate_matches_final_phase_cup_4]': {
                click: this.generate_matches_final_phase_cup_4
            }, 'competitions_grid menuitem[action=generate_matches_final_phase_cup_2]': {
                click: this.generate_matches_final_phase_cup_2
            }, 'daysgrid button[action=delete]': {
                click: this.deleteDays
            }, 'limitdatesgrid button[action=delete]': {
                click: this.deleteLimitDates
            }, 'playersgrid': {
                itemdblclick: this.editPlayer
            }, 'profilesgrid': {
                itemdblclick: this.editProfile
            }, 'usersgrid': {
                itemdblclick: this.editUser
            }, 'gymnasiumsgrid': {
                itemdblclick: this.editGymnasium
            }, 'clubsgrid': {
                itemdblclick: this.editClub
            }, 'teamsgrid': {
                itemdblclick: this.editTeam
            }, 'matchesgrid': {
                itemdblclick: this.editMatch
            }, 'rankgrid': {
                itemdblclick: this.editRank
            }, 'daysgrid': {
                itemdblclick: this.editDay
            }, 'limitdatesgrid': {
                itemdblclick: this.editLimitDate
            }, 'button[action=cancel]': {
                click: this.cancel
            }, 'button[action=save]': {
                click: this.save
            }, 'menuitem[action=displayActivity]': {
                click: this.showActivityGrid
            }, 'menuitem[action=managePlayers]': {
                click: this.showPlayersGrid
            }, 'menuitem[action=manageProfiles]': {
                click: this.showProfilesGrid
            }, 'menuitem[action=manageUsers]': {
                click: this.showUsersGrid
            }, 'menuitem[action=manageGymnasiums]': {
                click: this.showGymnasiumsGrid
            }, 'menuitem[action=manageClubs]': {
                click: this.showClubsGrid
            }, 'menuitem[action=manageTeams]': {
                click: this.showTeamsGrid
            }, 'menuitem[action=manageMatches]': {
                click: this.showMatchesGrid
            }, 'menuitem[action=manageRanks]': {
                click: this.showRanksGrid
            }, 'menuitem[action=manageRanksDragDrop]': {
                click: this.showRanksDragDrop
            }, 'menuitem[action=manageDays]': {
                click: this.showDaysGrid
            }, 'menuitem[action=manageLimitDates]': {
                click: this.showLimitDatesGrid
            }, 'menuitem[action=displayWeekSchedule]': {
                click: this.showWeekScheduleGrid
            }, 'button[action=showClubSelect]': {
                click: this.showClubSelect
            }, 'button[action=showProfileSelect]': {
                click: this.showProfileSelect
            }, 'button[action=showTeamSelect]': {
                click: this.showTeamSelect
            }, 'playersgrid button[action=delete]': {
                click: this.deletePlayers
            }, 'menuitem[action=displayIndicators]': {
                click: this.displayIndicators
            }, 'menuitem[action=displayHallOfFame]': {
                click: this.displayHallOfFame
            }, 'hall_of_fame_grid': {
                added: this.addToolbarHallOfFame
            }, 'button[action=addHallOfFame]': {
                click: this.addHallOfFame
            }, 'button[action=editHallOfFame]': {
                click: this.editHallOfFame
            }, 'button[action=deleteHallOfFame]': {
                click: this.deleteHallOfFame
            }, 'menuitem[action=displayTimeslots]': {
                click: this.displayTimeslots
            }, 'timeslots_grid': {
                added: this.addToolbarTimeslots
            }, 'button[action=addTimeslot]': {
                click: this.addTimeslot
            }, 'button[action=editTimeslot]': {
                click: this.editTimeslot
            }, 'button[action=deleteTimeslot]': {
                click: this.deleteTimeslot
            }, 'menuitem[action=displayCompetitions]': {
                click: this.displayCompetitions
            }, 'competitions_grid': {
                added: this.addToolbarCompetitions
            }, 'button[action=addCompetition]': {
                click: this.addCompetition
            }, 'button[action=editCompetition]': {
                click: this.editCompetition
            }, 'button[action=deleteCompetition]': {
                click: this.deleteCompetition
            }, 'menuitem[action=displayBlacklistGymnase]': {
                click: this.displayBlacklistGymnase
            }, 'blacklistgymnase_grid': {
                added: this.addToolbarBlacklistGymnase
            }, 'button[action=addBlacklistGymnase]': {
                click: this.addBlacklistGymnase
            }, 'button[action=editBlacklistGymnase]': {
                click: this.editBlacklistGymnase
            }, 'button[action=deleteBlacklistGymnase]': {
                click: this.deleteBlacklistGymnase
            }, 'menuitem[action=displayBlacklistTeam]': {
                click: this.displayBlacklistTeam
            }, 'menuitem[action=displayBlacklistTeams]': {
                click: this.displayBlacklistTeams
            }, 'blacklistteam_grid': {
                added: this.addToolbarBlacklistTeam
            }, 'blacklistteams_grid': {
                added: this.addToolbarBlacklistTeams
            }, 'button[action=addBlacklistTeam]': {
                click: this.addBlacklistTeam
            }, 'button[action=editBlacklistTeam]': {
                click: this.editBlacklistTeam
            }, 'button[action=deleteBlacklistTeam]': {
                click: this.deleteBlacklistTeam
            }, 'button[action=addBlacklistTeams]': {
                click: this.addBlacklistTeams
            }, 'button[action=editBlacklistTeams]': {
                click: this.editBlacklistTeams
            }, 'button[action=deleteBlacklistTeams]': {
                click: this.deleteBlacklistTeams
            }, 'menuitem[action=displayBlacklistDate]': {
                click: this.displayBlacklistDate
            }, 'blacklistdate_grid': {
                added: this.addToolbarBlacklistDate
            }, 'button[action=addBlacklistDate]': {
                click: this.addBlacklistDate
            }, 'button[action=editBlacklistDate]': {
                click: this.editBlacklistDate
            }, 'button[action=deleteBlacklistDate]': {
                click: this.deleteBlacklistDate
            }, 'button[action=archiveMatch]': {
                click: this.archiveMatch
            }, 'button[action=confirmMatch]': {
                click: this.confirmMatch
            }, 'button[action=unconfirmMatch]': {
                click: this.unconfirmMatch
            }, 'menuitem[action=manageNews]': {
                click: this.showNewsGrid
            }, 'button[action=addNews]': {
                click: this.addNews
            }, 'button[action=editNews]': {
                click: this.editNews
            }, 'button[action=deleteNews]': {
                click: this.deleteNews
            }, 'newsgrid': {
                itemdblclick: this.editNews
            }
        });
    },
    getIndicatorColumn(field_key) {
        switch (field_key) {
            case 'codes_match':
                return {
                    header: field_key, dataIndex: field_key, flex: 1,
                    renderer: function (val) {
                        return val.split(',').map(function (code_match) {
                            return Ext.String.format("<a href='/match.php?code_match={0}' target='_blank'>{1}</a>", code_match, code_match);
                        }).join(',');
                    }
                };
            case 'code_match':
            case 'prev_code_match':
            case 'last_code_match':
                return {
                    header: field_key, dataIndex: field_key, flex: 1,
                    renderer: function (val) {
                        return Ext.String.format("<a href='/match.php?code_match={0}' target='_blank'>{1}</a>", val, val);
                    }
                };
            default:
                return {
                    header: field_key, dataIndex: field_key, flex: 1
                };
        }
    },
    displayIndicators: function () {
        var mainPanel = this.getMainPanel();
        mainPanel.setAutoScroll(true);
        var alertView = Ext.create('Ufolep13Volley.view.view.Indicators', {
            flex: 1,
            indicatorType: 'alert'
        });
        var infoView = Ext.create('Ufolep13Volley.view.view.Indicators', {
            flex: 1,
            indicatorType: 'info'
        });
        var tab = mainPanel.add({
            title: 'Indicateurs',
            layout: 'hbox',
            autoScroll: true,
            items: [{
                title: 'Alertes',
                flex: 1,
                layout: 'fit',
                autoScroll: true,
                items: [alertView]
            }, {
                title: 'Infos',
                flex: 1,
                layout: 'fit',
                autoScroll: true,
                items: [infoView]
            }]
        });
        mainPanel.setActiveTab(tab);
        alertView.getStore().load();
        infoView.getStore().load();
    },
    filterPlayersWithoutClub: function (checkbox, newValue) {
        var store = checkbox.up('grid').getStore();
        if (newValue !== true) {
            store.removeFilter('filterPlayersWithoutClub');
        }
        else {
            store.filter({
                id: 'filterPlayersWithoutClub', filterFn: function (item) {
                    return (!Ext.isEmpty(item.get('active_teams_list'))) && (Ext.isEmpty(item.get('id_club')));
                }
            });
        }
        checkbox.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
    },
    filterPlayersWithoutLicence: function (checkbox, newValue) {
        var store = checkbox.up('grid').getStore();
        if (newValue !== true) {
            store.removeFilter('filterPlayersWithoutLicence');
        }
        else {
            store.filter({
                id: 'filterPlayersWithoutLicence', filterFn: function (item) {
                    return (!Ext.isEmpty(item.get('active_teams_list'))) && (Ext.isEmpty(item.get('num_licence')));
                }
            });
        }
        checkbox.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
    },
    filterPlayersWith2TeamsSameCompetition: function (checkbox, newValue) {
        var store = checkbox.up('grid').getStore();
        if (newValue !== true) {
            store.removeFilter('filterPlayersWith2TeamsSameCompetition');
        }
        else {

            store.filter({
                id: 'filterPlayersWith2TeamsSameCompetition', filterFn: function (item) {
                    if (item.get('active_teams_list') !== null) {
                        var countM = (item.get('active_teams_list').match(/\(m\)/g) || []).length;
                        var countF = (item.get('active_teams_list').match(/\(f\)/g) || []).length;
                        var countKH = (item.get('active_teams_list').match(/\(kh\)/g) || []).length;
                        var countC = (item.get('active_teams_list').match(/\(c\)/g) || []).length;
                        return ((countM > 1) || (countF > 1) || (countKH > 1) || (countC > 1));
                    }
                    return false;
                }
            });
        }
        checkbox.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
    },
    filterPlayersEngaged: function (checkbox, newValue) {
        var store = checkbox.up('grid').getStore();
        if (newValue !== true) {
            store.removeFilter('filterPlayersEngaged');
        }
        else {
            store.filter({
                id: 'filterPlayersEngaged', filterFn: function (item) {
                    return !Ext.isEmpty(item.get('active_teams_list'));
                }
            });
        }
        checkbox.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());

    },
    filterInactivePlayers: function (checkbox, newValue) {
        var store = checkbox.up('grid').getStore();
        if (newValue !== true) {
            store.removeFilter('filterInactivePlayers');
        }
        else {
            store.filter({
                id: 'filterInactivePlayers', filterFn: function (item) {
                    return (!item.get('est_actif')) && (!Ext.isEmpty(item.get('active_teams_list')));
                }
            });
        }
        checkbox.up('grid').down('displayfield[action=displayFilteredCount]').setValue(store.getCount());
    },
    editPlayer: function (button) {
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var widget = Ext.widget('playeredit');
        this.getFormPanelEditPlayer().loadRecord(record);
        this.getImagePlayer().show();
        this.getImagePlayer().setSrc(record.get('path_photo'));
        this.getFormPanelEditPlayer().down('textfield[name=prenom]').focus();
    },
    display_import_licence_file: function (button) {
        var this_window = Ext.create('Ext.window.Window', {
            title: "Import d'un fichier de licences",
            height: 500,
            width: 700,
            maximizable: true,
            layout: 'fit',
            items: {
                xtype: 'form',
                trackResetOnLoad: true,
                layout: 'form',
                url: '/rest/action.php/player/update_from_licence_file',
                items: [
                    {
                        name: 'licences',
                        allowBlank: false,
                        xtype: 'filefield',
                        fieldLabel: 'Fichier PDF',
                        buttonText: 'Sélection PDF...',
                        msgTarget: 'under'
                    }
                ],
                buttons: [
                    {
                        text: 'Annuler',
                        action: 'cancel',
                    },
                    {
                        text: 'Importer',
                        formBind: true,
                        disabled: true,
                        handler: function (form_button) {
                            var form = form_button.up('form').getForm();
                            if (form.isValid()) {
                                form.submit({
                                    success: function () {
                                        button.up('grid').getStore().load();
                                        form_button.up('window').close();
                                    },
                                    failure: function (form, action) {
                                        Ext.Msg.alert('Erreur', action.result.message);
                                    }
                                });
                            }
                        }
                    }
                ]
            }
        });
        this_window.show();
    },
    editProfile: function (button) {
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var widget = Ext.widget('profileedit');
        this.getFormPanelEditProfile().loadRecord(record);
    },
    editUser: function (button) {
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var widget = Ext.widget('useredit');
        this.getFormPanelEditUser().loadRecord(record);
    },
    editGymnasium: function (button) {
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var widget = Ext.widget('gymnasiumedit');
        this.getFormPanelEditGymnasium().loadRecord(record);
    },
    editClub: function (button) {
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var widget = Ext.widget('clubedit');
        this.getFormPanelEditClub().loadRecord(record);
    },
    editTeam: function (button) {
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var widget = Ext.widget('teamedit');
        this.getFormPanelEditTeam().loadRecord(record);
    },
    editMatch: function (button) {
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var widget = Ext.widget('matchedit');
        this.getFormPanelEditMatch().loadRecord(record);
    },
    editRank: function (button) {
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var widget = Ext.widget('rankedit');
        this.getFormPanelEditRank().loadRecord(record);
    },
    editDay: function (button) {
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var widget = Ext.widget('dayedit');
        this.getFormPanelEditDay().loadRecord(record);
    },
    editLimitDate: function (button) {
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var widget = Ext.widget('limitdateedit');
        this.getFormPanelEditLimitDate().loadRecord(record);
    },
    addPlayer: function (button) {
        var widget = Ext.widget('playeredit');
        this.getImagePlayer().hide();
        this.getFormPanelEditPlayer().down('textfield[name=prenom]').focus();
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        widget.down('form').loadRecord(record);
        widget.down('form').getForm().findField('id').setValue("");
    },
    addProfile: function (button) {
        var widget = Ext.widget('profileedit');
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        widget.down('form').loadRecord(record);
        widget.down('form').getForm().findField('id').setValue("");
    },
    addUser: function (button) {
        var widget = Ext.widget('useredit');
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        widget.down('form').loadRecord(record);
        widget.down('form').getForm().findField('id').setValue("");
    },
    addGymnasium: function (button) {
        var widget = Ext.widget('gymnasiumedit');
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        widget.down('form').loadRecord(record);
        widget.down('form').getForm().findField('id').setValue("");
    },
    addClub: function (button) {
        var widget = Ext.widget('clubedit');
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        widget.down('form').loadRecord(record);
        widget.down('form').getForm().findField('id').setValue("");
    },
    addTeam: function (button) {
        var widget = Ext.widget('teamedit');
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        widget.down('form').loadRecord(record);
        widget.down('form').getForm().findField('id_equipe').setValue("");
    },
    addMatch: function (button) {
        var widget = Ext.widget('matchedit');
        var record = button.up('grid').getSelectionModel().getSelection()[0];
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
    addDay: function (button) {
        var widget = Ext.widget('dayedit');
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        widget.down('form').loadRecord(record);
        widget.down('form').getForm().findField('id').setValue("");
    },
    addLimitDate: function (button) {
        var widget = Ext.widget('limitdateedit');
        var record = button.up('grid').getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        widget.down('form').loadRecord(record);
        widget.down('form').getForm().findField('id_date').setValue("");
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
    manage_failure: function (response) {
        if (response.status === '404') {
            Ext.Msg.alert('Erreur', "La page n'a pas été trouvée !");
            return;
        }
        var response_json = Ext.decode(response.responseText);
        Ext.create('Ext.window.Window', {
            title: 'Erreur (copiable)', height: 500, width: 700, maximizable: true, layout: 'fit', items: {
                xtype: 'textarea', value: response_json.message
            }
        }).show();
    },
    deleteHallOfFame: function (button) {
        this.genericDelete(button, '/rest/action.php/halloffame/delete', 'id');
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
        this.genericDelete(button, '/rest/action.php/timeslot/delete', 'id');
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
        this.genericDelete(button, '/rest/action.php/competition/delete', 'id');
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
        this.genericDelete(button, '/rest/action.php/blacklistcourt/delete', 'id');
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
        this.genericDelete(button, '/rest/action.php/blacklistteam/delete', 'id');
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
        this.genericDelete(button, '/rest/action.php/blacklistteams/delete', 'id');
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
        this.genericDelete(button, '/rest/action.php/blacklistdate/delete', 'id');
    },
    deleteUsers: function (button) {
        this.genericDelete(button, '/rest/action.php/usermanager/deleteUsers', 'id');
    },
    reset_password: function (button) {
        var this_controller = this;
        var records = button.up('grid').getSelectionModel().getSelection();
        if (records.length !== 1) {
            return;
        }
        Ext.Msg.show({
            title: 'Réinitialiser le mot de passe ?',
            msg: 'Etes-vous certain de vouloir réinitialiser le mot de passe ?',
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn !== 'yes') {
                    return;
                }
                Ext.Ajax.request({
                    url: '/rest/action.php/usermanager/reset_password', params: {
                        id: records[0].get('id')
                    }, success: function () {
                        button.up('grid').getStore().load();
                    }, failure: this_controller.manage_failure,
                });
            }
        });
    },
    deleteGymnasiums: function (button) {
        this.genericDelete(button, '/rest/action.php/court/delete', 'id');
    },
    deleteClubs: function (button) {
        this.genericDelete(button, '/rest/action.php/club/deleteClubs', 'id');
    },
    deleteTeams: function (button) {
        this.genericDelete(button, '/rest/action.php/team/delete', 'id_equipe');
    },
    deleteMatches: function (button) {
        this.genericDelete(button, '/rest/action.php/matchmgr/delete', 'id_match');
    },
    deleteRanks: function (button) {
        this.genericDelete(button, '/rest/action.php/rank/delete', 'id');
    },
    genericRequest: function (button, title, url, is_one_record_allowed, extra_params = {}) {
        var this_controller = this;
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
                var params = {
                    ids: ids.join(',')
                };
                Ext.apply(params, extra_params);
                Ext.Ajax.request({
                    url: url, params: params, timeout: 600000, success: function (response) {
                        if (response.status === 201) {
                            var response_json = Ext.decode(response.responseText);
                            Ext.create('Ext.window.Window', {
                                title: 'Réalisé avec succès',
                                height: 500,
                                width: 700,
                                maximizable: true,
                                layout: 'fit',
                                items: {
                                    xtype: 'textarea', value: response_json.message
                                }
                            }).show();
                        } else {
                            Ext.Msg.alert('Succès', "L'opération a été réalisée avec succès.");
                        }
                        grid.getStore().load();
                    }, failure: this_controller.manage_failure,
                });
            }
        });
    },
    generateHallOfFame: function (button) {
        var this_controller = this;
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length !== 1) {
            Ext.Msg.alert('Erreur', "Veuillez sélectionner une seule compétition !");
            return;
        }
        var code_competition = records[0].get('code_competition');
        
        Ext.create('Ext.window.Window', {
            title: 'Générer le palmarès',
            width: 400,
            modal: true,
            layout: 'fit',
            items: {
                xtype: 'form',
                bodyPadding: 10,
                defaults: {
                    anchor: '100%',
                    labelWidth: 120
                },
                items: [{
                    xtype: 'displayfield',
                    fieldLabel: 'Compétition',
                    value: code_competition
                }, {
                    xtype: 'datefield',
                    fieldLabel: 'Date début',
                    name: 'date_debut',
                    format: 'Y-m-d',
                    allowBlank: false
                }, {
                    xtype: 'datefield',
                    fieldLabel: 'Date fin',
                    name: 'date_fin',
                    format: 'Y-m-d',
                    allowBlank: false
                }, {
                    xtype: 'textfield',
                    fieldLabel: 'Période',
                    name: 'period',
                    allowBlank: false,
                    value: '2025-2026'
                }, {
                    xtype: 'combobox',
                    fieldLabel: 'Type',
                    name: 'title_season',
                    store: [['mi-saison', 'Mi-saison'], ['Dept.', 'Départemental']],
                    value: 'mi-saison',
                    allowBlank: false
                }],
                buttons: [{
                    text: 'Générer',
                    formBind: true,
                    handler: function() {
                        var form = this.up('form').getForm();
                        if (form.isValid()) {
                            var values = form.getValues();
                            var win = this.up('window');
                            Ext.Ajax.request({
                                url: '/rest/action.php/halloffame/generateHallOfFameFromMatches',
                                params: {
                                    code_competition: code_competition,
                                    date_debut: values.date_debut,
                                    date_fin: values.date_fin,
                                    period: values.period,
                                    title_season: values.title_season
                                },
                                timeout: 600000,
                                success: function(response) {
                                    win.close();
                                    Ext.Msg.alert('Succès', "Le palmarès a été généré avec succès.");
                                    grid.getStore().load();
                                },
                                failure: this_controller.manage_failure
                            });
                        }
                    }
                }, {
                    text: 'Annuler',
                    handler: function() {
                        this.up('window').close();
                    }
                }]
            }
        }).show();
    },
    resetCompetition: function (button) {
        this.genericRequest(button, 'Reset compétition', '/rest/action.php/competition/resetCompetition');
    },
    generateDays: function (button) {
        this.genericRequest(button, 'Générer les journées', '/rest/action.php/day/generateDays');
    },
    generateMatches: function (button) {
        this.genericRequest(button, 'Générer les matches', '/rest/action.php/matchmgr/generateMatches');
    },
    generate_matches_final_phase_cup_8: function (button) {
        this.genericRequest(button, 'Tirer au sort les 1/8e', '/rest/action.php/competition/generate_matches_final_phase_cup', false, {'nommage': '1/8e'});
    },
    generate_matches_final_phase_cup_4: function (button) {
        this.genericRequest(button, 'Tirer au sort les 1/4', '/rest/action.php/competition/generate_matches_final_phase_cup', false, {'nommage': '1/4'});
    },
    generate_matches_final_phase_cup_2: function (button) {
        this.genericRequest(button, 'Tirer au sort les 1/2', '/rest/action.php/competition/generate_matches_final_phase_cup', false, {'nommage': '1/2'});
    },
    genericDelete: function (button, url, id_field) {
        var this_controller = this;
        var records = button.up('grid').getSelectionModel().getSelection();
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
                    ids.push(record.get(id_field));
                });
                Ext.Ajax.request({
                    url: url, params: {
                        ids: ids.join(',')
                    }, success: function () {
                        button.up('grid').getStore().load();
                    }, failure: this_controller.manage_failure,
                });
            }
        });
    },
    deleteDays: function (button) {
        this.genericDelete(button, '/rest/action.php/day/delete', 'id');
    },
    deleteLimitDates: function (button) {
        this.genericDelete(button, '/rest/action.php/limitdate/delete', 'id_date');
    },
    deletePlayers: function (button) {
        this.genericDelete(button, '/rest/action.php/player/delete_players', 'id');
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
                }, success: function () {
                    if (viewport.down('tabpanel')) {
                        viewport.down('tabpanel').getActiveTab().getStore().load();
                    }
                    if (button.up('window')) {
                        button.up('window').close();
                    }
                }, failure: function (form, action) {
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
    showRanksDragDrop: function () {
        this.showAdministrationGrid('rankdragdroppanel');
    },
    showDaysGrid: function () {
        this.showAdministrationGrid('daysgrid');
    },
    showLimitDatesGrid: function () {
        this.showAdministrationGrid('limitdatesgrid');
    },
    showClubSelect: function (button) {
        var records = button.up('grid').getSelectionModel().getSelection();
        var idPlayers = [];
        Ext.each(records, function (record) {
            idPlayers.push(record.get('id'));
        });
        if (idPlayers.length === 0) {
            return;
        }
        var widget = Ext.widget('clubselect');
        this.getFormPanelSelectClub().getForm().setValues({
            id_players: idPlayers.join(',')
        });
    },
    showProfileSelect: function (button) {
        var records = button.up('grid').getSelectionModel().getSelection();
        var idUsers = [];
        Ext.each(records, function (record) {
            idUsers.push(record.get('id'));
        });
        if (idUsers.length === 0) {
            return;
        }
        var widget = Ext.widget('profileselect');
        this.getFormPanelSelectProfile().getForm().setValues({
            id_users: idUsers.join(',')
        });
    },
    showTeamSelect: function (button) {
        var records = button.up('grid').getSelectionModel().getSelection();
        var idPlayers = [];
        Ext.each(records, function (record) {
            idPlayers.push(record.get('id'));
        });
        if (idPlayers.length === 0) {
            return;
        }
        var widget = Ext.widget('teamselect');
        this.getFormPanelSelectTeam().getForm().setValues({
            id_players: idPlayers.join(',')
        });
    },
    displayHallOfFame: function () {
        this.showAdministrationGrid('hall_of_fame_grid');
    },
    addToolbarHallOfFame: function (grid) {
        grid.addDocked({
            xtype: 'toolbar', dock: 'top', items: ['ACTIONS', {
                xtype: 'tbseparator'
            }, {
                text: 'Ajouter', action: 'addHallOfFame'
            }, {
                text: 'Modifier', action: 'editHallOfFame'
            }, {
                text: 'Supprimer', action: 'deleteHallOfFame'
            }, {
                text: 'Diplôme(s)', hidden: true, action: 'download_diploma'
            },]
        });
    },
    displayTimeslots: function () {
        this.showAdministrationGrid('timeslots_grid');
    },
    addToolbarTimeslots: function (grid) {
        grid.addDocked({
            xtype: 'toolbar', dock: 'top', items: ['ACTIONS', {
                xtype: 'tbseparator'
            }, {
                text: 'Ajouter', action: 'addTimeslot'
            }, {
                text: 'Modifier', action: 'editTimeslot'
            }, {
                text: 'Supprimer', action: 'deleteTimeslot'
            }]
        });
    },
    displayCompetitions: function () {
        this.showAdministrationGrid('competitions_grid');
    },
    addToolbarCompetitions: function (grid) {
        grid.addDocked({
            xtype: 'toolbar', dock: 'top', items: ['ACTIONS', {
                xtype: 'tbseparator'
            }, {
                text: 'Ajouter', action: 'addCompetition'
            }, {
                text: 'Modifier', action: 'editCompetition'
            }, {
                text: 'Supprimer', action: 'deleteCompetition'
            }, {
                text: 'Générer', menu: [{
                    text: 'Palmarès...', action: 'generateHallOfFame'
                }, {
                    text: 'Reset compétition...', action: 'resetCompetition'
                }, {
                    text: 'Journées...', action: 'generateDays'
                }, {
                    text: 'Tirer au sort les 1/8e', action: 'generate_matches_final_phase_cup_8'
                }, {
                    text: 'Tirer au sort les 1/4', action: 'generate_matches_final_phase_cup_4'
                }, {
                    text: 'Tirer au sort les 1/2', action: 'generate_matches_final_phase_cup_2'
                },]
            },
            ]
        });
    },
    displayBlacklistGymnase: function () {
        this.showAdministrationGrid('blacklistgymnase_grid');
    },
    addToolbarBlacklistGymnase: function (grid) {
        grid.addDocked({
            xtype: 'toolbar', dock: 'top', items: ['ACTIONS', {
                xtype: 'tbseparator'
            }, {
                text: 'Ajouter', action: 'addBlacklistGymnase'
            }, {
                text: 'Modifier', action: 'editBlacklistGymnase'
            }, {
                text: 'Supprimer', action: 'deleteBlacklistGymnase'
            }]
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
            xtype: 'toolbar', dock: 'top', items: ['ACTIONS', {
                xtype: 'tbseparator'
            }, {
                text: 'Ajouter', action: 'addBlacklistTeam'
            }, {
                text: 'Modifier', action: 'editBlacklistTeam'
            }, {
                text: 'Supprimer', action: 'deleteBlacklistTeam'
            }]
        });
    },
    addToolbarBlacklistTeams: function (grid) {
        grid.addDocked({
            xtype: 'toolbar', dock: 'top', items: ['ACTIONS', {
                xtype: 'tbseparator'
            }, {
                text: 'Ajouter', action: 'addBlacklistTeams'
            }, {
                text: 'Modifier', action: 'editBlacklistTeams'
            }, {
                text: 'Supprimer', action: 'deleteBlacklistTeams'
            }]
        });
    },
    displayBlacklistDate: function () {
        this.showAdministrationGrid('blacklistdate_grid');
    },
    addToolbarBlacklistDate: function (grid) {
        grid.addDocked({
            xtype: 'toolbar', dock: 'top', items: ['ACTIONS', {
                xtype: 'tbseparator'
            }, {
                text: 'Ajouter', action: 'addBlacklistDate'
            }, {
                text: 'Modifier', action: 'editBlacklistDate'
            }, {
                text: 'Supprimer', action: 'deleteBlacklistDate'
            }]
        });
    },
    archiveMatch: function (button) {
        var this_controller = this;
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
                    url: '/rest/action.php/matchmgr/archiveMatch', params: {
                        ids: ids.join(',')
                    }, success: function () {
                        grid.getStore().load();
                    }, failure: this_controller.manage_failure,
                });
            }
        });
    },
    confirmMatch: function (button) {
        var this_controller = this;
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
                    url: '/rest/action.php/matchmgr/confirmMatch', params: {
                        ids: ids.join(',')
                    }, success: function () {
                        grid.getStore().load();
                    }, failure: this_controller.manage_failure,
                });
            }
        });
    },
    unconfirmMatch: function (button) {
        var this_controller = this;
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
                    url: '/rest/action.php/matchmgr/unconfirmMatch', params: {
                        ids: ids.join(',')
                    }, success: function () {
                        grid.getStore().load();
                    }, failure: this_controller.manage_failure,
                });
            }
        });
    },
    setTeamLeader: function (button) {
        var this_controller = this;
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        if (!record) {
            Ext.Msg.alert('Erreur', 'Veuillez sélectionner une équipe');
            return;
        }
        var id_equipe = record.get('id_equipe');
        var nom_equipe = record.get('nom_equipe');
        var playersStore = Ext.create('Ext.data.Store', {
            fields: ['id', 'prenom', 'nom', 'full_name'],
            proxy: {
                type: 'ajax',
                url: '/rest/action.php/player/get_players_by_team',
                reader: {
                    type: 'json'
                }
            },
            autoLoad: false
        });
        playersStore.load({
            params: {id_team: id_equipe}
        });
        var selectWindow = Ext.create('Ext.window.Window', {
            title: 'Nommer responsable pour ' + nom_equipe,
            width: 400,
            height: 400,
            layout: 'fit',
            modal: true,
            items: [{
                xtype: 'grid_ufolep',
                store: playersStore,
                columns: [
                    {header: 'Prénom', dataIndex: 'prenom', flex: 1},
                    {header: 'Nom', dataIndex: 'nom', flex: 1},
                    {header: 'Dans l\'équipe', dataIndex: 'is_in_team', width: 100, renderer: function(val) {
                        return val == 1 ? 'Oui' : 'Non';
                    }}
                ]
            }],
            buttons: [{
                text: 'Annuler',
                handler: function () {
                    selectWindow.close();
                }
            }, {
                text: 'Nommer responsable',
                handler: function () {
                    var playerGrid = selectWindow.down('grid');
                    var selectedPlayer = playerGrid.getSelectionModel().getSelection()[0];
                    if (!selectedPlayer) {
                        Ext.Msg.alert('Erreur', 'Veuillez sélectionner un joueur');
                        return;
                    }
                    Ext.Ajax.request({
                        url: '/rest/action.php/player/set_leader',
                        params: {
                            ids: selectedPlayer.get('id'),
                            id_team: id_equipe
                        },
                        success: function () {
                            Ext.Msg.alert('Succès', 'Le joueur a été nommé responsable de l\'équipe');
                            selectWindow.close();
                            grid.getStore().load();
                        },
                        failure: this_controller.manage_failure
                    });
                }
            }]
        });
        selectWindow.show();
    },
    showNewsGrid: function () {
        var mainPanel = this.getMainPanel();
        var tab = mainPanel.add(Ext.widget('newsgrid'));
        mainPanel.setActiveTab(tab);
    },
    addNews: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        var widget = Ext.widget('newsedit');
        if (record) {
            widget.down('form').loadRecord(record);
            widget.down('form').getForm().findField('id').setValue("");
        }
    },
    editNews: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        if (!record) {
            Ext.Msg.alert('Erreur', 'Veuillez sélectionner une news');
            return;
        }
        var widget = Ext.widget('newsedit');
        this.getFormPanelEditNews().loadRecord(record);
    },
    deleteNews: function (button) {
        var grid = button.up('grid');
        var record = grid.getSelectionModel().getSelection()[0];
        if (!record) {
            Ext.Msg.alert('Erreur', 'Veuillez sélectionner une news');
            return;
        }
        Ext.Msg.confirm('Confirmation', 'Voulez-vous vraiment supprimer cette news ?', function (btn) {
            if (btn === 'yes') {
                Ext.Ajax.request({
                    url: '/rest/action.php/news/deleteNews',
                    method: 'POST',
                    params: {id: record.get('id')},
                    success: function () {
                        grid.getStore().load();
                    },
                    failure: function (response) {
                        var result = Ext.decode(response.responseText);
                        Ext.Msg.alert('Erreur', result.message);
                    }
                });
            }
        });
    },
    saveNews: function (editor, context) {
        var record = context.record;
        Ext.Ajax.request({
            url: '/rest/action.php/news/saveNews',
            method: 'POST',
            params: {
                id: record.get('id'),
                title: record.get('title'),
                text: record.get('text'),
                file_path: record.get('file_path'),
                news_date: Ext.Date.format(record.get('news_date'), 'Y-m-d'),
                is_disabled: record.get('is_disabled')
            },
            success: function () {
                context.grid.getStore().load();
            },
            failure: function (response) {
                var result = Ext.decode(response.responseText);
                Ext.Msg.alert('Erreur', result.message);
            }
        });
    }
});