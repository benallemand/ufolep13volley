// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Calendrier de la home alimenté depuis la base (issue #253)
 *
 *  1. Setup : insérer trois événements connus dans la saison en cours
 *     (ponctuel avec heure, période, ponctuel « journée entière »).
 *  2. Ouvrir la home et vérifier que le calendrier les affiche, ce qui prouve
 *     que les données viennent bien de la base et non du tableau supprimé
 *     de Home.js.
 *  3. Vérifier la régression des fériés : un ponctuel à minuit ne doit pas
 *     s'afficher « à 00:00 ».
 *  4. Teardown : supprimer les événements de test.
 */

test.describe('Issue #253 — Calendrier de la home en base', () => {
    let season = null;
    let pointLabel = null;
    let periodLabel = null;
    let alldayLabel = null;

    test.beforeAll(async ({ request }) => {
        const res = await request.get('/e2e/helpers/calendar_events_setup.php');
        const body = await res.json();
        if (body.error) throw new Error(`Setup failed: ${body.error}`);
        expect(res.status()).toBe(200);
        expect(body.success).toBe(true);
        season = body.season;
        pointLabel = body.point_label;
        periodLabel = body.period_label;
        alldayLabel = body.allday_label;
    });

    test.afterAll(async ({ request }) => {
        await request.get('/e2e/helpers/calendar_events_teardown.php');
    });

    /**
     * Les événements de test sont posés en novembre. Selon la date d'exécution
     * ce mois peut être passé, donc masqué par défaut : on déplie d'abord.
     */
    async function openCalendarWithPastMonths(page) {
        await page.goto('/pages/home.html#/home');
        await expect(page.getByText(`calendrier ${season}`)).toBeVisible({ timeout: 10000 });

        const toggle = page.getByRole('button', { name: /Afficher les mois passés/ });
        if (await toggle.isVisible().catch(() => false)) {
            await toggle.click();
        }
    }

    test('le calendrier affiche les événements lus en base', async ({ page }) => {
        await openCalendarWithPastMonths(page);

        await expect(page.getByText(pointLabel).first()).toBeVisible({ timeout: 10000 });
        await expect(page.getByText(periodLabel).first()).toBeVisible();

        await page.screenshot({
            path: 'test-results/issue-253/calendrier_depuis_la_base.png',
            fullPage: true
        });
    });

    test('un événement ponctuel affiche son heure', async ({ page }) => {
        await openCalendarWithPastMonths(page);

        const entry = page.locator('div.rounded.border-l-4', { hasText: pointLabel }).first();
        await expect(entry).toBeVisible({ timeout: 10000 });
        await expect(entry).toContainText('19:30');
    });

    test('un événement sur la journée entière n\'affiche pas 00:00', async ({ page }) => {
        await openCalendarWithPastMonths(page);

        const entry = page.locator('div.rounded.border-l-4', { hasText: alldayLabel }).first();
        await expect(entry).toBeVisible({ timeout: 10000 });
        await expect(entry).not.toContainText('00:00');

        await page.screenshot({
            path: 'test-results/issue-253/journee_entiere_sans_heure.png',
            fullPage: true
        });
    });
});
