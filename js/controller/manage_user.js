Ext.define('Ufolep13Volley.controller.manage_user', {
    extend: 'Ext.app.Controller',
    stores: [],
    models: [],
    views: ['window.UserTeams', 'window.UserClubs'],
    refs: [],
    init: function () {
        this.control(
            {
                'usersgrid > toolbar > button[action=manage_user_teams]': {
                    click: this.show_manage_user_teams
                },
                'window_user_teams button[action=save_user_teams]': {
                    click: this.save_user_teams
                },
                'usersgrid > toolbar > button[action=manage_user_clubs]': {
                    click: this.show_manage_user_clubs
                },
                'window_user_clubs button[action=save_user_clubs]': {
                    click: this.save_user_clubs
                }
            }
        );
    },
    show_manage_user_teams: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length !== 1) {
            Ext.Msg.alert('Attention', 'Veuillez sélectionner un utilisateur');
            return;
        }
        var record = records[0];
        var userTeamsWindow = Ext.create('Ufolep13Volley.view.window.UserTeams', {
            title: Ext.String.format("Équipes liées à {0}", record.get('email'))
        });
        userTeamsWindow.loadUserTeams(record.get('id'));
    },
    save_user_teams: function (button) {
        var win = button.up('window');
        var form = win.down('form');
        var grid = win.down('grid');
        var userId = form.getForm().findField('user_id').getValue();
        var selectedRecords = grid.getSelectionModel().getSelection();
        var teamIds = [];

        Ext.each(selectedRecords, function (record) {
            teamIds.push(record.get('id_equipe'));
        });

        Ext.Ajax.request({
            url: '/rest/action.php/usermanager/updateUserTeams',
            method: 'POST',
            params: {
                user_id: userId,
                team_ids: teamIds.join(',')
            },
            success: function () {
                Ext.ComponentQuery.query('usersgrid')[0].getStore().reload();
                Ext.Msg.alert('Succès', 'Les équipes ont été mises à jour');
                win.close();
            },
            failure: function (response) {
                console.log(response);
                if (response.responseText) {
                    let responseJson = Ext.decode(response.responseText);
                    Ext.Msg.alert('Erreur', responseJson.message);
                    return;
                }
                Ext.Msg.alert('Erreur', 'Une erreur est survenue');
            }
        });
    },
    show_manage_user_clubs: function (button) {
        var grid = button.up('grid');
        var records = grid.getSelectionModel().getSelection();
        if (records.length !== 1) {
            Ext.Msg.alert('Attention', 'Veuillez sélectionner un utilisateur');
            return;
        }
        var record = records[0];
        var userClubsWindow = Ext.create('Ufolep13Volley.view.window.UserClubs', {
            title: Ext.String.format("Clubs liés à {0}", record.get('email'))
        });
        userClubsWindow.loadUserClubs(record.get('id'));
    },
    save_user_clubs: function (button) {
        var win = button.up('window');
        var form = win.down('form');
        var grid = win.down('grid');
        var userId = form.getForm().findField('user_id').getValue();
        var selectedRecords = grid.getSelectionModel().getSelection();
        var clubIds = [];

        Ext.each(selectedRecords, function (record) {
            clubIds.push(record.get('id'));
        });

        Ext.Ajax.request({
            url: '/rest/action.php/usermanager/updateUserClubs',
            method: 'POST',
            params: {
                user_id: userId,
                club_ids: clubIds.join(',')
            },
            success: function () {
                Ext.ComponentQuery.query('usersgrid')[0].getStore().reload();
                Ext.Msg.alert('Succès', 'Les clubs ont été mis à jour');
                win.close();
            },
            failure: function (response) {
                console.log(response);
                if (response.responseText) {
                    let responseJson = Ext.decode(response.responseText);
                    Ext.Msg.alert('Erreur', responseJson.message);
                    return;
                }
                Ext.Msg.alert('Erreur', 'Une erreur est survenue');
            }
        });
    }
});