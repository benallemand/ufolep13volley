// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Injections SQL sans connexion (issue #354)
 *
 * Le routeur REST passe chaque paramètre de la requête comme argument de la
 * méthode : `club/get?query=…` devenait une clause WHERE libre, sans connexion.
 * Le garde `rest/raw_sql_guard.php` refuse désormais ces arguments SQL bruts,
 * ainsi que les clés numériques (arguments positionnels).
 */

test.describe('Issue #354 — injections SQL publiques', () => {

    test('les arguments SQL bruts et positionnels sont refusés', async ({ playwright }) => {
        const ctx = await playwright.request.newContext();
        try {
            for (const url of [
                '/rest/action.php/commission/get?query=1=0',
                '/rest/action.php/commission/get?0=1=0',
                '/rest/action.php/competition/getCompetitions?query=1=0',
                '/rest/action.php/commission/get?bindings[]=x',
            ]) {
                const res = await ctx.get(url);
                expect(res.status(), url).toBe(400);
            }
        } finally {
            await ctx.dispose();
        }
    });

    test('club/get est réservé à l\'admin, la liste publique ne donne que le nom', async ({ playwright }) => {
        const ctx = await playwright.request.newContext();
        try {
            const complet = await ctx.get('/rest/action.php/club/get', { maxRedirects: 0 });
            expect(complet.status()).toBe(403);

            const liste = await ctx.get('/rest/action.php/club/getClubList');
            expect(liste.status()).toBe(200);
            const clubs = await liste.json();
            expect(clubs.length).toBeGreaterThan(0);
            for (const club of clubs) {
                expect(Object.keys(club).sort()).toEqual(['id', 'nom']);
            }
        } finally {
            await ctx.dispose();
        }
    });

    test('team/getTeam et rank/getDivisionsFromCompetition neutralisent les charges', async ({ playwright }) => {
        const ctx = await playwright.request.newContext();
        try {
            const equipe = await ctx.get('/rest/action.php/team/getTeam?id=' + encodeURIComponent('1 OR 1=1'));
            expect(equipe.ok()).toBe(false);
            expect(await equipe.text()).toContain("Identifiant d'\\u00e9quipe invalide");

            const charge = await ctx.get('/rest/action.php/rank/getDivisionsFromCompetition',
                { params: { code_competition: "zz' OR '1'='1" } });
            expect(charge.status()).toBe(200);
            expect(await charge.json()).toEqual([]);
        } finally {
            await ctx.dispose();
        }
    });

    test('les usages publics légitimes répondent toujours', async ({ playwright }) => {
        const ctx = await playwright.request.newContext();
        try {
            // Le seed de CI n'a aucun membre de commission : on vérifie que
            // l'appel passe le garde et rend une liste, pas qu'elle est remplie.
            const commission = await ctx.get('/rest/action.php/commission/get');
            expect(commission.status()).toBe(200);
            expect(Array.isArray(await commission.json())).toBe(true);

            const competitions = await ctx.get('/rest/action.php/competition/getCompetitions');
            expect(competitions.status()).toBe(200);
            expect((await competitions.json()).length).toBeGreaterThan(0);
        } finally {
            await ctx.dispose();
        }
    });
});
