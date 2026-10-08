<?php
// The six kinds of research output and the fields each one asks for, taken row
// for row from "Format For Research Data Collection.xlsx" (one tab per type).
// The labels are the workbook's own, so the export reads like the IQAC format
// it came from. Everything else follows from this file: the add form, its
// validation, the tables and every export column.
//
// A field is [key => [label, input, hint, required, options?]]. Inputs:
//   text, textarea, url, month (MM/YYYY as a month picker), date,
//   daterange (two dates), select (one of options), multi (any of options).
// The proof document is not a field here: every type has one, as a file upload
// and a link, handled by pubs.php.
//
// Fields marked "(if available)", "(if applicable)" or "optional" in the
// workbook are optional here; the rest are required.

const SDG_OPTIONS = [
    'SDG 1 – No Poverty', 'SDG 2 – Zero Hunger', 'SDG 3 – Good Health and Well-being',
    'SDG 4 – Quality Education', 'SDG 5 – Gender Equality', 'SDG 6 – Clean Water and Sanitation',
    'SDG 7 – Affordable and Clean Energy', 'SDG 8 – Decent Work and Economic Growth',
    'SDG 9 – Industry, Innovation and Infrastructure', 'SDG 10 – Reduced Inequalities',
    'SDG 11 – Sustainable Cities and Communities', 'SDG 12 – Responsible Consumption and Production',
    'SDG 13 – Climate Action', 'SDG 14 – Life Below Water', 'SDG 15 – Life on Land',
    'SDG 16 – Peace, Justice and Strong Institutions', 'SDG 17 – Partnerships for the Goals',
];

const IQAC_CATEGORY = ['Faculty', 'Student', 'Collaborative', 'Institutional'];

