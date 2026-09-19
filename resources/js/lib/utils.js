import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

export function money(value) {
    return new Intl.NumberFormat('fr-MA', {
        style: 'currency',
        currency: 'MAD',
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
}

export function number(value) {
    return new Intl.NumberFormat('fr-MA', {
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}

// Shows a PDF in a same-page overlay and triggers the browser's print dialog on it.
//
// Three earlier approaches were tried and dropped, each for a distinct reason:
// - A hidden/off-screen iframe printed blank pages — the PDF renderer needs to be
//   genuinely on-screen to paint reliably before print.
// - Opening a blank popup and setting its `.location` to a blob URL afterward hit
//   Chrome's "Failed to load PDF document" error — a popup navigated to a blob URL
//   after the fact isn't reliably able to resolve it.
// - Fetching synchronously via XHR to preserve the click's user-gesture (needed for
//   window.open) doesn't work either — browsers refuse responseType 'blob' on
//   synchronous XHR outright (InvalidAccessError).
// A same-page overlay sidesteps all of it: no new window, so no popup blocker and no
// cross-window blob URL handoff, and the iframe is actually rendered on screen.
export async function printPdf(url) {
    const openFallback = () => window.open(url, '_blank', 'noopener,noreferrer');

    let blobUrl;
    try {
        const response = await fetch(url, { credentials: 'same-origin' });
        if (!response.ok) throw new Error('PDF fetch failed');
        const blob = await response.blob();
        blobUrl = URL.createObjectURL(blob.type === 'application/pdf' ? blob : new Blob([blob], { type: 'application/pdf' }));
    } catch {
        openFallback();
        return;
    }

    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(24,24,27,.6);display:flex;align-items:center;justify-content:center;padding:24px;';

    const panel = document.createElement('div');
    panel.style.cssText = 'width:100%;max-width:900px;height:100%;background:#fff;border-radius:8px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 50px rgba(0,0,0,.3);';

    const bar = document.createElement('div');
    bar.style.cssText = 'display:flex;justify-content:flex-end;padding:8px 12px;border-bottom:1px solid #e4e4e7;';
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.textContent = 'Fermer';
    closeBtn.style.cssText = 'padding:6px 14px;border-radius:6px;border:1px solid #d4d4d8;background:#fff;cursor:pointer;font-size:14px;';
    bar.appendChild(closeBtn);

    const iframe = document.createElement('iframe');
    iframe.style.cssText = 'flex:1;border:0;width:100%;';
    iframe.title = 'Aperçu PDF';
    iframe.src = blobUrl;

    panel.append(bar, iframe);
    overlay.appendChild(panel);

    const cleanup = () => {
        overlay.remove();
        URL.revokeObjectURL(blobUrl);
    };
    closeBtn.addEventListener('click', cleanup);
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) cleanup();
    });

    iframe.addEventListener('load', () => {
        // Give the PDF renderer a moment to finish painting before printing.
        setTimeout(() => {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch {
                // Printing failed — the overlay stays open so the user can print manually via the viewer's own controls.
            }
        }, 400);
    });

    document.body.appendChild(overlay);
}
