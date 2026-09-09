// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Calendrier de la home alimenté depuis la base (issue #253)
 *
 * Réécrit pour la timeline de saison (#290), qui remplace `AnnualCalendar.js` :
 *
 *  - il n'y a plus de bouton « Afficher les mois passés » — la timeline montre
 *    la saison entière, donc plus rien à déplier ;
 *  - un rendez-vous ponctuel n'est plus une ligne de texte mais un **losange**
 *    portant une infobulle : on assertionne sur l'attribut `title`, seul
 *    endroit où son heure apparaît ;
 *  - une période a sa propre **ligne**, dont le libellé est du texte visible.
 *
 * Ce que le test garantit, inchangé depuis #253 : les événements viennent de la
 * base et non d'un tableau codé en dur, un ponctuel affiche son heure, et un
 * événement à minuit ne s'affiche PAS « à 00:00 ».
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

    async function openCalendar(page) {
        await page.goto('/pages/home.html#/home');
        await expect(page.getByText(`calendrier ${season}`)).toBeVisible({ timeout: 10000 });
    }

    test('la timeline affiche les événements lus en base', async ({ page }) => {
        await openCalendar(page);

        // Une période a sa propre ligne, libellée par son intitulé.
        await expect(page.getByText(periodLabel).first()).toBeVisible({ timeout: 10000 });

        // Les ponctuels sont regroupés sur une ligne unique.
        await expect(page.getByText('Réunions et rendez-vous')).toBeVisible();

        await page.screenshot({
            path: 'test-results/issue-253/timeline_depuis_la_base.png',
            fullPage: true
        });
    });

    test('un événement ponctuel affiche son heure dans son infobulle', async ({ page }) => {
        await openCalendar(page);

        const losange = page.locator(`[title*="${pointLabel}"]`).first();
        await expect(losange).toBeVisible({ timeout: 10000 });
        await expect(losange).toHaveAttribute('title', /19:30/);
    });

    test("un événement à minuit n'affiche pas 00:00", async ({ page }) => {
        await openCalendar(page);

        const losange = page.locator(`[title*="${alldayLabel}"]`).first();
        await expect(losange).toBeVisible({ timeout: 10000 });
        await expect(losange).not.toHaveAttribute('title', /00:00/);

        await page.screenshot({
            path: 'test-results/issue-253/journee_entiere_sans_heure.png',
            fullPage: true
        });
    });
});
