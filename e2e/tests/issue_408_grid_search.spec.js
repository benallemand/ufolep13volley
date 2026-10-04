// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Grilles d'administration : recherche sur toute la ligne, taille de
 * page automatique (issue #408)
 *
 *   - la recherche trouve une donnée non affichée : le numéro de licence d'un
 *     joueur (dans le tiroir depuis #308), même tapé avec le préfixe de
 *     département imprimé sur la licence ;
 *   - jusqu'à 500 lignes la grille affiche tout ; au-delà, des pages de 100.
 *
 * Les données sont celles de la base : le joueur est choisi dans la réponse du
 * serveur.
 */

/** Même principe que issue_308 : cookie posé sur `baseURL` avant de naviguer. */
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

test.describe('Issue #408 — recherche et pagination des grilles', () => {

    test('Joueurs : pages de 100, recherche par numéro de licence', async ({ page, request, baseURL }) => {
        test.setTimeout(90000);
        await loginAsAdmin(page, request, baseURL);
        const response = page.waitForResponse((r) => r.url().includes('/player/getPlayers'), { timeout: 60000 });
        await page.goto('/admin/index.html#/players');
        const players = await (await response).json();
        test.skip(players.length <= 500, 'moins de 500 joueurs dans cette base : pas de pagination');

        // Une licence portée par un seul joueur, et qui n'est contenue dans
        // aucune autre donnée : la recherche ne doit trouver que lui.
        const licences = players.map((p) => String(p.num_licence || '').trim()).filter((l) => l.length >= 8);
        const target = players.find((p) => {
            const l = String(p.num_licence || '').trim();
            return l.length >= 8 && licences.filter((x) => x.includes(l)).length === 1;
        });
        test.skip(!target, 'aucun numéro de licence exploitable');

        const rows = page.locator('tbody tr');
        await expect(page.getByTestId('grid-page-size')).toHaveValue('100', { timeout: 30000 });
        await expect(rows).toHaveCount(100);

        const search = page.getByPlaceholder(/Rechercher…/);
        await search.fill('013_' + target.num_licence);
        await expect(page.getByTestId('grid-count')).toContainText(/^\s*[1-3] \//);
        await expect(rows.filter({ hasText: target.nom }).first()).toBeVisible();
        await page.screenshot({ path: 'test-results/issue-408/recherche_licence.png' });

        // Sans préfixe aussi.
        await search.fill(target.num_licence);
        await expect(rows.filter({ hasText: target.nom }).first()).toBeVisible();
    });

    test('Équipes : tout est affiché, sans pagination', async ({ page, request, baseURL }) => {
        test.setTimeout(60000);
        await loginAsAdmin(page, request, baseURL);
        const response = page.waitForResponse((r) => r.url().includes('/team/getTeams'), { timeout: 30000 });
        await page.goto('/admin/index.html#/teams');
        const teams = await (await response).json();
        test.skip(teams.length > 500, 'plus de 500 équipes : la grille pagine');

        await expect(page.getByTestId('grid-page-size')).toHaveValue('0', { timeout: 30000 });
        await expect(page.locator('tbody tr')).toHaveCount(teams.length);
    });
});
