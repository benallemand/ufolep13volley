// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Consultation et suivi de lecture des emails par les responsables d'équipe (issue #221)
 *
 * Scénario standard :
 *  1. Setup : créer un email non lu pour l'équipe du responsable connecté
 *  2. Se connecter en tant que responsable d'équipe
 *  3. Vérifier le badge "messages" dans la navbar indique au moins 1 non lu
 *  4. Naviguer vers /messages et vérifier la liste (message non lu surligné + badge ●)
 *  5. Cliquer sur le message → vérifier l'affichage du détail (sujet, expéditeur, corps, statut Non lu)
 *  6. Cliquer "Marquer comme lu" → vérifier le statut passe à "Lu"
 *  7. Retour à la liste → vérifier que le message n'est plus en gras / le badge ● a disparu
 *  8. Cliquer "Marquer comme non lu" → vérifier que le badge ● réapparaît
 *  9. Teardown : nettoyer les données de test
 */

test.describe('Issue #221 — Messages Team Leader', () => {
    let setupData = null;

    test.beforeEach(async ({ page }) => {
        // Setup : créer session responsable + email de test
        // Le goto() permet à PHP de poser le cookie PHPSESSID via Set-Cookie
        await page.goto('/e2e/helpers/messages_setup.php');
        const body = JSON.parse(await page.locator('body').innerText());
        if (body.error) throw new Error(`Setup failed: ${body.error}`);
        setupData = body;
    });

    test.afterEach(async ({ page }) => {
        await page.request.get('/e2e/helpers/messages_teardown.php');
        setupData = null;
    });

    test('badge messages dans la navbar indique au moins 1 non lu', async ({ page }) => {
        await page.goto('/pages/my_page.html#/messages');
        await page.waitForLoadState('networkidle');

        // Le badge non lu doit être affiché (dans le titre du composant ou la navbar)
        // Le composant affiche "X non lu" en badge-error dans l'en-tête Messages
        const unreadBadge = page.locator('.badge-error').filter({ hasText: /non lu/ });
        await expect(unreadBadge).toBeVisible({ timeout: 10000 });

        await page.screenshot({ path: 'test-results/issue-221/navbar_badge_unread.png' });
    });

    test('liste des messages : message non lu mis en évidence', async ({ page }) => {
        await page.goto('/pages/my_page.html#/messages');
        await page.waitForLoadState('networkidle');

        // La ligne du message de test doit être présente
        const row = page.locator('tr').filter({ hasText: 'E2E_MESSAGES_TEST' });
        await expect(row).toBeVisible({ timeout: 10000 });

        // La ligne doit avoir la classe font-bold (non lu)
        await expect(row).toHaveClass(/font-bold/, { timeout: 5000 });

        // Le badge ● doit être présent dans la ligne
        await expect(row.locator('.badge-error')).toBeVisible();

        await page.screenshot({ path: 'test-results/issue-221/liste_messages_non_lu.png' });
    });

    test('vue détail : affiche corps, participants, statut Lu après ouverture', async ({ page }) => {
        await page.goto('/pages/my_page.html#/messages');
        await page.waitForLoadState('networkidle');

        const row = page.locator('tr').filter({ hasText: 'E2E_MESSAGES_TEST' });
        await expect(row).toBeVisible({ timeout: 10000 });
        // Cliquer ouvre le détail ET marque automatiquement comme lu
        await row.click();

        // Le détail doit afficher le sujet
        await expect(page.locator('.card-title')).toContainText('E2E_MESSAGES_TEST');

        // Le destinataire doit être affiché (champ À)
        await expect(page.locator('.card-body')).toContainText('À :');

        // Le corps doit être présent
        await expect(page.locator('.prose')).toContainText('message de test E2E');

        // Le statut passe à "Lu" automatiquement à l'ouverture
        await expect(page.locator('.card-body .badge-success')).toBeVisible({ timeout: 5000 });

        await page.screenshot({ path: 'test-results/issue-221/detail_message_lu_auto.png' });
    });

    test('marquer lu via liste puis non lu : badge toggle correct', async ({ page }) => {
        await page.goto('/pages/my_page.html#/messages');
        await page.waitForLoadState('networkidle');

        const row = page.locator('tr').filter({ hasText: 'E2E_MESSAGES_TEST' });
        await expect(row).toBeVisible({ timeout: 10000 });

        // Marquer comme lu via le bouton de la liste (sans ouvrir le détail)
        const markReadBtn = row.locator('button', { hasText: 'Marquer lu' });
        await expect(markReadBtn).toBeVisible();
        await markReadBtn.click();

        // La ligne ne doit plus avoir font-bold
        await expect(row).not.toHaveClass(/font-bold/, { timeout: 5000 });
        await expect(row.locator('.badge-error')).not.toBeVisible();

        await page.screenshot({ path: 'test-results/issue-221/liste_messages_lu.png' });

        // Marquer comme non lu
        const markUnreadBtn = row.locator('button', { hasText: 'Marquer non lu' });
        await expect(markUnreadBtn).toBeVisible();
        await markUnreadBtn.click();

        // La ligne repasse en font-bold
        await expect(row).toHaveClass(/font-bold/, { timeout: 5000 });
        await expect(row.locator('.badge-error')).toBeVisible();

        await page.screenshot({ path: 'test-results/issue-221/liste_messages_remarque_non_lu.png' });
    });

    test('tout marquer comme lu : badge disparaît et unreadCount = 0', async ({ page }) => {
        await page.goto('/pages/my_page.html#/messages');
        await page.waitForLoadState('networkidle');

        // Le bouton doit être visible car il y a des non lus
        const markAllBtn = page.locator('button', { hasText: 'Tout marquer comme lu' });
        await expect(markAllBtn).toBeVisible({ timeout: 10000 });
        await markAllBtn.click();

        // Le badge non lu doit disparaître
        await expect(page.locator('.badge-error').filter({ hasText: /non lu/ })).not.toBeVisible({ timeout: 5000 });

        // Le bouton doit disparaître aussi
        await expect(markAllBtn).not.toBeVisible();

        await page.screenshot({ path: 'test-results/issue-221/tout_marque_lu.png' });
    });

    test('détail : marquer non lu depuis le détail puis retour liste', async ({ page }) => {
        await page.goto('/pages/my_page.html#/messages');
        await page.waitForLoadState('networkidle');

        // Ouvrir le détail (auto-marque comme lu)
        const row = page.locator('tr').filter({ hasText: 'E2E_MESSAGES_TEST' });
        await expect(row).toBeVisible({ timeout: 10000 });
        await row.click();

        // Attendre que le détail soit affiché et que le statut soit Lu
        await expect(page.locator('.card-body .badge-success')).toBeVisible({ timeout: 5000 });

        // Marquer comme non lu depuis le détail
        await page.locator('button', { hasText: 'Marquer comme non lu' }).click();
        await expect(page.locator('.card-body .badge-error')).toBeVisible({ timeout: 5000 });

        await page.screenshot({ path: 'test-results/issue-221/detail_remarque_non_lu.png' });

        // Retour liste : la ligne doit repasser en font-bold
        await page.locator('button', { hasText: 'Retour' }).click();
        const rowAfter = page.locator('tr').filter({ hasText: 'E2E_MESSAGES_TEST' });
        await expect(rowAfter).toBeVisible();
        await expect(rowAfter).toHaveClass(/font-bold/, { timeout: 5000 });
        await expect(rowAfter.locator('.badge-error')).toBeVisible();

        await page.screenshot({ path: 'test-results/issue-221/liste_apres_remarque_non_lu.png' });
    });
});
