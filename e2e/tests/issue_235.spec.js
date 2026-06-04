// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Afficher le score final si renseigné (issue #235)
 *
 * Setup : deux matchs du jour, l'un AVEC score final (3-0), l'autre SANS.
 *
 *  Partie A (home, encart "Matchs du jour") :
 *    - match avec score   → affiche "3 - 0"
 *    - match sans score   → affiche "vs", pas de score
 *
 *  Partie B (live.html) :
 *    - match avec score   → bouton "Passer en mode scoreur" masqué + "Résultat final"
 *    - match sans score   → bouton visible, pas de "Résultat final"
 */

test.describe('Issue #235 — score final (Matchs du jour + live.html)', () => {
    let codeScored = null;
    let codeUnscored = null;

    test.beforeAll(async ({ request }) => {
        const res = await request.get('/e2e/helpers/final_score_setup.php');
        const body = await res.json();
        if (body.error) throw new Error(`Setup failed: ${body.error}`);
        expect(res.status()).toBe(200);
        expect(body.success).toBe(true);
        codeScored = body.code_scored;
        codeUnscored = body.code_unscored;
    });

    test.afterAll(async ({ request }) => {
        await request.get('/e2e/helpers/final_score_teardown.php');
        codeScored = null;
        codeUnscored = null;
    });

    test('Matchs du jour : score final affiché seulement si renseigné', async ({ page }) => {
        await page.goto('/pages/home.html#/home');
        await expect(page.getByText('Matchs du jour')).toBeVisible({ timeout: 10000 });

        const scoredCard = page.locator(`a[href="/live.html?id_match=${codeScored}"]`);
        const unscoredCard = page.locator(`a[href="/live.html?id_match=${codeUnscored}"]`);

        await expect(scoredCard).toBeVisible();
        await expect(unscoredCard).toBeVisible();

        // Match avec score : affiche "3 - 0" et pas "vs"
        await expect(scoredCard).toContainText('3 - 0');
        await expect(scoredCard).not.toContainText('vs');

        // Match sans score : affiche "vs" et aucun score
        await expect(unscoredCard).toContainText('vs');
        await expect(unscoredCard).not.toContainText('3 - 0');

        await page.screenshot({ path: 'test-results/issue-235/home_today_matches_score.png', fullPage: true });
    });

    test('live.html (match avec score) : bouton scoreur masqué + résultat final', async ({ page }) => {
        await page.goto(`/live.html?id_match=${codeScored}`);

        // Le score final est affiché
        await expect(page.getByText('Résultat final')).toBeVisible({ timeout: 10000 });

        // Le bouton "Passer en mode scoreur" est masqué
        await expect(page.getByRole('button', { name: /Passer en mode scoreur/ })).toHaveCount(0);

        await page.screenshot({ path: 'test-results/issue-235/live_scored.png', fullPage: true });
    });

    test('live.html (match sans score) : bouton scoreur visible, pas de résultat final', async ({ page }) => {
        await page.goto(`/live.html?id_match=${codeUnscored}`);

        // Le bouton "Passer en mode scoreur" est visible
        await expect(page.getByRole('button', { name: /Passer en mode scoreur/ })).toBeVisible({ timeout: 10000 });

        // Aucun "Résultat final"
        await expect(page.getByText('Résultat final')).toHaveCount(0);

        await page.screenshot({ path: 'test-results/issue-235/live_unscored.png', fullPage: true });
    });
});
