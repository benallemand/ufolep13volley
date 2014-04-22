Ext.define('Ufolep13Volley.controller.Administration', {
    extend: 'Ext.app.Controller',
    views: [
        'player.Edit'
    ],
    refs: [
        {
            ref: 'managePlayersGrid',
            selector: 'grid[title=Gestion des joueurs]'
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
                    'playeredit button[action=save]': {
                        click: this.updatePlayer
                    }
                }
        );
    },
    editPlayer: function() {
        var record = this.getManagePlayersGrid().getSelectionModel().getSelection()[0];
        if (!record) {
            return;
        }
        var view = Ext.widget('playeredit');
        view.down('form').loadRecord(record);
    },
    addPlayer: function() {
        Ext.widget('playeredit');
    },
    updatePlayer: function() {
        Ext.Msg.alert('Erreur', "Cette fonction n'est pas encore disponible !");
    }
});