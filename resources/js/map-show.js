/**
 * map-show.js
 * UI interactions for the single-drone map page (/map/{drone}).
 * Depends on window.mapInstance, window.centerOnFocused, and
 * window.setFollowMode exposed by map.js.
 */

document.addEventListener('DOMContentLoaded', () => {
    // ── HUD zoom / center buttons ─────────────────────────────────────
    document.getElementById('btn-zoom-in')?.addEventListener('click', () => {
        window.mapInstance?.zoomIn();
    });

    document.getElementById('btn-zoom-out')?.addEventListener('click', () => {
        window.mapInstance?.zoomOut();
    });

    document.getElementById('btn-center-drone')?.addEventListener('click', () => {
        window.centerOnFocused?.();
    });

    // Topbar "zoom to fit" button (shared in layout)
    document.getElementById('btn-fit-bounds')?.addEventListener('click', () => {
        window.centerOnFocused?.();
    });

    // ── Follow-mode toggle ────────────────────────────────────────────
    let following = false;

    document.getElementById('btn-follow')?.addEventListener('click', (e) => {
        following = !following;
        window.setFollowMode?.(following);

        const btn  = e.currentTarget;
        const icon = btn.querySelector('span.material-symbols-outlined');

        btn.classList.toggle('bg-[#adc6ff]/10', following);
        if (icon) icon.textContent = following ? 'location_on' : 'near_me';
    });

    // ── Bottom sheet open / close ─────────────────────────────────────
    const sheet    = document.getElementById('bottom-sheet');
    const backdrop = document.getElementById('sheet-backdrop');

    function openSheet() {
        sheet?.classList.remove('translate-y-full');
        backdrop?.classList.remove('opacity-0', 'pointer-events-none');
        backdrop?.classList.add('opacity-100');
    }

    function closeSheet() {
        sheet?.classList.add('translate-y-full');
        backdrop?.classList.add('opacity-0', 'pointer-events-none');
        backdrop?.classList.remove('opacity-100');
    }

    document.getElementById('sheet-handle')?.addEventListener('click', openSheet);
    document.getElementById('sheet-close')?.addEventListener('click', closeSheet);
    backdrop?.addEventListener('click', closeSheet);

    // Expose so map.js can open the sheet when the focused drone marker is clicked
    window.openDroneSheet = openSheet;
});
