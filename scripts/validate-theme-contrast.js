#!/usr/bin/env node
/**
 * Validador WCAG de contraste — paleta LabHub (claro + escuro).
 * Uso: node scripts/validate-theme-contrast.js
 * Requer ratio >= 4.5 para texto normal (AA).
 */

const PAIRS = {
    light: {
        bg: '#f8f9fa',
        checks: [
            ['Título verde', '#00734F', '#f8f9fa'],
            ['Título roxo', '#421B71', '#f8f9fa'],
            ['Badge fixa', '#ffffff', '#5b21b6'],
            ['Badge avulsa', '#ffffff', '#c2410c'],
            ['Badge pendente', '#1c1917', '#ca8a04'],
            ['Texto body', '#212529', '#ffffff'],
        ],
    },
    dark: {
        bg: '#121212',
        checks: [
            ['Título verde', '#86efac', '#121212'],
            ['Título roxo', '#ddd6fe', '#121212'],
            ['Badge fixa', '#ffffff', '#7c3aed'],
            ['Badge avulsa', '#ffffff', '#c2410c'],
            ['Badge pendente', '#422006', '#facc15'],
            ['Texto body', '#e0e0e0', '#1e1e1e'],
            ['Roxo antigo (FALHA)', '#421B71', '#121212'],
        ],
    },
};

function luminance(hex) {
    const rgb = hex.replace('#', '').match(/.{2}/g).map((x) => {
        const c = parseInt(x, 16) / 255;
        return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    });
    return 0.2126 * rgb[0] + 0.7152 * rgb[1] + 0.0722 * rgb[2];
}

function contrast(fg, bg) {
    const l1 = luminance(fg);
    const l2 = luminance(bg);
    const lighter = Math.max(l1, l2);
    const darker = Math.min(l1, l2);
    return (lighter + 0.05) / (darker + 0.05);
}

let failed = 0;
const MIN = 4.5;

for (const [mode, { checks }] of Object.entries(PAIRS)) {
    console.log(`\n=== Modo ${mode.toUpperCase()} (mín. ${MIN}:1) ===`);
    for (const [label, fg, bg] of checks) {
        const ratio = contrast(fg, bg);
        const ok = ratio >= MIN;
        const icon = ok ? '✓' : '✗';
        console.log(`${icon} ${label.padEnd(22)} ${ratio.toFixed(2)}:1  ${fg} sobre ${bg}`);
        if (!ok) failed++;
    }
}

console.log(failed === 0 ? '\n✓ Todas as combinações passaram no AA.\n' : `\n✗ ${failed} combinação(ões) abaixo do WCAG AA.\n`);
process.exit(failed > 0 ? 1 : 0);
