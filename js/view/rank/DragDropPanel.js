Ext.define('Ufolep13Volley.view.rank.DragDropPanel', {
    extend: 'Ext.panel.Panel',
    alias: 'widget.rankdragdroppanel',
    title: 'Gestion des Classements (Drag & Drop)',
    layout: 'border',
    
    initComponent: function() {
        var me = this;
        
        me.competitionStore = Ext.create('Ext.data.Store', {
            fields: ['code_competition', 'libelle'],
            proxy: {
                type: 'ajax',
                url: '/rest/action.php/competition/getCompetitions',
                reader: { type: 'json' }
            },
            autoLoad: true
        });
        
        me.items = [{
            region: 'north',
            xtype: 'toolbar',
            items: [{
                xtype: 'combo',
                itemId: 'competitionCombo',
                fieldLabel: 'Compétition',
                labelWidth: 80,
                width: 300,
                store: me.competitionStore,
                displayField: 'libelle',
                valueField: 'code_competition',
                queryMode: 'local',
                forceSelection: true,
                listeners: {
                    select: function(combo, record) {
                        me.loadDivisions(record.get('code_competition'));
                    }
                }
            }, '-', {
                text: 'Sauvegarder',
                iconCls: 'x-fa fa-save',
                handler: function() {
                    me.saveChanges();
                }
            }, '-', {
                text: 'Mode Grille',
                iconCls: 'x-fa fa-table',
                handler: function() {
                    me.up('tabpanel').add({xtype: 'rankgrid'});
                    me.up('tabpanel').setActiveTab(me.up('tabpanel').items.length - 1);
                }
            }]
        }, {
            region: 'center',
            xtype: 'panel',
            itemId: 'divisionsContainer',
            layout: 'hbox',
            autoScroll: true,
            defaults: {
                flex: 1,
                margin: 5
            },
            items: [{
                xtype: 'panel',
                html: '<div style="padding: 20px; color: #666;">Sélectionnez une compétition pour afficher les divisions</div>'
            }]
        }];
        
        me.callParent(arguments);
    },
    
    loadDivisions: function(code_competition) {
        var me = this;
        var container = me.down('#divisionsContainer');
        
        container.setLoading(true);
        
        Ext.Ajax.request({
            url: '/rest/action.php/rank/getRanksByCompetitionGroupedByDivision',
            method: 'GET',
            params: { code_competition: code_competition },
            success: function(response) {
                var divisions = Ext.decode(response.responseText);
                
                Ext.Ajax.request({
                    url: '/rest/action.php/rank/getUnassignedTeams',
                    method: 'GET',
                    params: { code_competition: code_competition },
                    success: function(response2) {
                        var unassigned = Ext.decode(response2.responseText);
                        me.renderDivisions(divisions, unassigned, code_competition);
                        container.setLoading(false);
                    },
                    failure: function() {
                        container.setLoading(false);
                        Ext.Msg.alert('Erreur', 'Impossible de charger les équipes non affectées');
                    }
                });
            },
            failure: function() {
                container.setLoading(false);
                Ext.Msg.alert('Erreur', 'Impossible de charger les divisions');
            }
        });
    },
    
    renderDivisions: function(divisions, unassigned, code_competition) {
        var me = this;
        var container = me.down('#divisionsContainer');
        
        container.removeAll();
        
        me.code_competition = code_competition;
        me.originalData = {
            divisions: Ext.clone(divisions),
            unassigned: Ext.clone(unassigned)
        };
        
        // Panel for unassigned teams
        var unassignedPanel = me.createDivisionPanel('unassigned', 'Non affectées', unassigned.map(function(team) {
            return {
                id: null,
                id_equipe: team.id_equipe,
                nom_equipe: team.nom_equipe,
                club: team.club,
                rank_start: 0
            };
        }));
        container.add(unassignedPanel);
        
        // Panels for each division
        var divisionKeys = Object.keys(divisions).sort(function(a, b) {
            var numA = parseInt(a) || 999;
            var numB = parseInt(b) || 999;
            return numA - numB;
        });
        
        Ext.each(divisionKeys, function(divKey) {
            var teams = divisions[divKey];
            var panel = me.createDivisionPanel(divKey, 'Division ' + divKey, teams);
            container.add(panel);
        });
        
        // Button to add new division
        container.add({
            xtype: 'panel',
            width: 150,
            flex: 0,
            bodyStyle: 'background: #f5f5f5; border: 2px dashed #ccc;',
            layout: {
                type: 'vbox',
                align: 'center',
                pack: 'center'
            },
            items: [{
                xtype: 'button',
                text: '+ Nouvelle Division',
                handler: function() {
                    me.addNewDivision();
                }
            }]
        });
        
        container.updateLayout();
    },
    
    createDivisionPanel: function(divisionId, title, teams) {
        var me = this;
        
        var store = Ext.create('Ext.data.Store', {
            fields: ['id', 'id_equipe', 'nom_equipe', 'club', 'rank_start', 'division'],
            data: teams.map(function(team, index) {
                return {
                    id: team.id,
                    id_equipe: team.id_equipe,
                    nom_equipe: team.nom_equipe,
                    club: team.club,
                    rank_start: index + 1,
                    division: divisionId
                };
            })
        });
        
        var grid = Ext.create('Ext.grid.Panel', {
            store: store,
            divisionId: divisionId,
            hideHeaders: false,
            flex: 1,
            viewConfig: {
                plugins: {
                    ptype: 'gridviewdragdrop',
                    dragGroup: 'ranksDragGroup',
                    dropGroup: 'ranksDragGroup',
                    dragText: '{0} équipe(s) sélectionnée(s)',
                    enableDrag: true,
                    enableDrop: true
                },
                listeners: {
                    drop: function(node, data, dropRec, dropPosition) {
                        me.onTeamDrop(this, data, dropRec, dropPosition);
                    }
                }
            },
            columns: [{
                text: '#',
                width: 35,
                renderer: function(val, meta, record, rowIndex) {
                    return rowIndex + 1;
                }
            }, {
                text: 'Équipe',
                dataIndex: 'nom_equipe',
                flex: 1
            }, {
                text: 'Club',
                dataIndex: 'club',
                flex: 1,
                hidden: true
            }],
            listeners: {
                render: function(grid) {
                    grid.getStore().on('datachanged', function() {
                        me.updateRankNumbers(grid);
                    });
                }
            }
        });
        
        var panel = Ext.create('Ext.panel.Panel', {
            title: title + ' (' + teams.length + ')',
            itemId: 'division_' + divisionId,
            divisionId: divisionId,
            layout: 'fit',
            minWidth: 200,
            flex: 1,
            bodyStyle: divisionId === 'unassigned' ? 'background: #fff3cd;' : '',
            items: [grid],
            tools: divisionId !== 'unassigned' ? [{
                type: 'close',
                tooltip: 'Supprimer cette division (vide)',
                handler: function() {
                    if (grid.getStore().getCount() > 0) {
                        Ext.Msg.alert('Erreur', 'Impossible de supprimer une division non vide. Déplacez d\'abord les équipes.');
                        return;
                    }
                    panel.up('panel').remove(panel);
                }
            }] : []
        });
        
        return panel;
    },
    
    onTeamDrop: function(view, data, dropRec, dropPosition) {
        var me = this;
        var targetGrid = view.up('grid');
        var targetDivision = targetGrid.divisionId;
        
        // Update division for dropped records
        Ext.each(data.records, function(record) {
            record.set('division', targetDivision);
        });
        
        // Update rank numbers for all affected grids
        me.updateAllRankNumbers();
        me.updatePanelTitles();
    },
    
    updateRankNumbers: function(grid) {
        var store = grid.getStore();
        store.each(function(record, index) {
            record.set('rank_start', index + 1);
        });
    },
    
    updateAllRankNumbers: function() {
        var me = this;
        var container = me.down('#divisionsContainer');
        
        container.items.each(function(panel) {
            var grid = panel.down('grid');
            if (grid) {
                me.updateRankNumbers(grid);
            }
        });
    },
    
    updatePanelTitles: function() {
        var me = this;
        var container = me.down('#divisionsContainer');
        
        container.items.each(function(panel) {
            var grid = panel.down('grid');
            if (grid && panel.divisionId) {
                var count = grid.getStore().getCount();
                var titleBase = panel.divisionId === 'unassigned' ? 'Non affectées' : 'Division ' + panel.divisionId;
                panel.setTitle(titleBase + ' (' + count + ')');
            }
        });
    },
    
    addNewDivision: function() {
        var me = this;
        var container = me.down('#divisionsContainer');
        
        // Find the next division number
        var maxDiv = 0;
        container.items.each(function(panel) {
            if (panel.divisionId && panel.divisionId !== 'unassigned') {
                var num = parseInt(panel.divisionId);
                if (!isNaN(num) && num > maxDiv) {
                    maxDiv = num;
                }
            }
        });
        
        var newDivId = String(maxDiv + 1);
        var newPanel = me.createDivisionPanel(newDivId, 'Division ' + newDivId, []);
        
        // Insert before the "add" button
        container.insert(container.items.length - 1, newPanel);
        container.updateLayout();
    },
    
    saveChanges: function() {
        var me = this;
        var container = me.down('#divisionsContainer');
        var updates = [];
        var removals = [];
        
        container.items.each(function(panel) {
            var grid = panel.down('grid');
            if (grid && panel.divisionId) {
                var store = grid.getStore();
                if (panel.divisionId === 'unassigned') {
                    // Equipes dans "non assignées" avec un ID existant = à supprimer
                    store.each(function(record) {
                        var id = record.get('id');
                        if (id && !isNaN(parseInt(id))) {
                            removals.push(parseInt(id));
                        }
                    });
                } else {
                    // Equipes dans une division = à mettre à jour
                    store.each(function(record, index) {
                        updates.push({
                            id: record.get('id'),
                            id_equipe: record.get('id_equipe'),
                            division: panel.divisionId,
                            rank_start: index + 1
                        });
                    });
                }
            }
        });
        
        if (updates.length === 0 && removals.length === 0) {
            Ext.Msg.alert('Info', 'Aucune modification à sauvegarder');
            return;
        }
        
        // Fonction pour sauvegarder les updates
        var saveUpdates = function(callback) {
            if (updates.length === 0) {
                callback();
                return;
            }
            Ext.Ajax.request({
                url: '/rest/action.php/rank/updateRanksBatch',
                method: 'POST',
                params: {
                    code_competition: me.code_competition,
                    updates: Ext.encode(updates)
                },
                success: callback,
                failure: function(response) {
                    var result = Ext.decode(response.responseText);
                    Ext.Msg.alert('Erreur', result.message || 'Erreur lors de la sauvegarde');
                }
            });
        };
        
        // Fonction pour supprimer les équipes retirées
        var removeTeams = function(callback) {
            if (removals.length === 0) {
                callback();
                return;
            }
            var processed = 0;
            Ext.each(removals, function(id) {
                Ext.Ajax.request({
                    url: '/rest/action.php/rank/removeFromDivision',
                    method: 'POST',
                    params: { id: id },
                    success: function() {
                        processed++;
                        if (processed === removals.length) {
                            callback();
                        }
                    },
                    failure: function(response) {
                        var result = Ext.decode(response.responseText);
                        Ext.Msg.alert('Erreur', result.message || 'Erreur lors de la suppression');
                    }
                });
            });
        };
        
        // Exécuter les deux opérations puis recharger
        saveUpdates(function() {
            removeTeams(function() {
                Ext.Msg.alert('Succès', 'Classements mis à jour');
                me.loadDivisions(me.code_competition);
            });
        });
    }
});
