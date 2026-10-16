// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — l'API REST répond en JSON déclaré comme tel (issue #433).
 *
 * Servi en `text/html`, un message d'erreur ou une donnée reprenant du HTML de
 * la requête aurait été interprété par le navigateur (XSS par réflexion).
 */
test.describe('Issue #433 — réponses JSON de l\'API', () => {
    test('succès et erreur sont en application/json, nosniff, sans balise brute', async ({ request }) => {
        const ok = await request.get('/rest/action.php/team/getActiveTeams');
        expect(ok.ok(), await ok.text()).toBe(true);
        expect(ok.headers()['content-type']).toContain('application/json');
        expect(ok.headers()['x-content-type-options']).toBe('nosniff');

        const ko = await request.get('/rest/action.php/team/getTeam', {
            params: { id: '<svg onload=alert(1)>' },
        });
        expect(ko.ok()).toBe(false);
        expect(ko.headers()['content-type']).toContain('application/json');
        expect(ko.headers()['x-content-type-options']).toBe('nosniff');
        const body = await ko.text();
        expect(body).not.toContain('<');
        expect(JSON.parse(body).success).toBe(false);
    });
});
