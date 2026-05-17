import { createRulesComponent } from './RulesTemplate.js';

export default createRulesComponent({
    title: 'Règlement championnat masculin',
    subtitle: '6 x 6 (mixte possible)',
    lastUpdate: '15 mai 2012',
    articles: [
        {
            num: 1,
            title: 'Définition de la compétition',
            content: [
                'La CTSD UFOLEP Volley des Bouches du Rhône organise un championnat masculin 6 x 6 (mixte possible) couronnant un champion départemental chaque saison.',
                'Le format garantit une compétition ouverte à toutes les équipes licenciées dans le département.',
            ],
        },
        {
            num: 2,
            title: "Inscription d'une équipe",
            content: [
                'Chaque équipe doit compter au moins six joueurs et peut être mixte. Ajout de nouveaux joueurs autorisé à tout moment après notification au responsable des classements.',
                'Les nouvelles équipes débutent dans la division inférieure ; la CTSD peut organiser un tournoi pour attribuer les places vacantes en divisions supérieures.',
                'Si cinq joueurs ou plus d’une équipe se réinscrivent sous un autre nom, ils peuvent conserver la place sportive initiale.',
            ],
        },
        {
            num: 3,
            title: 'Règles de jeu',
            content: [
                'Arbitrage, feuille de match et règles techniques se réfèrent au règlement FFVB et au règlement général UFOLEP Volley 13.',
            ],
        },
        {
            num: 4,
            title: 'Organisation du championnat',
            content: [
                'Divisions de niveaux disputées en deux demi-saisons ; montée/descente de deux équipes par division (hors dernière).',
                'Le titre départemental est attribué à l’issue de la deuxième demi-saison.',
                'La CTSD peut ajuster les montées ou maintiens pour rééquilibrer les effectifs et organise un tournoi de sélection si nécessaire.',
            ],
        },
        {
            num: 5,
            title: 'Filet',
            content: ['Hauteur réglementaire du filet fixée à 2,43 m.'],
        },
        {
            num: 6,
            title: 'Prêt de joueur',
            content: ['Application de la règle de prêt définie dans le règlement général UFOLEP Volley 13 pour éviter les forfaits.'],
        },
        {
            num: 7,
            title: 'Inscription au championnat',
            content: [
                'Engagement auprès de la CTSD avant le 30 septembre pour la première demi-saison et avant le 30 janvier pour la seconde.',
            ],
        },
    ],
});
