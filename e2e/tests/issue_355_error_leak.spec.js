// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * E2E — Injections SQL des comptes connectés et erreurs renvoyées au client
 * (issue #355)
 *
 * Les helpers internes lient désormais leurs paramètres, et `rest/action.php`
 * ne renvoie plus ni message MySQL brut, ni errno en code HTTP, ni signature
 * de méthode.
 */

/** Voir `issue_308_detail_drawer.spec.js` : cookie posé avant toute navigation. */
async function loginAsAdmin(page, request, baseURL) {
    const res = await request.get('/e2e/helpers/admin_session.php');
    expect(res.status(), 'Le helper de session admin doit répondre 200').toBe(200);
    const { session_id } = await res.json();
    expect(session_id).toBeTruthy();
    await page.context().addCookies([{
        name: 'PHPSESSID',
        value: session_id,
        url: baseURL || 'http://localhost',
    }]);
}

/** Rien de ce qui trahit la base ou le code ne doit sortir. */
function expectNoLeak(body) {
    expect(body).not.toMatch(/SQL syntax|mysqli|Duplicate entry|Unknown column|Table '|Stack trace|\.php/i);
}

test.describe('Issue #355 — erreurs sans fuite', () => {

    test('un identifiant piégé est refusé par la validation, pas par MySQL', async ({ page, request, baseURL }) => {
        await loginAsAdmin(page, request, baseURL);
        const res = await page.request.get('/rest/action.php/matchmgr/getMatchPlayers',
            { params: { id_match: '1 OR 1=1' } });
        expect(res.ok()).toBe(false);
        const body = await res.text();
        expect(body).toContain('Identifiant de match invalide');
        expectNoLeak(body);
    });

    test('un paramètre inconnu donne 400 sans exposer la signature', async ({ playwright }) => {
        const ctx = await playwright.request.newContext();
        try {
            const res = await ctx.get('/rest/action.php/commission/get?inconnu=1');
            expect(res.status()).toBe(400);
            const body = await res.text();
            expect(JSON.parse(body).message).toBe('Paramètres invalides pour cette action !');
            expect(body).not.toContain('inconnu');
            expectNoLeak(body);
        } finally {
            await ctx.dispose();
        }
    });
});
