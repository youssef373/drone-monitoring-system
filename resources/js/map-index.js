/**
 * map-index.js
 * UI interactions for the fleet map page (/map).
 * Depends on window.mapInstance and window.fitAllDrones exposed by map.js.
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── HUD zoom / fit buttons ────────────────────────────────────────
    document.getElementById('btn-zoom-in')?.addEventListener('click', () => {
        window.mapInstance?.zoomIn();
    });

    document.getElementById('btn-zoom-out')?.addEventListener('click', () => {
        window.mapInstance?.zoomOut();
    });

    document.getElementById('btn-fit-all')?.addEventListener('click', () => {
        window.fitAllDrones?.();
    });

    // Topbar "zoom to fit" button (shared in layout)
    document.getElementById('btn-fit-bounds')?.addEventListener('click', () => {
        window.fitAllDrones?.();
    });

    // ── Sidebar card highlight ────────────────────────────────────────
    // Called by map.js when a marker is clicked, or by the card click below.
    window.selectDrone = (id) => {
        document.querySelectorAll('[data-drone-id]').forEach((el) => {
            const isSelected = el.dataset.droneId == id;
            el.classList.toggle('bg-[#4d8eff]/10',      isSelected);
            el.classList.toggle('border-[#adc6ff]/30',  isSelected);
            el.classList.toggle('bg-[#272a31]/50',      !isSelected);
            el.classList.toggle('border-[#424754]/20',  !isSelected);
        });
    };

    // Delegate click on sidebar drone cards (replaces inline onclick)
    document.addEventListener('click', (e) => {
        const card = e.target.closest('[data-drone-id]');
        if (card) window.selectDrone(card.dataset.droneId);
    });
});
