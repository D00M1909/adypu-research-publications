<?php
// The one icon sprite every page carries. Lucide-style 24px strokes; the school
// icons are the attendance dashboard's own, so a school looks the same on both
// sites. Use icon('name') to place one.

function icon_sprite(): string {
    return '<svg style="display:none" aria-hidden="true"><defs>'
        . '<symbol id="icon-eng" viewBox="0 0 24 24"> <path d="M11 10.27 7 3.34" /> <path d="m11 13.73-4 6.93" /> <path d="M12 22v-2" /> <path d="M12 2v2" /> <path d="M14 12h8" /> <path d="m17 20.66-1-1.73" /> <path d="m17 3.34-1 1.73" /> <path d="M2 12h2" /> <path d="m20.66 17-1.73-1" /> <path d="m20.66 7-1.73 1" /> <path d="m3.34 17 1.73-1" /> <path d="m3.34 7 1.73 1" /> <circle cx="12" cy="12" r="2" /> <circle cx="12" cy="12" r="8" /> </symbol>'
        . '<symbol id="icon-mgmt" viewBox="0 0 24 24"> <path d="M3 3v16a2 2 0 0 0 2 2h16" /> <path d="M18 17V9" /> <path d="M13 17V5" /> <path d="M8 17v-3" /> </symbol>'
        . '<symbol id="icon-law" viewBox="0 0 24 24"> <path d="M12 3v18" /> <path d="m19 8 3 8a5 5 0 0 1-6 0zV7" /> <path d="M3 7h1a17 17 0 0 0 8-2 17 17 0 0 0 8 2h1" /> <path d="m5 8 3 8a5 5 0 0 1-6 0zV7" /> <path d="M7 21h10" /> </symbol>'
        . '<symbol id="icon-design" viewBox="0 0 24 24"> <path d="M12 22a1 1 0 0 1 0-20 10 9 0 0 1 10 9 5 5 0 0 1-5 5h-2.25a1.75 1.75 0 0 0-1.4 2.8l.3.4a1.75 1.75 0 0 1-1.4 2.8z" /> <circle cx="13.5" cy="6.5" r=".5" fill="currentColor" /> <circle cx="17.5" cy="10.5" r=".5" fill="currentColor" /> <circle cx="6.5" cy="12.5" r=".5" fill="currentColor" /> <circle cx="8.5" cy="7.5" r=".5" fill="currentColor" /> </symbol>'
        . '<symbol id="icon-science" viewBox="0 0 24 24"> <path d="M14 2v6a2 2 0 0 0 .245.96l5.51 10.08A2 2 0 0 1 18 22H6a2 2 0 0 1-1.755-2.96l5.51-10.08A2 2 0 0 0 10 8V2" /> <path d="M6.453 15h11.094" /> <path d="M8.5 2h7" /> </symbol>'
        . '<symbol id="icon-arch" viewBox="0 0 24 24"> <path d="M10 18v-7" /> <path d="M11.119 2.205a2 2 0 0 1 1.762 0l7.84 3.846A.5.5 0 0 1 20.5 7h-17a.5.5 0 0 1-.22-.949z" /> <path d="M14 18v-7" /> <path d="M18 18v-7" /> <path d="M3 22h18" /> <path d="M6 18v-7" /> </symbol>'
        . '<symbol id="icon-hosp" viewBox="0 0 24 24"> <path d="M3 20a1 1 0 0 1-1-1v-1a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v1a1 1 0 0 1-1 1Z" /> <path d="M20 16a8 8 0 1 0-16 0" /> <path d="M12 4v4" /> <path d="M10 4h4" /> </symbol>'
        . '<symbol id="icon-lib" viewBox="0 0 24 24"> <path d="M12 5v16" /> <path d="M20.001 19A2 2 0 0022 17V5a2 2 0 00-1.999-2L16 3.002A5 5 0 0012 5a5 5 0 00-4-2H4a2 2 0 00-2 2v12a2 2 0 001.999 2H8a5 5 0 014 2 5 5 0 014-2z" /> </symbol>'
        . '<symbol id="icon-film" viewBox="0 0 24 24"> <path d="m12.296 3.464 3.02 3.956" /> <path d="M20.2 6 3 11l-.9-2.4c-.3-1.1.3-2.2 1.3-2.5l13.5-4c1.1-.3 2.2.3 2.5 1.3z" /> <path d="M3 11h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /> <path d="m6.18 5.276 3.1 3.899" /> </symbol>'
        . '<symbol id="icon-partner" viewBox="0 0 24 24"> <path d="m11 17 2 2a1 1 0 1 0 3-3" /> <path d="m14 14 2.5 2.5a1 1 0 1 0 3-3l-3.88-3.88a3 3 0 0 0-4.24 0l-.88.88a1 1 0 1 1-3-3l2.81-2.81a5.79 5.79 0 0 1 7.06-.87l.47.28a2 2 0 0 0 1.42.25L21 4" /> <path d="m21 3 1 11h-2" /> <path d="M3 3 2 14l6.5 6.5a1 1 0 1 0 3-3" /> <path d="M3 4h8" /> </symbol>'
        . '<symbol id="icon-user" viewBox="0 0 24 24"> <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" /> <circle cx="12" cy="7" r="4" /> </symbol>'
        . '<symbol id="icon-out" viewBox="0 0 24 24"> <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /> <path d="m16 17 5-5-5-5" /> <path d="M21 12H9" /> </symbol>'
        . '<symbol id="icon-user-check" viewBox="0 0 24 24"> <path d="m16 11 2 2 4-4" /> <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /> <circle cx="9" cy="7" r="4" /> </symbol>'
        . '<symbol id="icon-refresh" viewBox="0 0 24 24"> <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" /> <path d="M21 3v5h-5" /> <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" /> <path d="M8 16H3v5" /> </symbol>'
        . '<symbol id="icon-x" viewBox="0 0 24 24"> <path d="M18 6 6 18" /> <path d="m6 6 12 12" /> </symbol>'
        . '<symbol id="icon-chevron" viewBox="0 0 24 24"> <path d="m9 18 6-6-6-6" /> </symbol>'
        . '<symbol id="icon-caret" viewBox="0 0 24 24"> <path d="m6 9 6 6 6-6" /> </symbol>'
        . '<symbol id="icon-journal" viewBox="0 0 24 24"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/><path d="M8 7h6"/><path d="M8 11h8"/></symbol>'
        . '<symbol id="icon-conf" viewBox="0 0 24 24"><path d="M2 3h20"/><path d="M21 3v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3"/><path d="m7 21 5-5 5 5"/></symbol>'
        . '<symbol id="icon-book" viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></symbol>'
        . '<symbol id="icon-chapter" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/></symbol>'
        . '<symbol id="icon-patent" viewBox="0 0 24 24"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></symbol>'
        . '<symbol id="icon-copyright" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M14.83 14.83a4 4 0 1 1 0-5.66"/></symbol>'
        . '<symbol id="icon-plus" viewBox="0 0 24 24"><path d="M12 5v14"/><path d="M5 12h14"/></symbol>'
        . '<symbol id="icon-download" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/></symbol>'
        . '<symbol id="icon-upload" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/></symbol>'
        . '<symbol id="icon-link" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></symbol>'
        . '<symbol id="icon-check" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></symbol>'
        . '<symbol id="icon-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></symbol>'
        . '<symbol id="icon-edit" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></symbol>'
        . '<symbol id="icon-trash" viewBox="0 0 24 24"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></symbol>'
        . '<symbol id="icon-list" viewBox="0 0 24 24"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></symbol>'
        . '<symbol id="icon-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></symbol>'
        . '<symbol id="icon-calendar" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/></symbol>'
        . '<symbol id="icon-file" viewBox="0 0 24 24"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><path d="M14 2v6h6"/></symbol>'
        . '<symbol id="icon-pie" viewBox="0 0 24 24"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></symbol>'
        . '<symbol id="icon-back" viewBox="0 0 24 24"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></symbol>'
        . '<symbol id="icon-home" viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></symbol>'
        . '<symbol id="icon-chevron-r" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></symbol>'
        . '<symbol id="icon-warn" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></symbol>'
        . '<symbol id="icon-eye" viewBox="0 0 24 24"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></symbol>'
        . '<symbol id="icon-menu" viewBox="0 0 24 24"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></symbol>'
        . '<symbol id="icon-moon" viewBox="0 0 24 24"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></symbol>'
        . '<symbol id="icon-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></symbol>'
        . '</defs></svg>';
}

function icon(string $id, string $class = ''): string {
    return '<svg class="ic' . ($class !== '' ? ' ' . $class : '') . '" aria-hidden="true"><use href="#icon-' . htmlspecialchars($id) . '"/></svg>';
}
