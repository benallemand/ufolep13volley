Ext.onReady(function () {
    var s = Snap("body");
    Snap.load('includes/templates/teamSheet.svg', function (loadedSvg) {
        s.append(loadedSvg);
        var storeTeamSheet = Ext.create('Ext.data.Store', {
            fields: [
                'club',
                'championnat',
                'division',
                'leader',
                'portable',
                'courriel',
                'creneau',
                'gymnase',
                'equipe',
                'date_visa_ctsd'
            ],
            proxy: {
                type: 'ajax',
                url: 'ajax/getMyTeamSheet.php',
                reader: {
                    type: 'json',
                    root: 'results'
                }
            },
            autoLoad: false
        });
        storeTeamSheet.load(function (records) {
            if (records.length !== 1) {
                return;
            }
            var record = records[0];
            s.select("text[id='club']").node.innerHTML = record.get('club');
            s.select("text[id='championship']").node.innerHTML = record.get('championnat');
            s.select("text[id='league']").node.innerHTML = record.get('division');
            s.select("text[id='leader']").node.innerHTML = record.get('leader');
            s.select("text[id='phone']").node.innerHTML = record.get('portable');
            s.select("text[id='email']").node.innerHTML = record.get('courriel');
            s.select("text[id='scheduler']").node.innerHTML = record.get('creneau');
            s.select("text[id='court']").node.innerHTML = record.get('gymnase');
            s.select("text[id='team']").node.innerHTML = record.get('equipe');
            s.select("text[id='visaDate']").node.innerHTML = 'Visa CTSD le: ' + record.get('date_visa_ctsd');
        });
        var storeMyPlayers = Ext.create('Ext.data.Store', {
            fields: [
                'full_name',
                'prenom',
                'nom',
                'telephone',
                'email',
                'num_licence',
                'path_photo',
                'sexe',
                {
                    name: 'departement_affiliation',
                    type: 'int'
                },
                {
                    name: 'est_actif',
                    type: 'bool',
                    convert: function (val) {
                        return val === '1';
                    }
                },
                {
                    name: 'id_club',
                    type: 'int'
                },
                'adresse',
                'code_postal',
                'ville',
                'telephone2',
                'email2',
                'telephone3',
                'telephone4',
                {
                    name: 'est_licence_valide',
                    type: 'bool',
                    convert: function (val) {
                        return val === '1';
                    }
                },
                {
                    name: 'est_responsable_club',
                    type: 'bool',
                    convert: function (val) {
                        return val === '1';
                    }
                },
                {
                    name: 'is_captain',
                    type: 'bool',
                    convert: function (val) {
                        return val === '1';
                    }
                },
                {
                    name: 'is_leader',
                    type: 'bool',
                    convert: function (val) {
                        return val === '1';
                    }
                },
                {
                    name: 'is_vice_leader',
                    type: 'bool',
                    convert: function (val) {
                        return val === '1';
                    }
                },
                {
                    name: 'id',
                    type: 'int'
                },
                {
                    name: 'date_homologation',
                    type: 'date'
                }
            ],
            proxy: {
                type: 'ajax',
                url: 'ajax/getMyPlayers.php',
                reader: {
                    type: 'json',
                    root: 'results'
                }
            },
            autoLoad: false
        });
        storeMyPlayers.load(function (records) {
            var groupPlayers = s.select("g[id='teamPlayers']");
            Ext.each(records, function (record, index) {
                var group = groupPlayers.g();
                group.image(record.get('path_photo'), 0, 0, 100, 100).attr({
                    preserveAspectRatio: "xMinYMin meet"
                });
                group.text(110, 10, record.get('nom'));
                group.text(110, 30, record.get('prenom'));
                group.text(110, 60, record.get('num_licence') + ' /' + record.get('sexe'));
                group.text(110, 80, 'Present:');
                group.rect(250, 65, 20, 20).attr({
                    'fill': 'white',
                    'stroke': 'black',
                    'stroke-width': 1
                });
                var roles = [];
                if (record.get('is_leader')) {
                    roles.push('RESP');
                }
                if (record.get('is_captain')) {
                    roles.push('CAP');
                }
                if (record.get('is_vice_leader')) {
                    roles.push('SUPP');
                }
                group.text(110, 100, roles.join('/')).attr({'fill': 'red'});
                var maxPlayersByColumn = 6;
//                if(records.length > 18) {
//                    maxPlayersByColumn = 8;
//                }
                var playerNumber = index;
                group.transform('t' + Math.floor(playerNumber / maxPlayersByColumn) * 300 + ',' + (playerNumber % maxPlayersByColumn) * 110);
                group.rect(0, -5, 300, 110).attr({
                    'fill': 'gray',
                    'stroke': 'black',
                    'stroke-width': 1,
                    'fill-opacity': 0.1
                });
            });
            var widthPlayers = groupPlayers.node.getBoundingClientRect().width;
            var widthTeamDetails = s.select("g[id='teamDetails']").node.getBoundingClientRect().width;
            s.select("g[id='teamDetails']").line(90, 0, 90, 120).attr({
                'stroke': 'black',
                'stroke-width': 1
            });
            s.select("g[id='teamDetails']").line(0, 15, widthTeamDetails, 15).attr({
                'stroke': 'black',
                'stroke-width': 1
            });
            s.select("g[id='teamDetails']").line(0, 45, widthTeamDetails, 45).attr({
                'stroke': 'black',
                'stroke-width': 1
            });
            s.select("g[id='teamDetails']").line(0, 90, widthTeamDetails, 90).attr({
                'stroke': 'black',
                'stroke-width': 1
            });
            var widthTeamTitleVisa = s.select("g[id='teamTitleVisa']").node.getBoundingClientRect().width;
            var widthMatchDetails = s.select("g[id='matchDetails']").node.getBoundingClientRect().width;
            var finalWidth = Math.max(widthPlayers, widthTeamDetails + widthTeamTitleVisa + widthMatchDetails);
            s.select("g[id='teamTitleVisa']").transform('t' + (finalWidth / 2) + ',0');
            s.select("g[id='teamTitleVisa']").rect(0, 150, s.select("text[id='team']").node.getBoundingClientRect().width, s.select("text[id='team']").node.getBoundingClientRect().height, 10, 10).attr({
                'fill': 'white',
                'stroke': 'black',
                'stroke-width': 1,
                'fill-opacity': 0.1
            });
            s.select("g[id='matchDetails']").transform('t' + (finalWidth) + ',0');
            var widthViewport = s.select("g[id='viewport']").node.getBoundingClientRect().width;
            var heightViewport = s.select("g[id='viewport']").node.getBoundingClientRect().height;
            s.select("svg").attr({
                viewBox: "0 0 " + widthViewport + " " + heightViewport,
                preserveAspectRatio: "xMinYMin meet"
            });
        });
    });
});