function pub_types(): array {
    return [
        'journal' => ['name' => 'Journal Paper', 'short' => 'Journal', 'icon' => 'journal', 'color' => '#C21B27', 'title' => 'title', 'source' => 'journal', 'date' => 'published', 'fields' => [
            'title'     => ['Title of the Paper', 'text', 'Full title as published', true],
            'journal'   => ['Name of Journal', 'text', 'Full name of the journal', true],
            // Not in the workbook: added later, so every journal shows its rank.
            'quartile'  => ['Journal Quartile', 'select', 'Q1 is the top 25% of journals in its field', true, ['Q1', 'Q2', 'Q3', 'Q4', 'Not ranked']],
            'issn'      => ['ISSN Number', 'text', 'International Standard Serial Number', true],
            'publisher' => ['Publisher', 'text', 'Name of publishing organisation', true],
            'pages'     => ['Page Numbers', 'text', 'e.g. pp. 45–57', true],
            'issue'     => ['Issue', 'text', 'e.g. Issue 2', true],
            'volume'    => ['Volume', 'text', 'e.g. Vol. 10', true],
            'published' => ['Month & Year of Publication', 'month', 'MM/YYYY', true],
            'indexing'  => ['Indexing Database', 'select', '', true, ['Scopus', 'Web of Science', 'Others']],
            'impact'    => ['Impact Factor / Cite Score', 'text', 'Latest value, if available', false],
            'doi'       => ['DOI / URL', 'url', 'Digital Object Identifier link', true],
            'category'  => ['Category', 'select', '', true, ['International', 'National']],
            'sdg'       => ['SDG Linkage', 'multi', 'e.g. SDG 4 – Quality Education', false, SDG_OPTIONS],
        ]],
        'conf' => ['name' => 'Conference Paper', 'short' => 'Conference', 'icon' => 'conf', 'color' => '#2563eb', 'title' => 'title', 'source' => 'conference', 'date' => 'published', 'fields' => [
            'title'       => ['Title of the Paper', 'text', 'Exact title as published or presented', true],
            'authors'     => ['Author', 'text', 'All authors in order', true],
            'institution' => ['Affiliated Institution', 'text', 'Full name of the university or institute', true],
            'conference'  => ['Title of the Conference / Seminar', 'text', 'Full name of the conference', true],
            'organizer'   => ['Organizer', 'text', 'Host institution, organisation or association', true],
            'conf_type'   => ['Conference Type', 'select', '', true, ['International', 'National', 'Regional', 'State', 'Institutional']],
            'venue'       => ['Venue / Location', 'text', 'City, Country (e.g. Pune, India)', true],
            'dates'       => ['Date(s) of Conference', 'daterange', '', true],
            'proceedings' => ['Title of Proceedings (if published)', 'text', 'Proceedings or book of abstracts', false],
            'isbn'        => ['ISBN / ISSN (if available)', 'text', 'Number of the proceedings or journal', false],
            'publisher'   => ['Publisher / Publication Details', 'text', 'e.g. IEEE, Springer, Elsevier', true],
            'published'   => ['Month & Year of Publication', 'month', 'MM/YYYY', true],
            'pages'       => ['Page Numbers (if applicable)', 'text', 'e.g. pp. 145–152', false],
            'indexing'    => ['Indexing', 'select', '', true, ['Scopus', 'Web of Science', 'UGC CARE', 'IEEE Xplore', 'Non-Indexed']],
            'doi'         => ['DOI / URL', 'url', 'Digital Object Identifier or link', true],
            'mode'        => ['Mode of Presentation', 'select', '', true, ['Oral', 'Poster', 'Virtual', 'Hybrid']],
            'category'    => ['Category (for IQAC use)', 'select', '', true, IQAC_CATEGORY],
            'sdg'         => ['SDG Linkage (if applicable)', 'multi', '', false, SDG_OPTIONS],
        ]],
        'book' => ['name' => 'Book', 'short' => 'Book', 'icon' => 'book', 'color' => '#15803d', 'title' => 'title', 'source' => 'publisher', 'date' => 'published', 'fields' => [
            'title'         => ['Title of the Book', 'text', 'Full title as on the cover and title page', true],
            'authors'       => ['Author / Editor', 'text', 'All authors or editors in order', true],
            'contribution'  => ['Type of Contribution', 'select', '', true, ['Authored Book', 'Edited Book', 'Monograph', 'Textbook', 'Reference Book']],
            'publisher'     => ['Publisher Name', 'text', 'e.g. Springer, Routledge, Sage', true],
            'place'         => ['Place of Publication', 'text', 'City, Country (as per title page)', true],
            'isbn'          => ['ISBN Number', 'text', 'e.g. ISBN 978-93-XXXX-XXXX-X', true],
            'published'     => ['Month & Year of Publication', 'month', 'MM/YYYY', true],
            'edition'       => ['Edition / Volume', 'text', 'First / Second Edition, or Vol. 1 (if applicable)', false],
            'total_pages'   => ['Total Pages', 'text', 'Total number of pages in the book', true],
            'publisher_type' => ['Publisher Type', 'select', '', true, ['National', 'International']],
            'indexing'      => ['Indexed In', 'select', '', true, ['Scopus', 'Web of Science', 'UGC CARE', 'Non-indexed']],
            'doi'           => ['ISBN-Linked DOI / URL', 'url', 'DOI or direct URL, if available', false],
            'funding'       => ['Funding / Sponsorship', 'text', 'Grant or support, if any', false],
            'collaborators' => ['Collaborating Institution / Co-authors (if any)', 'text', 'Other institutions or universities involved', false],
            'sdg'           => ['SDG Relevance (if applicable)', 'multi', '', false, SDG_OPTIONS],
            'description'   => ['Brief Description / Theme', 'textarea', 'Two or three lines on the focus of the book', true],
            'category'      => ['Category (for IQAC use)', 'select', '', true, ['Faculty Publication', 'Student Publication', 'Collaborative', 'Institutional']],
        ]],
        'chapter' => ['name' => 'Book Chapter', 'short' => 'Chapter', 'icon' => 'chapter', 'color' => '#d97706', 'title' => 'chapter', 'source' => 'book', 'date' => 'published', 'fields' => [
            'book'      => ['Title of the Book', 'text', 'Full title as per the cover page', true],
            'chapter'   => ['Title of the Chapter', 'text', 'Exact title of the contribution', true],
            'authors'   => ['Author / Editor', 'text', 'All authors', true],
            'publisher' => ['Publisher', 'text', 'Name of the publisher', true],
            'isbn'      => ['ISBN Number', 'text', 'International Standard Book Number', true],
            'edition'   => ['Edition / Volume', 'text', '', false],
            'published' => ['Month & Year of Publication', 'month', 'MM/YYYY', true],
            'pages'     => ['Page Numbers', 'text', 'e.g. pp. 102–118', true],
            'place'     => ['Place of Publication', 'text', 'City, Country', true],
            'pub_type'  => ['Type', 'select', '', true, ['National', 'International']],
            'indexing'  => ['Indexed In (if applicable)', 'text', 'e.g. Scopus Indexed Book Series', false],
            'doi'       => ['DOI / URL', 'url', '', false],
            'sdg'       => ['SDG / Thematic Relevance', 'multi', '', false, SDG_OPTIONS],
        ]],
        'patent' => ['name' => 'Patent', 'short' => 'Patent', 'icon' => 'patent', 'color' => '#7c3aed', 'title' => 'title', 'source' => 'office', 'date' => 'filed', 'fields' => [
            'title'         => ['Title of the Patent', 'text', 'Exact title as filed or approved', true],
            'inventors'     => ['Name of Inventor', 'text', 'All inventors in order of application', true],
            'department'    => ['Department / School', 'text', 'Department of the inventor', true],
            'institution'   => ['Affiliated Institution', 'text', 'e.g. Ajeenkya DY Patil University, Pune', true],
            'application_no' => ['Patent Application Number', 'text', 'Official number from the Patent Office', true],
            'filed'         => ['Date of Filing', 'date', '', true],
            'published'     => ['Date of Publication', 'date', '', false],
            'patent_no'     => ['Patent Number', 'text', 'Granted patent number, if granted', false],
            'status'        => ['Status of Patent', 'select', '', true, ['Filed', 'Published', 'Granted', 'Commercialized']],
            'country'       => ['Country of Filing', 'text', 'e.g. India / USA / PCT', true],
            'office'        => ['Patent Office / Authority', 'text', 'e.g. Indian Patent Office, USPTO, WIPO', true],
            'patent_type'   => ['Type of Patent', 'select', '', true, ['Design', 'Utility', 'Process', 'Product', 'Copyright-based']],
            'category'      => ['Category (for IQAC)', 'select', '', true, ['Faculty Patent', 'Student Patent', 'Collaborative Patent']],
            'collaborators' => ['Collaborating Organization / Institute (if any)', 'text', 'Partner institution or industry', false],
            'field'         => ['Field of Invention', 'text', 'e.g. Mechanical Engineering, Biotechnology', true],
            'sdg'           => ['SDG Linkage (if applicable)', 'multi', '', false, SDG_OPTIONS],
            'ipc'           => ['Patent Classification / IPC Code', 'text', 'e.g. G06F 17/30', false],
        ]],
        'copyright' => ['name' => 'Copyright', 'short' => 'Copyright', 'icon' => 'copyright', 'color' => '#0891b2', 'title' => 'title', 'source' => 'country', 'date' => 'applied', 'fields' => [
            'title'         => ['Title of the Work', 'text', 'Exact title as registered', true],
            'authors'       => ['Name of Inventor', 'text', 'All authors in order', true],
            'department'    => ['Department / School', 'text', 'Department the author(s) belong to', true],
            'work_type'     => ['Type of Work', 'select', '', true, ['Literary', 'Artistic', 'Software', 'Musical', 'Design', 'Educational Content', 'Digital Media', 'Training Module', 'Manual', 'Other']],
            'reg_no'        => ['Registration Number', 'text', 'Official number, after grant', false],
            'applied'       => ['Date of Application (Filing Date)', 'date', '', true],
            'registered'    => ['Date of Registration (Grant Date)', 'date', '', false],
            'status'        => ['Status', 'select', '', true, ['Filed', 'Registered', 'Granted']],
            'country'       => ['Country / Copyright Office', 'text', 'e.g. Copyright Office, Government of India', true],
            'field'         => ['Field / Domain', 'text', 'e.g. Engineering, Design, Law, Media', true],
            'category'      => ['Category (for IQAC use)', 'select', '', true, IQAC_CATEGORY],
            'collaborators' => ['Collaborating Organization / Co-author (if any)', 'text', 'External collaborator or institution', false],
            'sdg'           => ['SDG Linkage (optional)', 'multi', '', false, SDG_OPTIONS],
        ]],
    ];
}

function pub_type(string $id): ?array {
    return pub_types()[$id] ?? null;
}

function type_ids(): array {
    return array_keys(pub_types());
}

// One field as [label, input, hint, required, options].
function field_spec(array $f): array {
    return [$f[0], $f[1], $f[2], $f[3], $f[4] ?? []];
}
