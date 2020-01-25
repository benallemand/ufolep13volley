Ext.define('Ufolep13Volley.controller.manage_match_players', {
    extend: 'Ext.app.Controller',
    stores: [
        'Players',
        'MatchPlayers'
    ],
    models: [
        'Player'
    ],
    views: [
        'form.MatchPlayers',
        'view.MatchPlayers'
    ],
    refs: [],
    init: function () {
        this.control(
            {
                'button[action=manage_match_players]': {
                    click: this.manage_match_players
                },
                'button[action=save]': {
                    click: this.save
                },
                'matchesgrid': {
                    selectionchange: this.manage_display
                }
            }
        );
    },
    manage_display: function (selection_model, selected) {
        var button = selection_model.view.ownerCt.down('button[action=manage_match_players]');
        var is_hidden = Ext.isEmpty(selected) ||
            !Ext.isArray(selected) ||
            selected.length !== 1 ||
            selected[0].get('match_status') !== 'CONFIRMED';
        button.setHidden(is_hidden);
    },
    save: function (button) {
        var form = button.up('form').getForm();
        if (form.isValid()) {
            form.submit({
                success: function () {
                    Ext.data.StoreManager.lookup('Players').load();
                    button.up('window').close();
                },
                failure: function (form, action) {
                    Ext.Msg.alert('Erreur', action.result.message);
                }
            });
        }
    },
    manage_match_players: function (button) {
        var current_record = button.up('grid').getSelectionModel().getSelection()[0];
        var store = button.up('grid').getStore();
        if (Ext.isEmpty(current_record)) {
            return;
        }
        var this_window = Ext.create('Ext.window.Window', {
            title: "Match / Joueurs (joueuses)",
            height: window.innerHeight * 80 / 100,
            width: window.innerWidth * 80 / 100,
            maximizable: true,
            layout: 'border',
            items: [
                {
                    xtype: 'form_match_players',
                    region: 'center',
                    flex: 1,
                    trackResetOnLoad: true
                },
                {
                    xtype: 'panel',
                    region: 'south',
                    autoScroll: true,
                    flex: 5,
                    title: 'Actuellement',
                    layout: 'fit',
                    items: {
                        xtype: 'view_match_players'
                    }
                }
            ]
        });
        this_window.down('form').loadRecord(current_record);
        this_window.down('dataview').getStore().load({
            params: {
                id_match: current_record.get('id_match')
            }
        });
        this_window.show();
    }
});