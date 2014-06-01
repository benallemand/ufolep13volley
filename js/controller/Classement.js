Ext.define('Ufolep13Volley.controller.Classement', {
    extend: 'Ext.app.Controller',
    stores: ['Classement'],
    models: ['Classement'],
    views: [],
    refs: [],
    init: function() {
        this.control(
                {
                    'grid[title=Classement]': {
                        itemaddpenaltybuttonclick: this.addPenalty,
                        itemremovepenaltybuttonclick: this.removePenalty,
                        itemdeletebuttonclick: this.deleteTeam
                    }
                });
    },
    addPenalty: function(grid, rowIndex) {
        var me = this;
        var rec = grid.getStore().getAt(rowIndex);
        Ext.Msg.show({
            title: 'P�nalit�',
            msg: 'Voulez-vous ajouter un point de p�nalit� � cette �quipe ?',
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.Msg.QUESTION,
            fn: function(btn) {
                if (btn === 'ok') {
                    Ext.Ajax.request({
                        url: 'ajax/penalite.php',
                        params: {
                            type: 'ajout',
                            compet: rec.get('code_competition'),
                            equipe: rec.get('id_equipe')
                        },
                        success: function(response) {
                            var responseJson = Ext.decode(response.responseText);
                            Ext.Msg.alert('Info', responseJson.message);
                            me.getClassementStore().load();
                        }
                    });
                }
            }
        });
    },
    removePenalty: function(grid, rowIndex) {
        var me = this;
        var rec = grid.getStore().getAt(rowIndex);
        Ext.Msg.show({
            title: 'P�nalit�',
            msg: 'Voulez-vous enlever un point de p�nalit� � cette �quipe ?',
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.Msg.QUESTION,
            fn: function(btn) {
                if (btn === 'ok') {
                    Ext.Ajax.request({
                        url: 'ajax/penalite.php',
                        params: {
                            type: 'suppression',
                            compet: rec.get('code_competition'),
                            equipe: rec.get('id_equipe')
                        },
                        success: function(response) {
                            var responseJson = Ext.decode(response.responseText);
                            Ext.Msg.alert('Info', responseJson.message);
                            me.getClassementStore().load();
                        }
                    });
                }
            }
        });
    },
    deleteTeam: function(grid, rowIndex) {
        var me = this;
        var rec = grid.getStore().getAt(rowIndex);
        Ext.Msg.show({
            title: 'Suppression',
            msg: 'Cette op�ration entrainera la suppression de cette �quipe de cette comp�tition ! �tes-vous sur ?',
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.Msg.QUESTION,
            fn: function(btn) {
                if (btn === 'ok') {
                    Ext.Ajax.request({
                        url: 'ajax/supprimerEquipeCompetition.php',
                        params: {
                            compet: rec.get('code_competition'),
                            equipe: rec.get('id_equipe')
                        },
                        success: function(response) {
                            var responseJson = Ext.decode(response.responseText);
                            Ext.Msg.alert('Info', responseJson.message);
                            me.getClassementStore().load();
                        }
                    });
                }
            }
        });
    }
});