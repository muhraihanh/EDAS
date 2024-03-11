<?php

require 'koneksi.php';

global $conn;
function menampilkan($query)
{
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}
$eda_alternatives = menampilkan("SELECT * FROM eda_alternatives");
$eda_criterias = menampilkan("SELECT * FROM eda_criterias");
$eda_evaluations = menampilkan("SELECT * FROM eda_evaluations ");

$temp_evaluations = array();
$a = 0;
for ($i = 1; $i <= count($eda_alternatives); $i++) {
    $temp_evaluations[$i] = array();
    for ($j = 1; $j <= count($eda_criterias); $j++) {
        $temp_evaluations[$i][$j] = $eda_evaluations[$a]['value'];
        $a++;
    }
}



if (isset($_POST['submit'])) {
    //-- inisialisasi variabel array alternatif
    $alternative = array();
    $sql = 'SELECT * FROM eda_alternatives';
    $data = $conn->query($sql);
    while ($row = $data->fetch_object()) {
        $alternative[$row->id_alternative] = $row->name;
    }

    // membuat bobot
    $w = [
        $_POST['harga_sewa'], $_POST['kapasitas_pengunjung'], $_POST['ukuran_lahan_parkir'],
        $_POST['kelayakan_toilet'], $_POST['kelayakan_musholla'], $_POST['jarak'],
        $_POST['ketersediaan_konsumsi'], $_POST['jenis_lapangan']
    ];



    //-- inisialisasi variabel array kriteria dan bobot (W)
    $kriteria = array();
    $sql = 'SELECT * FROM eda_criterias';
    $data = $conn->query($sql);
    while ($row = $data->fetch_object()) {
        $kriteria[$row->id_criteria] = array($row->criteria, $row->attribute);
    }


    //-- inisialisasi variabel array matriks keputusan X
    $X = array();
    //-- ambil nilai dari tabel
    $sql = 'SELECT * FROM eda_evaluations';
    $data = $conn->query($sql);
    while ($row = $data->fetch_object()) {
        $i = $row->id_alternative;
        $j = $row->id_criteria;
        $X[$i][$j] = $row->value;
    }


    $AV = array();
    $jml_alternative = count($eda_alternatives);
    foreach ($X as $i => $ai) {
        foreach ($ai as $j => $aij) {
            if (!isset($AV[$j])) {
                $AV[$j] = 0;
            }
            $AV[$j] += $aij / $jml_alternative;
        }
    }


    //-- inisialisasi array PDA/NDA
    $PDA = array();
    $NDA = array();
    foreach ($X as $i => $xi) {
        $PDA[$i] = array();
        $NDA[$i] = array();
        foreach ($xi as $j => $xij) {
            if ($kriteria[$j][1] == 'benefit') {
                $PDA[$i][$j] = max(0, ($xij - $AV[$j]) / $AV[$j]);
                $NDA[$i][$j] = max(0, ($AV[$j] - $xij) / $AV[$j]);
            } else {
                $PDA[$i][$j] = max(0, ($AV[$j] - $xij) / $AV[$j]);
                $NDA[$i][$j] = max(0, ($xij - $AV[$j]) / $AV[$j]);
            }
        }
    }

    //-- inisialisasi array SP/SN
    $SP = array();
    $SN = array();
    foreach ($X as $i => $xi) {
        $SP[$i] = 0;
        $SN[$i] = 0;
        foreach ($xi as $j => $xij) {
            $SP[$i] += $w[$j - 1] * $PDA[$i][$j];
            $SN[$i] += $w[$j - 1] * $NDA[$i][$j];
        }
    }


    $NSP = array();
    $NSN = array();
    for ($i = 1; $i <= $jml_alternative; $i++) {
        $NSP[$i] = $SP[$i] / max($SP);
        $NSN[$i] = 1 - $SN[$i] / max($SN);
    }


    //-- inisialisasi nilai skor penilaian AS
    $AS = array();
    foreach ($alternative as $i => $ax) {
        $AS[$i] = ($NSP[$i] + $NSN[$i]) / 2;
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pendukung Keputusan EDAS</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>

<body style="background-color:aliceblue;">
    <h4><span class="blue"></span>Sistem Pendukung Keputusan<span class="blue"></span></h4>
    <h2><b>Mencari Lapangan Basket terbaik untuk Fasilkom Games menggunakan metode EDAS oleh Kelompok EDAS</b></h2>

    <table class="container">
        <thead>
            <tr>
                <th style="width:7%;">
                    <h1 style="text-align:left;">No</h1>
                </th>
                <th style="width:18%;">
                    <h1 style="text-align:left;">Nama</h1>
                </th>
                <th style="width:12%;">
                    <h1 style="text-align:left;">Harga (RP) </h1>
                </th>
                <th style="width:12%;">
                    <h1 style="text-align:left;">Kapasitas</h1>
                </th>
                <th style="width:8%;">
                    <h1 style="text-align:left;">Parkir</h1>
                </th>
                <th style="width:8%;">
                    <h1 style="text-align:left;">Toilet</h1>
                </th>
                <th style="width:10%;">
                    <h1 style="text-align:left;">Musholla</h1>
                </th>
                <th style="width:8%;">
                    <h1 style="text-align:left;">Jarak (KM)</h1>
                </th>
                <th style="width:11%;">
                    <h1 style="text-align:left;">Konsumsi</h1>
                </th>
                <th style="width:11%;">
                    <h1 style="text-align:centre;">Jenis</h1>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;  ?>
            <?php foreach ($eda_alternatives as $index => $data) : ?>
                <tr>
                    <td><?= $no; ?></td>
                    <td><?= $data['name']; ?></td>
                    <?php for ($d = 1; $d <= count($eda_criterias); $d++) : ?>
                        <td><?= $temp_evaluations[$index + 1][$d]; ?></td>
                    <?php endfor; ?>
                </tr>
            <?php $no++;
            endforeach; ?>
        </tbody>
    </table>

    <h4></span> Bobot Kriteria</h4>
    <h2><b>Harap Masukkan Bobot Tiap Kriteria</b></h2>
    <form action="" method="post">
        <table border="0" cellspacing="0" cellpadding="10" style="color:aliceblue;border-color:#FFBC8E;
    ;width:715px;height:498px;margin:auto;border-radius:10px;">

            <tr>
                <td style="color:black;text-align: left;padding-left:70px;"><label for="harga_sewa"><b>Harga </b></label></td>
                <td style="width:500px;text-align:left" ;"> <input style="width:90%;" type="text" name="harga_sewa" id="harga_sewa"> </td>
            </tr>
            <tr>
                <td style="color:black;text-align: left;padding-left:70px;"><label for="kapasitas_pengunjung"><b>Kapasitas </b> </label></td>
                <td style="width:500px;text-align:left" ;><input style="width:90%;" type="text" name="kapasitas_pengunjung" id="kapasitas_pengunjung"></td>
            </tr>
            <tr>
                <td style="color:black;text-align: left;padding-left:70px;"><label for="ukuran_lahan_parkir"><b>Parkir </b></label></td>
                <td style="width:500px;text-align:left" ;> <input style="width:90%;" type="text" name="ukuran_lahan_parkir" id="ukuran_lahan_parkir"></td>
            </tr>
            <tr>
                <td style="color:black;text-align: left;padding-left:70px;"> <label for="kelayakan_toilet"><b>Toilet</b> </label></td>
                <td style="width:500px;text-align:left" ;> <input style="width:90%;" type="text" name="kelayakan_toilet" id="kelayakan_toilet"></td>
            </tr>
            <tr>
                <td style="color:black;text-align: left;padding-left:70px;"> <label for="kelayakan_musholla"><b>Musholla</b> </label></td>
                <td style="width:500px;text-align:left" ;> <input style="width:90%;" type="text" name="kelayakan_musholla" id="kelayakan_musholla"></td>
            </tr>
            <tr>
                <td style="color:black;text-align: left;padding-left:70px;"> <label for="jarak"><b>Jarak </b> </label></td>
                <td style="width:500px;text-align:left" ;><input style="width:90%;" type="text" name="jarak" id="jarak"></td>
            </tr>
            <tr>
                <td style="color:black;text-align: left;padding-left:70px;"><label for="ketersediaan_konsumsi"><b>Konsumsi </b></label></td>
                <td style="width:500px;text-align:left" ;><input style="width:90%;" type="text" name="ketersediaan_konsumsi" id="ketersediaan_konsumsi"></td>
            </tr>
            <tr>
                <td style="color:black;text-align: left;padding-left:70px;"><label for="jenis_lapangan"><b>Jenis</b></label></td>
                <td style="width:500px;text-align:left" ;> <input style="width:90%;" type="text" name="jenis_lapangan" id="jenis_lapangan"></td>
            </tr>


        </table>
        <br>
        <br>

        <button type="submit" name="submit">Submit</button>

    </form>

    <p id="kesini">
        <?php if (isset($_POST['submit'])) :  ?>
            <?php
            arsort($AS);

            //-- ambil key-index yang pertama
            $terpilih = key($AS);
            echo "Dari hasil perhitungan dipilih alternatif ke-{$terpilih}"
                . " ({$alternative[$terpilih]}) <br>dengan nilai skor penilaian "
                . " sebesar {$AS[$terpilih]}";

            ?>
        <?php endif; ?>
    </p>

    <h1></span> <span class="yellow"></span>
    </h1>
</body>

</html>