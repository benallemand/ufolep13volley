// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Refonte mobile-first de la page d'accueil (issue #230)
 *
 *  1. Setup : créer un VRAI match programmé aujourd'hui (helper PHP qui recopie
 *     les FK d'un match existant pour qu'il remonte dans matchs_view).
 *  2. Ouvrir la home et vérifier que l'encart "Matchs du jour" apparaît avec un
 *     lien vers le live score du match créé.
 *  3. Vérifier que les dernières nouvelles sont repliées et se déplient au tap.
 *  4. Teardown : supprimer le match de test.
 */

test.describe('Issue #230 — Page d\'accueil mobile-first', () => {
    let codeMatch = null;

    test.beforeAll(async ({ request }) => {
        const res = await request.get('/e2e/helpers/today_matches_setup.php');
        const body = await res.json();
        if (body.error) throw new Error(`Setup failed: ${body.error}`);
        expect(res.status()).toBe(200);
        expect(body.success).toBe(true);
        codeMatch = body.code_match;
    });

    test.afterAll(async ({ request }) => {
        await request.get('/e2e/helpers/today_matches_teardown.php');
        codeMatch = null;
    });

    test('l\'encart "Matchs du jour" affiche le match du jour avec un lien live', async ({ page }) => {
        await page.goto('/pages/home.html#/home');

        await expect(page.getByText('Matchs du jour')).toBeVisible({ timeout: 10000 });

        const liveLink = page.locator(`a[href="/live.html?id_match=${codeMatch}"]`);
        await expect(liveLink).toBeVisible();
        await expect(liveLink).toContainText('Live score');
    });

    test('les dernières nouvelles sont repliées et se déplient au tap', async ({ page }) => {
        await page.goto('/pages/home.html#/home');

        await expect(page.getByText('dernières nouvelles')).toBeVisible({ timeout: 10000 });

        // Le setup a seedé au moins une nouvelle : on attend que le bouton (titre)
        // soit rendu par le fetch async.
        const firstNews = page.locator('button[aria-expanded]').first();
        await expect(firstNews).toBeVisible({ timeout: 10000 });

        // Replié par défaut
        await expect(firstNews).toHaveAttribute('aria-expanded', 'false');
        // Déplié au tap
        await firstNews.click();
        await expect(firstNews).toHaveAttribute('aria-expanded', 'true');
        await expect(page.locator('.prose').first()).toBeVisible();
    });
});
