// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Dropdown Coupes réorganisée en 4 sections (issue #218)
 *
 * Scénario :
 *  1. Ouvrir la page d'accueil
 *  2. Cliquer sur le bouton "Coupes" dans la navbar
 *  3. Vérifier la présence des 4 sections : Tirages, Poules, Qualifiés, Phases finales
 *  4. Prendre un screenshot de preuve
 */

test.describe('Issue #218 — Dropdown Coupes en 4 sections', () => {
    test('la dropdown Coupes affiche les 4 sections attendues', async ({ page }) => {
        await page.goto('/');

        // Ouvrir la dropdown Coupes
        const coupesBtn = page.locator('.navbar .dropdown').filter({ hasText: 'Coupes' }).first();
        await coupesBtn.locator('[role="button"]').click();

        const dropdown = coupesBtn.locator('.dropdown-content');

        // Vérifier les 4 titres de section
        await expect(dropdown.locator('.menu-title', { hasText: 'Tirages' })).toBeVisible();
        await expect(dropdown.locator('.menu-title', { hasText: 'Poules' })).toBeVisible();
        await expect(dropdown.locator('.menu-title', { hasText: 'Qualifiés' })).toBeVisible();
        await expect(dropdown.locator('.menu-title', { hasText: 'Phases finales' })).toBeVisible();

        await page.screenshot({
            path: 'test-results/issue-218/dropdown_coupes_4_sections.png',
            fullPage: false,
        });
    });

    test('la section Tirages contient les liens vers les tirages des deux coupes', async ({ page }) => {
        await page.goto('/');

        const coupesBtn = page.locator('.navbar .dropdown').filter({ hasText: 'Coupes' }).first();
        await coupesBtn.locator('[role="button"]').click();

        const dropdown = coupesBtn.locator('.dropdown-content');

        // Les liens de tirage doivent être présents
        await expect(dropdown.locator('a, [href]', { hasText: /tirage.*Isoardi/i })).toBeVisible();
        await expect(dropdown.locator('a, [href]', { hasText: /tirage.*Khoury/i })).toBeVisible();

        await page.screenshot({
            path: 'test-results/issue-218/section_tirages.png',
            fullPage: false,
        });
    });
});
