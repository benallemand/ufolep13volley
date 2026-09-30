// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Règlements lus dans le dossier Google Drive (issue #342)
 *
 * Setup : un dossier fictif placé dans le cache (`rules_setup.php`) — une
 * saison 2026-2027 avec le règlement général (qui porte une tentative de XSS)
 * et le championnat féminin. Aucun appel à Google : le test ne dépend ni du
 * réseau ni du contenu réel des documents.
 */
test.describe('Issue #342 — règlements', () => {
    test.beforeEach(async ({ request }) => {
        const res = await request.get('/e2e/helpers/rules_setup.php');
        const body = await res.json();
        expect(body.error, `Setup failed: ${body.error}`).toBeUndefined();
    });

    test.afterEach(async ({ request }) => {
        await request.get('/e2e/helpers/rules_teardown.php');
    });

    test('la liste vient du dossier et le règlement général s\'affiche', async ({ page }) => {
        await page.goto('/pages/home.html#/ufolep-rules');
        await expect(page.getByTestId('rules-list').locator('button'))
            .toHaveText([/Règlement général/, /Championnat féminin 4×4/], { timeout: 15000 });

        const rules = page.getByTestId('rules-document');
        await expect(rules.getByTestId('rules-article')).toHaveCount(3);
        await expect(rules.getByTestId('rules-season')).toHaveText('Saison 2026-2027');
        await expect(rules.getByRole('heading', { name: 'Article 2 : Arbitrage E2E' })).toBeVisible();
        await expect(rules.locator('#article-2 strong')).toHaveText('en gras');
        await expect(rules.locator('#article-3 td').first()).toHaveText('Victoire 3-0');
        // Récapitulatif alphabétique : « Arbitrage » avant « Attribution ».
        await expect(rules.getByTestId('rules-toc').locator('tbody td:first-child'))
            .toHaveText(['Arbitrage E2E', 'Attribution des points E2E', 'Saison sportive E2E']);
        await page.screenshot({ path: 'test-results/issue-342/reglement-general.png', fullPage: true });
    });

    test('un autre règlement du dossier s\'ouvre depuis la liste', async ({ page }) => {
        await page.goto('/pages/home.html#/ufolep-rules');
        await page.getByTestId('rules-list').locator('button[data-slug="feminine"]').click({ timeout: 15000 });
        const rules = page.locator('[data-testid="rules-document"][data-slug="feminine"]');
        await expect(rules.getByRole('heading', { name: 'Article 1 : Définition de la compétition E2E' }))
            .toBeVisible();
        await expect(rules.getByTestId('rules-article')).toHaveCount(1);
    });

    test('le contenu d\'un document ne peut pas exécuter de script', async ({ page }) => {
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
        expect(href).toBe('/rest/action.php/rules/getRulesPdf?slug=general');
        // Aucune adresse Google dans les règlements : un lien d'édition ne peut
        // pas fuiter (la barre de navigation a ses propres liens, hors sujet).
        expect(await page.locator('#reglement-content').innerHTML()).not.toMatch(/google\.com/);

        const pdf = await request.get(href);
        expect(pdf.status()).toBe(200);
        expect(pdf.headers()['content-type']).toContain('application/pdf');
        expect((await pdf.body()).subarray(0, 4).toString()).toBe('%PDF');
    });

    test('la page Infos propose les PDF à jour', async ({ page }) => {
        await page.goto('/pages/home.html#/information');
        const liens = page.getByTestId('info-rules-pdf').locator('a');
        await expect(liens).toHaveText(['Règlement général', 'Championnat féminin 4×4'], { timeout: 15000 });
        await expect(liens.first()).toHaveAttribute('href', '/rest/action.php/rules/getRulesPdf?slug=general');
    });

    test('sur mobile, la page reste lisible', async ({ page }) => {
        await page.setViewportSize({ width: 375, height: 812 });
        await page.goto('/pages/home.html#/ufolep-rules');
        await expect(page.locator('#article-1')).toBeVisible({ timeout: 15000 });
        // Rien du règlement ne dépasse sa colonne (les menus de la barre de
        // navigation, hors sujet ici, ont leur propre débordement).
        const debordants = await page.getByTestId('rules-document').evaluate((root) => {
            const limite = root.getBoundingClientRect().right + 1;
            return [...root.querySelectorAll('*')]
                .filter((el) => el.getBoundingClientRect().right > limite && !el.closest('.overflow-x-auto'))
                .map((el) => el.tagName + (el.id ? '#' + el.id : ''));
        });
        expect(debordants).toEqual([]);
        await page.screenshot({ path: 'test-results/issue-342/reglement-mobile.png', fullPage: false });
    });
});
