<?php
// One input for one field from types.php, named f_{type}_{key} so the six
// types can share a page without their fields colliding. pub_read_fields()
// in pubs.php reads the same names back.
//
// No HTML "required" on these: five of the six types are hidden at any time,
// and a browser refuses to submit a form with an empty required field it
// cannot even show. The server checks instead, and the red star says which.

function field_input(string $type, string $key, array $f, $value, string $error = ''): string {
    [$label, $input, $hint, $required, $options] = field_spec($f);
    $name = "f_{$type}_{$key}";
    $id = $name;
    $wide = in_array($input, ['textarea', 'multi'], true) || $key === 'title' || $key === 'chapter' || $key === 'book' ? ' w3' : (in_array($input, ['daterange'], true) ? ' w2' : '');
    $h = '<div class="field' . $wide . ($error !== '' ? ' has-error' : '') . '" data-key="' . e($key) . '"' . ($required ? ' data-required' : '') . '>'
       . '<label for="' . $id . '">' . e($label) . ($required ? ' <span class="req">*</span>' : '') . '</label>';
    switch ($input) {
        case 'textarea':
            $h .= '<textarea id="' . $id . '" name="' . $name . '" placeholder="' . e($hint) . '">' . e($value) . '</textarea>';
            break;
        case 'select':
            $h .= '<select id="' . $id . '" name="' . $name . '"><option value="">Choose</option>';
            foreach ($options as $o) $h .= '<option' . ($value === $o ? ' selected' : '') . '>' . e($o) . '</option>';
            $h .= '</select>';
            break;
        case 'multi':
            $value = (array) $value;
            $h .= '<div class="multi">';
            foreach ($options as $o) {
                $h .= '<label class="chip"><input type="checkbox" name="' . $name . '[]" value="' . e($o) . '"' . (in_array($o, $value, true) ? ' checked' : '') . '>' . icon('check') . e($o) . '</label>';
            }
            $h .= '</div>';
            break;
        case 'daterange':
            [$from, $to] = array_pad(explode('|', (string) $value), 2, '');
            $h .= '<div class="range"><input type="date" id="' . $id . '" name="' . $name . '_from" value="' . e($from) . '" aria-label="From">'
                . '<span>to</span><input type="date" name="' . $name . '_to" value="' . e($to) . '" aria-label="To"></div>';
            break;
        default:
            $html = ['url' => 'url', 'month' => 'month', 'date' => 'date'][$input] ?? 'text';
            // A DOI can be pasted bare ("10.1016/..."), which type=url would refuse.
            if ($html === 'url') $html = 'text';
            $h .= '<input type="' . $html . '" id="' . $id . '" name="' . $name . '" value="' . e($value) . '"' . ($hint !== '' && $input !== 'month' ? ' placeholder="' . e($hint) . '"' : '') . '>';
    }
    if ($error !== '') $h .= '<span class="err">' . e($error) . '</span>';
    elseif ($hint !== '' && in_array($input, ['month'], true)) $h .= '<span class="hint">' . e($hint) . '</span>';
    return $h . '</div>';
}

// A department picker for a unit, with "Other" for one that is not listed.
function dept_select(string $unit, string $selected, string $name = 'dept'): string {
    $depts = unit_depts($unit);
    $other = $selected !== '' && !in_array($selected, $depts, true);
    $h = '<select id="' . $name . '" name="' . $name . '" data-other="' . $name . '_other"><option value="">Choose</option>';
    foreach ($depts as $d) $h .= '<option' . ($d === $selected ? ' selected' : '') . '>' . e($d) . '</option>';
    $h .= '<option value="__other"' . ($other ? ' selected' : '') . '>Other</option></select>';
    $h .= '<input type="text" id="' . $name . '_other" name="' . $name . '_other" placeholder="Type your department" value="' . e($other ? $selected : '') . '"' . ($other ? '' : ' hidden') . '>';
    return $h;
}
