// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Dropdown Coupes réorganisée en 4 sous-menus collapsibles (issue #218)
 *
 * Scénario :
 *  1. Ouvrir la page d'accueil
 *  2. Cliquer sur le bouton "Coupes" dans la navbar
 *  3. Vérifier la présence des 4 sous-menus (Tirages, Poules, Qualifiés, Phases finales)
 *  4. Vérifier que les sous-menus sont fermés par défaut
 *  5. Ouvrir chaque sous-menu et vérifier son contenu
 *  6. Vérifier que les poules sont affichées en grille de numéros compacts
 */

test.describe('Issue #218 — Dropdown Coupes en 4 sous-menus collapsibles', () => {
    test('les 4 sous-menus sont présents et fermés par défaut', async ({ page }) => {
        await page.goto('/');

        const coupesBtn = page.locator('.navbar .dropdown').filter({ hasText: 'Coupes' }).first();
        await coupesBtn.locator('[role="button"]').click();

        const dropdown = coupesBtn.locator('.dropdown-content');

        // Les 4 summary doivent être visibles
        await expect(dropdown.locator('summary', { hasText: 'Tirages' })).toBeVisible();
        await expect(dropdown.locator('summary', { hasText: 'Poules' })).toBeVisible();
        await expect(dropdown.locator('summary', { hasText: 'Qualifiés' })).toBeVisible();
        await expect(dropdown.locator('summary', { hasText: 'Phases finales' })).toBeVisible();

        // Les <details> ne doivent pas avoir l'attribut open par défaut
        const details = dropdown.locator('details');
        const count = await details.count();
        for (let i = 0; i < count; i++) {
            await expect(details.nth(i)).not.toHaveAttribute('open');
        }

        await page.screenshot({
            path: 'test-results/issue-218/dropdown_collapsed.png',
            fullPage: false,
        });
    });

    test('le sous-menu Tirages contient les liens vers les deux coupes', async ({ page }) => {
        await page.goto('/');

        const coupesBtn = page.locator('.navbar .dropdown').filter({ hasText: 'Coupes' }).first();
        await coupesBtn.locator('[role="button"]').click();

        const dropdown = coupesBtn.locator('.dropdown-content');
        const tiragesDetails = dropdown.locator('details').filter({
            has: page.locator('summary', { hasText: 'Tirages' }),
        });
        await tiragesDetails.locator('summary').click();

        await expect(tiragesDetails.locator('a', { hasText: 'Coupe Isoardi' })).toBeVisible();
        await expect(tiragesDetails.locator('a', { hasText: 'Coupe Khoury Hanna' })).toBeVisible();

        await page.screenshot({
            path: 'test-results/issue-218/submenu_tirages.png',
            fullPage: false,
        });
    });

    test('le sous-menu Poules affiche des numéros compacts en grille', async ({ page }) => {
        await page.goto('/');

        const coupesBtn = page.locator('.navbar .dropdown').filter({ hasText: 'Coupes' }).first();
        await coupesBtn.locator('[role="button"]').click();

        const dropdown = coupesBtn.locator('.dropdown-content');
        await dropdown.locator('summary', { hasText: 'Poules' }).click();

        // La grille doit contenir des boutons avec des numéros courts (pas de texte long)
        const grid = dropdown.locator('.flex.flex-wrap');
        await expect(grid.first()).toBeVisible();

        const buttons = grid.first().locator('.btn-xs');
        const btnCount = await buttons.count();
        expect(btnCount).toBeGreaterThan(0);

        // Vérifier que le texte est court (numéro seul, pas "Coupe Isoardi — poule 1")
        const firstBtnText = await buttons.first().textContent();
        expect(firstBtnText?.trim().length).toBeLessThan(5);

        await page.screenshot({
            path: 'test-results/issue-218/submenu_poules_grid.png',
            fullPage: false,
        });
    });
});
