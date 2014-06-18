Ext.define('Ufolep13Volley.controller.mobile.Portal', {
    extend: 'Ext.app.Controller',
    requires: ['Ufolep13Volley.view.mobile.MyPlayers'],
    config: {
        refs: {
            mainPanel: 'navigationview',
            buttonMyPlayers: 'button[action=getMyPlayers]',
            buttonDisconnect: 'button[action=disconnect]'
        },
        control: {
            buttonMyPlayers: {
                tap: 'showMyPlayers'
            },
            buttonDisconnect: {
                tap: 'disconnect'
            }
        }
    },
    showMyPlayers: function() {
        this.getMainPanel().reset();
        this.getMainPanel().push({
            xtype: 'listmyplayers'
        });
    },
    disconnect: function() {
        window.location.href = 'ajax/logout.php';
    }
}
);
