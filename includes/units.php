<?php
// Who a faculty member belongs to: an ADYPU school or a knowledge partner, then
// a department inside it. A "unit" is either; its id is the school id ('eng')
// or the partner id ('kp-aero'), the same ids the attendance dashboard uses.
//
// Departments come from the attendance dashboard's includes/structure.php as of
// 8 Oct 2026: a school's branches, merged where two branch names are the same
// department (CSE and SE are both Software Engineering; Core and CS are first
// year's two scheduling groups). Law, Architecture, Liberal Arts and Film &
// Media sent the dashboard no branches, so they have none here yet. Every list
// also takes a typed-in "Other", so a missing department never blocks a signup.

const SCHOOLS = [
    'eng'     => ['name' => 'School of Engineering', 'short' => 'Engineering', 'icon' => 'eng', 'depts' => [
        'Computer Science & Engineering', 'AI & Data Science', 'Cyber Security',
        'Biotechnology', 'Biomedical Engineering', 'Mechanical Engineering',
        'Robotics', 'Civil Engineering', 'First Year (Basic Sciences)',
    ]],
    'mgmt'    => ['name' => 'School of Management', 'short' => 'Management', 'icon' => 'mgmt', 'depts' => ['MBA', 'BBA']],
    'law'     => ['name' => 'School of Law', 'short' => 'Law', 'icon' => 'law', 'depts' => []],
    'design'  => ['name' => 'School of Design', 'short' => 'Design', 'icon' => 'design', 'depts' => [
        'Fashion Design', 'Interior Design', 'Product Design', 'Transportation Design',
        'Visual Communication Design', 'UI/UX Design', 'PGDM',
    ]],
    'science' => ['name' => 'School of Science', 'short' => 'Science', 'icon' => 'science', 'depts' => ['B.Sc.']],
    'arch'    => ['name' => 'School of Architecture', 'short' => 'Architecture', 'icon' => 'arch', 'depts' => []],
    'hosp'    => ['name' => 'School of Hospitality', 'short' => 'Hospitality', 'icon' => 'hosp', 'depts' => ['Hospitality and Hotel Administration']],
    'lib'     => ['name' => 'School of Liberal Arts', 'short' => 'Liberal Arts', 'icon' => 'lib', 'depts' => []],
    'film'    => ['name' => 'School of Film & Media', 'short' => 'Film & Media', 'icon' => 'film', 'depts' => []],
];

// Partner programs stand in for departments: that is how a partner's faculty
// describe where they teach.
const PARTNERS = [
    'kp-aero'      => ['name' => 'Aero', 'depts' => ['Aeronautical', 'Aerospace', 'Avionics', 'Defence Technology', 'Space Technology']],
    'kp-newton'    => ['name' => 'Newton', 'depts' => ['B.Tech CSE (AI&ML)']],
    'kp-sunstone'  => ['name' => 'Sunstone', 'depts' => ['B.Tech (CS&IT)', 'B.Tech CSE (AI)', 'BCA (FSD)', 'MCA (FSD)', 'BBA', 'MBA']],
    'kp-nxtwave'   => ['name' => 'NxtWave', 'depts' => ['B.Tech CSE (DS)']],
    'kp-emversity' => ['name' => 'Emversity', 'depts' => ['B.Sc AOTT', 'B.Sc CVT', 'B.Sc MLT', 'B.Sc RT']],
    'kp-veloces'   => ['name' => 'Veloces', 'depts' => ['Cyber Forensics & Information Security', 'Virtual & Augmented Reality']],
    'kp-seamedu'   => ['name' => 'SeamEdu', 'depts' => ['Engineering', 'Computer Applications', 'Management', 'Media & Communication', 'Film, Animation & Games']],
    'kp-upgrad'    => ['name' => 'Upgrad', 'depts' => ['B.Tech CS in AI']],
    'kp-pixelpop'  => ['name' => 'PixelPop', 'depts' => []],
    'kp-flyglam'   => ['name' => 'Flyglam', 'depts' => ['Aviation']],
    'kp-noval'     => ['name' => 'Noval', 'depts' => ['Clinical Research']],
];

const DESIGNATIONS = [
    'Professor', 'Associate Professor', 'Assistant Professor',
    'Visiting Faculty', 'Research Scholar', 'Other',
];

function unit_kind(string $id): string {
    return isset(PARTNERS[$id]) ? 'partner' : 'adypu';
}

function unit_exists(string $id): bool {
    return isset(SCHOOLS[$id]) || isset(PARTNERS[$id]);
}

function unit_name(string $id): string {
    return SCHOOLS[$id]['name'] ?? PARTNERS[$id]['name'] ?? $id;
}

// The short name for charts and tables, where "School of" on every row is noise.
function unit_short(string $id): string {
    return SCHOOLS[$id]['short'] ?? PARTNERS[$id]['name'] ?? $id;
}

function unit_icon(string $id): string {
    return SCHOOLS[$id]['icon'] ?? 'partner';
}

function unit_depts(string $id): array {
    return SCHOOLS[$id]['depts'] ?? PARTNERS[$id]['depts'] ?? [];
}

// Every unit on one side of the ADYPU / Knowledge Partner switch, in order.
function units_of(string $kind): array {
    return array_keys($kind === 'partner' ? PARTNERS : SCHOOLS);
}
