<?php
require_once 'lang.php';

$res = getRecommendationsFromPython('recommend', ['query' => 'Pantai Kuta']);

echo "<pre>";
if ($res) {
    echo "BERHASIL DARI PYTHON!\n";
    print_r(array_slice($res, 0, 3)); // Tampilkan 3 tempat teratas
} else {
    echo "GAGAL MEMANGGIL PYTHON!";
}
echo "</pre>";