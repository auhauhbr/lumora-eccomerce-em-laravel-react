const fs = require('node:fs');
const path = require('node:path');

const caminhoManifest = path.join(process.cwd(), 'public', 'build', 'manifest.json');
const entradaLaravel = 'resources/js/app.tsx';

if (!fs.existsSync(caminhoManifest)) {
    process.exit(0);
}

const manifest = JSON.parse(fs.readFileSync(caminhoManifest, 'utf8'));

if (!manifest[entradaLaravel]) {
    const chaveEncontrada = Object.keys(manifest).find((chave) =>
        chave.replaceAll('\\', '/').endsWith(entradaLaravel),
    );

    if (chaveEncontrada) {
        manifest[entradaLaravel] = {
            ...manifest[chaveEncontrada],
            src: entradaLaravel,
        };
    }
}

fs.writeFileSync(caminhoManifest, `${JSON.stringify(manifest, null, 2)}\n`);
