<?php
$pattern = '/^\d{4}\.[\w]+\.?\d+$/';
$codes = ['2906.BMA.005', '2910.BMA.007'];
foreach ($codes as $code) {
    $match = preg_match($pattern, $code) ? 'MATCH' : 'NO MATCH';
    echo "$code: $match\n";
}
