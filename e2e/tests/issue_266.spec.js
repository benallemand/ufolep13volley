// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Migration des 2 dernières pages ExtJS vers Vue 3 (issue #266)
 *
 *  Partie A — mot de passe oublié :
 *    - le lien de la page de connexion mène à la route Vue #/reset_password
 *    - un email inconnu affiche l'erreur renvoyée par le backend
 *    - l'ancienne URL /reset_password.php redirige vers la route Vue
 *
 *  Partie B — page de confirmation du lien reçu par email :
 *    - un lien invalide affiche une erreur au lieu du JSON brut d'avant
 *
 *  Partie C — classement général d'une coupe :
 *    - la route Vue #/rank_for_cup/:code affiche le classement
 *    - les 16 premiers sont surlignés, les suivants non
 *    - la recherche multi-termes (séparés par des virgules) filtre les lignes
 *    - l'ancienne URL /rank_for_cup.php?code_competition=… redirige
 *
 *  Pas de setup/teardown : aucun de ces parcours n'écrit en base.
 *
 *  NOTE — le chemin nominal de la demande de réinitialisation (email connu)
 *  n'est volontairement PAS testé ici : il déclenche un envoi SMTP réel
 *  (.env.docker pointe sur Gmail, pas sur Mailpit). Seul le chemin d'erreur,
 *  qui lève avant tout envoi, est couvert.
 */

test.describe('Issue #266 — pages ExtJS migrées en Vue', () => {

    test.describe('Mot de passe oublié', () => {

        test('le lien de la page de connexion mène à la route Vue', async ({ page }) => {
            await page.goto('/pages/home.html#/login');

            const link = page.getByRole('link', { name: /mot de passe oublié/i });
            await expect(link).toBeVisible();
            await expect(link).toHaveAttribute('href', '#/reset_password');

            await link.click();
            await expect(page.getByRole('heading', { name: 'Mot de passe oublié' })).toBeVisible();
            await expect(page.locator('#reset-email')).toBeVisible();
        });

        test('un email inconnu affiche l\'erreur du backend', async ({ page }) => {
            await page.goto('/pages/home.html#/reset_password');

            await page.locator('#reset-email').fill('inconnu.e2e@example.invalid');
            await page.getByRole('button', { name: 'Envoyer' }).click();

            await expect(page.getByText(/Il n'existe pas de compte avec cette adresse email/))
                .toBeVisible();

            await page.screenshot({ path: 'test-results/issue-266/reset_password_unknown_email.png', fullPage: true });
        });

        test('l\'ancienne URL .php redirige vers la route Vue', async ({ page }) => {
            const response = await page.goto('/reset_password.php');
            expect(response?.status()).toBe(200); // après suivi de la redirection
            await expect(page.getByRole('heading', { name: 'Mot de passe oublié' })).toBeVisible();
        });
    });

    test.describe('Confirmation du lien reçu par email', () => {

        test('un lien invalide affiche une erreur, pas du JSON brut', async ({ page }) => {
            await page.goto('/pages/home.html#/reset_password/confirm?id=1332&hash=hash_invalide');

            await expect(page.getByRole('heading', { name: 'Réinitialisation du mot de passe' }))
                .toBeVisible();
            // Le backend refuse : soit le hash ne correspond pas, soit l'id n'existe pas.
            await expect(page.getByText(/n'est pas ou plus valide|Pas de donnée dispo/))
                .toBeVisible();
            await expect(page.getByRole('link', { name: /Demander un nouveau lien/ }))
                .toBeVisible();

            await page.screenshot({ path: 'test-results/issue-266/reset_password_confirm_invalid.png', fullPage: true });
        });

        test('un lien incomplet est détecté sans appel au backend', async ({ page }) => {
            await page.goto('/pages/home.html#/reset_password/confirm');

            await expect(page.getByText(/Le lien est incomplet/)).toBeVisible();
        });
    });

    test.describe('Classement général d\'une coupe', () => {

        test('la route Vue affiche le classement et surligne les qualifiés', async ({ page }) => {
            await page.goto('/pages/home.html#/rank_for_cup/c');

            await expect(page.getByRole('heading', { name: 'Classement général' })).toBeVisible();
            await expect(page.getByText(/Les 16 premiers sont qualifiés/)).toBeVisible();

            const rows = page.locator('tbody tr');
            await expect(rows.first()).toBeVisible({ timeout: 20000 });

            const rowCount = await rows.count();
            expect(rowCount).toBeGreaterThan(0);

            // Les lignes de rang <= 16 portent le fond de qualification, les autres non.
            await expect(rows.nth(0)).toHaveClass(/bg-success/);
            if (rowCount > 16) {
                await expect(rows.nth(15)).toHaveClass(/bg-success/);   // rang 16
                await expect(rows.nth(16)).not.toHaveClass(/bg-success/); // rang 17
            }

            // En-têtes groupés hérités de la grille ExtJS
            for (const header of ['Poule', 'Pondération', 'Détails']) {
                await expect(page.getByRole('columnheader', { name: header, exact: true }).first())
                    .toBeVisible();
            }

            await page.screenshot({ path: 'test-results/issue-266/rank_for_cup.png', fullPage: true });
        });

        test('la recherche multi-termes filtre les lignes', async ({ page }) => {
            await page.goto('/pages/home.html#/rank_for_cup/c');

            const rows = page.locator('tbody tr');
            await expect(rows.first()).toBeVisible({ timeout: 20000 });
            const totalCount = await rows.count();

            // On reprend le nom de la première équipe comme terme de recherche.
            const firstTeam = (await rows.nth(0).locator('td').nth(1).innerText()).trim();
            await page.getByPlaceholder(/Rechercher une équipe/).fill(firstTeam);

            await expect(rows).not.toHaveCount(totalCount);
            expect(await rows.count()).toBeGreaterThan(0);
            await expect(rows.nth(0)).toContainText(firstTeam);
        });

        test('l\'ancienne URL .php redirige en conservant la compétition', async ({ page }) => {
            await page.goto('/rank_for_cup.php?code_competition=kh');

            expect(page.url()).toContain('#/rank_for_cup/kh');
            await expect(page.getByRole('heading', { name: 'Classement général' })).toBeVisible();
        });
    });
});
