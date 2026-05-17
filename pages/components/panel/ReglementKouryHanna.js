import { createRulesComponent } from './RulesTemplate.js';

export default createRulesComponent({
    title: 'Règlement coupe Koury Hanna',
    subtitle: '4 x 4 mixte',
    lastUpdate: '15 mai 2012',
    articles: [
        {
            num: 1,
            title: 'Définition de la compétition',
            content: [
                'La CTSD UFOLEP Volley des Bouches du Rhône organise une coupe 4 x 4 mixte attribuant un titre départemental annuel.',
                'Elle honore la mémoire de Koury Hanna, joueur de l’ECSM disparu en 2010.',
            ],
        },
        {
            num: 2,
            title: "Inscription d'une équipe",
            content: [
                'Équipe composée de 4 joueurs avec au moins deux joueuses en permanence sur le terrain ; composition figée pour toute la coupe.',
                'Possibilité de regrouper des licenciés issus de deux clubs différents ; match à effectif réduit autorisé à condition d’aligner deux joueuses.',
            ],
        },
        {
            num: 3,
            title: 'Règles de jeu',
            content: [
                'Arbitrage, feuilles de match et règles techniques suivent le règlement FFVB et le règlement général UFOLEP Volley 13 (version 4x4).',
            ],
        },
        {
            num: 4,
            title: 'Organisation de la coupe',
            content: [
                'Phase de poules pendant l’inter-saison du championnat ; phase finale disputée après la seconde demi-saison.',
            ],
        },
        {
            num: 5,
            title: 'Rotation',
            content: [
                'Les joueurs se succèdent au service ; la rotation n’est obligatoire qu’au moment du service.',
                'Postes fixes autorisés tant que l’ordre de service reste inchangé pour le set.',
            ],
        },
        {
            num: 6,
            title: 'Zones',
            content: ['Pas de distinction zone avant/arrière : attaques possibles depuis toutes les zones.'],
        },
        {
            num: 7,
            title: 'Libéro',
            content: ['Aucun libéro autorisé.'],
        },
        {
            num: 8,
            title: 'Handicap',
            content: ['Aucun handicap de points (contrairement à la coupe Isoardi).'],
        },
        {
            num: 9,
            title: 'Filet',
            content: ['Hauteur de filet fixée à 2,35 m.'],
        },
        {
            num: 10,
            title: 'Prêt de joueur/se',
            content: ['Le prêt de joueur(se) est interdit ; seuls les licenciés inscrits sur la fiche officielle peuvent jouer.'],
        },
        {
            num: 11,
            title: 'Pénétrations et filet',
            content: ['Application des règles FFVB 2010 pour les pénétrations et le contact filet.'],
        },
        {
            num: 12,
            title: 'Inscription à la coupe',
            content: [
                'Inscription à signaler à la CTSD au plus tard deux mois avant l’inter-saison (fin novembre).',
            ],
        },
        {
            num: 13,
            title: 'Remplacements',
            content: ['Chaque joueur(se) peut être remplacé(e) au cours d’un set et reprendre sa place, dans la limite de quatre remplacements.'],
        },
    ],
});
