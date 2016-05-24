Ext.define('Ufolep13Volley.controller.Classement', {
    extend: 'Ext.app.Controller',
    stores: ['Classement'],
    models: ['Classement'],
    views: [],
    refs: [],
    init: function () {
        this.control(
            {
                'grid[title=Classement]': {
                    itemaddpenaltybuttonclick: this.addPenalty,
                    itemremovepenaltybuttonclick: this.removePenalty,
                    itemdeletebuttonclick: this.deleteTeam,
                    added: this.addAdminColumns
                }
            });
    },
    addAdminColumns: function (grid) {
        var gridView = grid;
        var column = Ext.create('Ext.grid.column.Action', {
                header: 'Administration',
                width: 200,
                items: [
                    {
                        icon: 'images/svg/thumb_down.svg',
                        tooltip: 'Ajouter un point de pénalité',
                        handler: function (grid, rowIndex) {
                            this.up('grid').fireEvent('itemaddpenaltybuttonclick', grid, rowIndex);
                        }
                    },
                    {
                        icon: 'images/svg/thumb_up.svg',
                        tooltip: 'Enlever un point de pénalité',
                        handler: function (grid, rowIndex) {
                            this.up('grid').fireEvent('itemremovepenaltybuttonclick', grid, rowIndex);
                        }
                    },
                    {
                        icon: 'images/svg/delete.svg',
                        tooltip: 'Supprimer cette équipe de la compétition',
                        handler: function (grid, rowIndex) {
                            this.up('grid').fireEvent('itemdeletebuttonclick', grid, rowIndex);
                        }
                    }
                ]
            }
        );
        gridView.headerCt.insert(gridView.columns.length, column);
        gridView.getView().refresh();
    },
    addPenalty: function (grid, rowIndex) {
        var me = this;
        var rec = grid.getStore().getAt(rowIndex);
        Ext.Msg.show({
            title: 'Pénalité',
            msg: 'Voulez-vous ajouter un point de pénalité à cette équipe ?',
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn === 'ok') {
                    Ext.Ajax.request({
                        url: 'ajax/penalite.php',
                        params: {
                            type: 'ajout',
                            compet: rec.get('code_competition'),
                            equipe: rec.get('id_equipe')
                        },
                        success: function (response) {
                            var responseJson = Ext.decode(response.responseText);
                            Ext.Msg.alert('Info', responseJson.message);
                            me.getClassementStore().load();
                        }
                    });
                }
            }
        });
    },
    removePenalty: function (grid, rowIndex) {
        var me = this;
        var rec = grid.getStore().getAt(rowIndex);
        Ext.Msg.show({
            title: 'Pénalité',
            msg: 'Voulez-vous enlever un point de pénalité à cette équipe ?',
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn === 'ok') {
                    Ext.Ajax.request({
                        url: 'ajax/penalite.php',
                        params: {
                            type: 'suppression',
                            compet: rec.get('code_competition'),
                            equipe: rec.get('id_equipe')
                        },
                        success: function (response) {
                            var responseJson = Ext.decode(response.responseText);
                            Ext.Msg.alert('Info', responseJson.message);
                            me.getClassementStore().load();
                        }
                    });
                }
            }
        });
    },
    deleteTeam: function (grid, rowIndex) {
        var me = this;
        var rec = grid.getStore().getAt(rowIndex);
        Ext.Msg.show({
            title: 'Suppression',
            msg: 'Cette opération entrainera la suppression de cette équipe de cette compétition ! Êtes-vous sur ?',
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if (btn === 'ok') {
                    Ext.Ajax.request({
                        url: 'ajax/supprimerEquipeCompetition.php',
                        params: {
                            compet: rec.get('code_competition'),
                            equipe: rec.get('id_equipe')
                        },
                        success: function (response) {
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