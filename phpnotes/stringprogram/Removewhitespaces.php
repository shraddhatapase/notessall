//remove spaces in sring
$str = "Remove white spaces from this string";
$newStr = '';

for($i = 0; $i < strlen($str); $i++) {
    if($str[$i] != ' ') {
        $newStr .= $str[$i];
    }
}

echo $newStr;