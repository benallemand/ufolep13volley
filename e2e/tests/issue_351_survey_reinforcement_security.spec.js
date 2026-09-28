// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Sondages lisibles sans connexion, injections SQL dans la recherche de
 * renforts (issue #351)
 *
 * `matchmgr/get_survey` était public : sans `id_match`, il livrait à n'importe
 * qui tous les sondages, commentaires et logins compris.
 * `matchmgr/getReinforcementPlayers` interpolait `id_match` et la recherche
 * dans le SQL.
 *
 * Les règles d'accès fines (match de sa propre équipe, sondage d'un autre
 * compte) sont couvertes par `unit_tests/SurveyAndReinforcementSecurityTest.php` ;
 * ici on vérifie la frontière HTTP et que l'admin garde ses usages.
 */

/** Voir `issue_308_detail_drawer.spec.js` : cookie posé avant toute navigation. */
async function loginAsAdmin(page, request, baseURL) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status(), 'Le helper de session admin doit répondre 200').toBe(200);
    const { session_id } = await res.json();
    expect(session_id).toBeTruthy();
    await page.context().addCookies([{
        name: 'PHPSESSID',
        value: session_id,
        url: baseURL || 'http://localhost',
    }]);
}

test.describe('Issue #351 — sondages et renforts', () => {

    test('un visiteur non connecté ne lit plus aucun sondage', async ({ playwright }) => {
        const ctx = await playwright.request.newContext();
        try {
            for (const url of ['/rest/action.php/matchmgr/get_survey',
                '/rest/action.php/matchmgr/get_survey?id_match=1']) {
                const res = await ctx.get(url, { maxRedirects: 0 });
                expect(res.status(), `${url} doit exiger une connexion`).toBe(403);
                expect(await res.text()).not.toContain('"comment"');
            }
        } finally {
            await ctx.dispose();
        }
    });

    test("l'admin garde la liste des sondages et la recherche de renforts", async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        const api = page.request;

        // Écran admin des sondages : même route, sans id_match.
        const liste = await api.get('/rest/action.php/matchmgr/get_survey');
        expect(liste.status()).toBe(200);
        const sondages = await liste.json();
        expect(Array.isArray(sondages)).toBe(true);
        test.skip(!sondages.length, 'aucun sondage dans cette base');
        const id_match = sondages[0].id_match;

        const chercher = (query, id = id_match) => api.get('/rest/action.php/matchmgr/getReinforcementPlayers',
            { params: { id_match: String(id), query } });

        // Une recherche ordinaire répond.
        const ordinaire = await chercher('mart');
        expect(ordinaire.status()).toBe(200);
        expect(Array.isArray(await ordinaire.json())).toBe(true);

        // Concaténée, cette charge rendait le filtre toujours vrai.
        const charge = await chercher("zzz' OR '1'='1");
        expect(charge.status()).toBe(200);
        expect(await charge.json()).toEqual([]);

        // `%` n'est plus un joker : il ne ramène pas tous les joueurs.
        const joker = await chercher('%%%%');
        expect(joker.status()).toBe(200);
        expect(await joker.json()).toEqual([]);

        // Un identifiant de match piégé est refusé, sans erreur SQL renvoyée.
        const piege = await chercher('mart', `${id_match} OR 1=1`);
        expect(piege.ok()).toBe(false);
        const corps = await piege.text();
        expect(corps).toContain('Identifiant de match invalide');
        expect(corps).not.toMatch(/SQL|syntax/i);
    });
});
