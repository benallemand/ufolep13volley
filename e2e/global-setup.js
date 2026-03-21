const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

const SQL_FILES = [
    process.env.SQL_HUITIEMES_KF || path.join(__dirname, '../ufolep13volley_python/calendar-agent/insert_huitiemes_kf.sql'),
    process.env.SQL_HUITIEMES_CF || path.join(__dirname, '../ufolep13volley_python/calendar-agent/insert_huitiemes_cf.sql'),
];

async function globalSetup() {
    const connection = await mysql.createConnection({
        host: process.env.DB_SERVER || 'host.docker.internal',
        port: parseInt(process.env.DB_PORT || '3306'),
        user: process.env.DB_USER || 'root',
        password: process.env.DB_PASSWORD || 'test',
        database: process.env.DB_NAME || 'ufolep_13volley',
        multipleStatements: true,
    });

    try {
        for (const sqlFile of SQL_FILES) {
            if (!fs.existsSync(sqlFile)) {
                console.warn(`[global-setup] Fichier SQL introuvable, ignoré : ${sqlFile}`);
                continue;
            }
            const sql = fs.readFileSync(sqlFile, 'utf8');
            await connection.query(sql);
            console.log(`[global-setup] SQL exécuté : ${path.basename(sqlFile)}`);
        }
    } finally {
        await connection.end();
    }
}

module.exports = globalSetup;
