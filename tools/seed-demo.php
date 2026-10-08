<?php
// Fills data/ with made-up faculty and publications so every page has
// something to show while testing. Run it from the command line on a local
// copy only:  php tools/seed-demo.php
// Every demo account's password is "demo1234".
if (PHP_SAPI !== 'cli') exit('Run this from the command line.');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pubs.php';

if (pubs_all() && !in_array('--force', $argv, true)) exit("data/ already has publications. Add --force to add more anyway.\n");

mt_srand(7);
$first = ['Priya', 'Rahul', 'Sneha', 'Amit', 'Kavita', 'Rohan', 'Neha', 'Sanjay', 'Anjali', 'Vikram', 'Pooja', 'Arjun', 'Meera', 'Nikhil', 'Shruti', 'Kiran'];
$last = ['Kulkarni', 'Deshmukh', 'Patil', 'Joshi', 'Shinde', 'Pawar', 'Gokhale', 'Bhosale', 'Rao', 'Iyer', 'Mehta', 'Nair'];
$topics = ['Machine Learning', 'Sustainable Design', 'Supply Chain', 'Renewable Energy', 'Consumer Behaviour', 'Image Processing', 'Smart Cities', 'Biomaterials', 'Cyber Security', 'Digital Marketing', 'Robotics', 'Hospitality Management'];
$journals = ['IEEE Access', 'Journal of Cleaner Production', 'Materials Today: Proceedings', 'International Journal of Hospitality Management', 'Journal of Business Research', 'Design Studies'];
$pick = fn(array $a) => $a[mt_rand(0, count($a) - 1)];

$weights = ['eng' => 10, 'mgmt' => 5, 'design' => 4, 'science' => 3, 'hosp' => 2, 'law' => 2, 'arch' => 1, 'lib' => 1, 'film' => 1,
            'kp-aero' => 2, 'kp-sunstone' => 3, 'kp-newton' => 1, 'kp-seamedu' => 2, 'kp-emversity' => 1];
$people = [];
foreach ($weights as $unit => $n) {
    for ($i = 0; $i < max(1, intdiv($n, 2) + 1); $i++) {
        $name = 'Dr. ' . $pick($first) . ' ' . $pick($last);
        $email = strtolower(str_replace(['Dr. ', ' '], ['', '.'], $name)) . mt_rand(1, 99) . '@demo.adypu.edu.in';
        $depts = unit_depts($unit) ?: ['General'];
        $people[] = $p = ['email' => $email, 'name' => $name, 'erp' => unit_kind($unit) === 'adypu' ? 'ADYPU' . mt_rand(10000, 99999) : '',
            'unit' => $unit, 'dept' => $pick($depts), 'designation' => $pick(['Professor', 'Associate Professor', 'Assistant Professor', 'Assistant Professor'])];
        auth_put($email, $p + ['hash' => password_hash('demo1234', PASSWORD_DEFAULT), 'status' => 'active', 'admin' => false, 'created' => date('Y-m-d H:i'), 'fails' => 0, 'locked' => 0]);
    }
}

$typeWeights = ['journal' => 9, 'conf' => 6, 'book' => 1, 'chapter' => 3, 'patent' => 2, 'copyright' => 1];
$typeBag = [];
foreach ($typeWeights as $t => $w) $typeBag = array_merge($typeBag, array_fill(0, $w, $t));
$years = array_slice(academic_years(), 0, 4);
$count = 0;
foreach ($people as $person) {
    $n = mt_rand(1, $weights[$person['unit']] + 2);
    for ($i = 0; $i < $n; $i++) {
        $type = $pick($typeBag);
        $ay = $years[min(3, (int) floor(mt_rand(0, 99) / 30))];
        $start = (int) substr($ay, 0, 4);
        $month = mt_rand(1, 12);
        $ym = sprintf('%04d-%02d', $month >= 6 ? $start : $start + 1, $month);
        if ($ym > date('Y-m')) $ym = date('Y-m');
        $day = min(date('Y-m-d'), $ym . '-' . sprintf('%02d', mt_rand(1, 28)));
        $topic = $pick($topics);
        $t = pub_type($type);
        $f = [];
        foreach ($t['fields'] as $key => $spec) {
            [$label, $input, , $required, $options] = field_spec($spec);
            if (!$required && mt_rand(0, 2) === 0) { $f[$key] = $input === 'multi' ? [] : ''; continue; }
            $f[$key] = match ($input) {
                'select' => $pick($options),
                'multi' => [$pick($options)],
                'month' => $ym,
                'date' => $day,
                'daterange' => $day . '|' . $day,
                'url' => '10.' . mt_rand(1000, 9999) . '/demo.' . mt_rand(100000, 999999),
                'textarea' => 'A study of ' . strtolower($topic) . ' in the Indian context.',
                default => $label,
            };
        }
        $title = $pick(['A Study of ', 'Advances in ', 'Towards Better ', 'Rethinking ', 'An Approach to ']) . $topic . $pick([' for Indian Universities', ' in Practice', ': A Review', ' Using Deep Learning', ' in Pune', '']);
        $f[$t['title']] = $title;
        if ($t['source'] === 'journal') $f['journal'] = $pick($journals);
        if ($t['source'] === 'conference') $f['conference'] = 'International Conference on ' . $topic . ' ' . $start;
        if ($t['source'] === 'publisher') $f['publisher'] = $pick(['Springer', 'Routledge', 'Sage', 'Wiley']);
        if ($t['source'] === 'book') $f['book'] = 'Handbook of ' . $topic;
        if ($t['source'] === 'office') $f['office'] = 'Indian Patent Office';
        if ($t['source'] === 'country') $f['country'] = 'Copyright Office, Government of India';
        $id = pub_new_id();
        pub_save([
            'id' => $id, 'owner' => $person['email'], 'owner_name' => $person['name'], 'owner_erp' => $person['erp'], 'designation' => $person['designation'],
            'unit' => $person['unit'], 'dept' => $person['dept'], 'ay' => $ay, 'type' => $type, 'f' => $f,
            'proof_file' => '', 'proof_link' => mt_rand(0, 4) ? 'https://drive.google.com/file/d/demo' . $id : '',
            'created' => date('Y-m-d H:i'), 'updated' => date('Y-m-d H:i'),
        ]);
        $count++;
    }
}
echo 'Added ' . count($people) . " demo faculty and $count publications. Password for each: demo1234\n";
