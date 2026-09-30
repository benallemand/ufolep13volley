// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Règlement général lu dans Google Docs (issue #342)
 *
 * Setup : un document fictif placé dans le cache (`rules_setup.php`), avec une
 * tentative de XSS. Aucun appel à Google : le test ne dépend ni du réseau ni
 * du contenu réel du document.
 */
test.describe('Issue #342 — règlement général', () => {
    test.beforeEach(async ({ request }) => {
        const res = await request.get('/e2e/helpers/rules_setup.php');
        const body = await res.json();
        expect(body.error, `Setup failed: ${body.error}`).toBeUndefined();
    });

    test.afterEach(async ({ request }) => {
        await request.get('/e2e/helpers/rules_teardown.php');
    });

    test('la page affiche le document, son récapitulatif et ses articles', async ({ page }) => {
        await page.goto('/pages/home.html#/ufolep-rules');
        const rules = page.getByTestId('general-rules');
        await expect(rules.getByTestId('rules-article')).toHaveCount(3, { timeout: 15000 });
        await expect(rules.getByRole('heading', { name: 'Article 2 : Arbitrage E2E' })).toBeVisible();
        await expect(rules.getByTestId('rules-fetched-at')).toContainText('relu le');
        await expect(rules.locator('#article-2 strong')).toHaveText('en gras');
        await expect(rules.locator('#article-3 td').first()).toHaveText('Victoire 3-0');

        // Récapitulatif alphabétique : « Arbitrage » avant « Attribution ».
        await expect(rules.getByTestId('rules-toc').locator('tbody td:first-child'))
            .toHaveText(['Arbitrage E2E', 'Attribution des points E2E', 'Saison sportive E2E']);
        await page.screenshot({ path: 'test-results/issue-342/reglement.png', fullPage: true });
    });

    test('le contenu du document ne peut pas exécuter de script', async ({ page }) => {
        await page.goto('/pages/home.html#/ufolep-rules');
        await expect(page.locator('#article-2')).toBeVisible({ timeout: 15000 });
        expect(await page.evaluate(() => window.__rulesXss)).toBeUndefined();
        await expect(page.locator('#article-2 img, #article-2 script')).toHaveCount(0);
    });

    test('un renvoi du récapitulatif fait défiler sans changer de page', async ({ page }) => {
        await page.goto('/pages/home.html#/ufolep-rules');
        const toc = page.getByTestId('rules-toc');
        await expect(toc).toBeVisible({ timeout: 15000 });
        await toc.getByRole('link', { name: '3', exact: true }).click();
        await expect(page.locator('#article-3')).toBeInViewport();
        expect(page.url()).toContain('#/ufolep-rules');
    });

    test('le document original passe par le serveur, en PDF', async ({ page, request }) => {
        await page.goto('/pages/home.html#/ufolep-rules');
        const lien = page.getByTestId('rules-original');
        await expect(lien).toBeVisible({ timeout: 15000 });
        const href = await lien.getAttribute('href');
        expect(href).toBe('/rest/action.php/rules/getGeneralRulesPdf');
        // Aucune adresse Google dans le règlement : le lien d'édition ne peut pas
        // fuiter (la barre de navigation a ses propres liens, hors sujet ici).
        expect(await page.getByTestId('general-rules').innerHTML()).not.toContain('docs.google.com');

        const pdf = await request.get(href);
        expect(pdf.status()).toBe(200);
        expect(pdf.headers()['content-type']).toContain('application/pdf');
        expect((await pdf.body()).subarray(0, 4).toString()).toBe('%PDF');
    });

    test('sur mobile, la page reste lisible', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 812 });
        await page.goto('/pages/home.html#/ufolep-rules');
        await expect(page.locator('#article-1')).toBeVisible({ timeout: 15000 });
        // Rien du règlement ne dépasse sa colonne (les menus de la barre de
        // navigation, hors sujet ici, ont leur propre débordement).
        const debordants = await page.getByTestId('general-rules').evaluate((root) => {
            const limite = root.getBoundingClientRect().right + 1;
            return [...root.querySelectorAll('*')]
                .filter((el) => el.getBoundingClientRect().right > limite && !el.closest('.overflow-x-auto'))
                .map((el) => el.tagName + (el.id ? '#' + el.id : ''));
        });
        expect(debordants).toEqual([]);
        await page.screenshot({ path: 'test-results/issue-342/reglement-mobile.png', fullPage: false });
    });
});
