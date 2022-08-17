<?php
/*
 * DATE OF CREATION: 18.11.2016, 11:11:55
 * ENCODING: UTF-8 
 * @author : _apinek
 * VERZIA : 2.0 (20170304_2124)
 * 
 *  - nacitanie konstant
 *  - nacitanie triedy soap - nastavenia
 *  - rozdelenie co sa ma nacitat podla toho co chceme poslat 
 * 
 */

function getPremenna($premenna) {
    if (filter_input(INPUT_GET, $premenna, FILTER_DEFAULT, FILTER_REQUIRE_SCALAR) <> NULL) {   // normalne hodnota
        return filter_input(INPUT_GET, $premenna, FILTER_DEFAULT, FILTER_REQUIRE_SCALAR);
    } elseif (filter_input(INPUT_GET, $premenna, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) <> NULL) {  // pole - tak ako je v $_GET
        return filter_input(INPUT_GET, $premenna, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    } else {
        return NULL;  // prazdna hodnota
    }
}

function getVaGe($premenna) {
    global $get;   // prenasane premenne mimo $_POST su v poli $post
    if (isset($get[$premenna])) {
        return $get[$premenna];
    } else {
        return getPremenna($premenna);
    }
}

function print_era($premenna, $nazov = '', $farba = '#f7c8d4') {    // print_er pre apiho 
    if (is_array($premenna)) {
        echo '<pre class="apiNotes" title="' . debug_backtrace()[0]['file'] . '  [ ' . debug_backtrace()[0]['line'] . ' ] "  style="background-color: ' . $farba . '; text-align: left; overflow-x: auto; "><h3><strong>' . $nazov . '</strong></h3>';
        print_r($premenna);
        echo '</pre>';
    } else {
        echo '<pre class="apiNotes" title="' . debug_backtrace()[0]['file'] . '  [ ' . debug_backtrace()[0]['line'] . ' ] "  style="background-color: ' . $farba . '; text-align: left; overflow-x: auto; font-size: 12px; ">';
        if ($nazov <> '') {
            echo '<em><b>' . $nazov . '</b></em> =';
        }
        echo ' <b>' . $premenna . '</b>';
        echo '</pre>';
    }
}

error_reporting(E_ALL); // for hide original errors messages
//define('ERROR_HANDLING', TRUE); // for postprocessing
define('POCET_ZOBRAZENYCH', 30);

class eDb {

    private static $spojenie;
    private static $nastavenie = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8",
        PDO::ATTR_EMULATE_PREPARES => false,
    );

    // pripojenie k DB
    public static function pripoj() {
        if (!isset(self::$spojenie)) {
//            $host = '92.240.254.155';
//            $databaze = 'db9067xerrlog';
//            $uzivatel = 'db9067xerrlog';
//            $heslo = 'LogErr2017';
            $host = '185.14.253.96';
            $databaze = 'DBerrlog';
            $uzivatel = 'errlog';
            $heslo = 'Errlog*2018';

            try {
                self::$spojenie = new PDO(
                        "mysql:host=$host;dbname=$databaze", $uzivatel, $heslo, self::$nastavenie
                );
            } catch (PDOException $ex) {
                die('not working...');
            }
        }
    }

    public static function dotaz($dotaz, $parametre = array()) {
        try {
            $navrat = self::$spojenie->prepare($dotaz);
            $navrat->execute($parametre);
            return $navrat->rowCount();
        } catch (Exception $e) {
            echo $e;
            return 0;
        }
    }

    public static function dotazVsetky($dotaz, $parametre = array()) {
        try {
            $navrat = self::$spojenie->prepare($dotaz);
            $navrat->execute($parametre);
            return $navrat->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            echo $e;
        }
    }

}

function cas($sekundy) {
    $minuty = round($sekundy / 60);
    $hodiny = round($sekundy / 3600);
    $dni = round($sekundy / 86400);
    if ($dni > 1) {
        return '<b>' . $dni . '</b> [dni]';
    } elseif ($hodiny > 1) {
        return '<b>' . $hodiny . '</b> [hod]';
    } elseif ($minuty > 1) {
        return '<b>' . $minuty . '</b> [min]';
    } else {
        return '<span style="color: red"><b>' . $sekundy . '</b> [sek]</span>';
    }
}

$adresaUrl = [
    'pvmsystemVS' => 'https://www.pvmsystem.sk',
    'tuliatuli_sk' => 'https://www.tuliatuli.sk',
    'tuliatuli_cz' => 'https://www.tuliatuli.cz',
    'tuliatuli_at' => 'https://www.tuliatuli.at',
    'aureus' => 'https://www.aureus.sk',
];
?>
<html>
    <head>
        <title>aureus - ErrLog</title>
    </head>
    <body>
        <div style="padding-left: 5px; ">
            <!--<button onclick="location.reload()">Refresh</button><br />-->
            <?php
            eDb::pripoj();
            $dotazT = eDb::dotazVsetky('SHOW TABLES', []);
            $tabulky = [];
            foreach ($dotazT as $tabulka) {
                $tsQ = eDb::dotazVsetky('SELECT MAX(err_TS) AS maxTs FROM ' . $tabulka['Tables_in_DBerrlog'], []);
                $tabulky[$tabulka['Tables_in_DBerrlog']] = $tsQ[0]['maxTs'];
                $tsQC = eDb::dotazVsetky('SELECT COUNT(err_videne) AS videne FROM ' . $tabulka['Tables_in_DBerrlog'] . ' WHERE err_videne = ?', [0]);
                $tabulkyCount[$tabulka['Tables_in_DBerrlog']] = $tsQC[0]['videne'];
            }
            arsort($tabulky);
            foreach ($tabulky as $tabulka => $cas) {
                ?>
                <br /><button onclick="location.href = 'index.php?tabulka=<?= $tabulka ?>'" style="<?= ($tabulka == getPremenna('tabulka')) ? 'background-color: #269abc' : '' ?>">
                    <?= $tabulka ?>  /  <?= $cas ?> - <?= !empty($cas) ? cas(time() - strtotime($cas)) . ' → ' . $tabulkyCount[$tabulka] : 'NaN' ?>
                </button>
                <?php
            }
            $tabulka = (getVaGe('tabulka') == null) ? 'apiONaureus' : getVaGe('tabulka');

            $page = (getVaGe('page') != NULL) ? getVaGe('page') : 1;
            $dotazNo = eDb::dotazVsetky('SELECT err_id FROM ' . $tabulka, []);
            $pageMax = ceil(count($dotazNo) / POCET_ZOBRAZENYCH);
            if (getVaGe('zaznam') != NULL) {
                $dotazZ = eDb::dotazVsetky('SELECT * FROM ' . $tabulka . ' WHERE err_id = ?', [getVaGe('zaznam')])[0];
                $dotazQ = eDb::dotazVsetky('SELECT * FROM ' . $tabulka
                                . ' WHERE err_errno = ? '
                                . 'AND err_errfile = ? '
                                . 'AND err_errline = ? '
                                . 'AND err_errtext = ? '
                                . 'ORDER BY err_id DESC LIMIT ' . (POCET_ZOBRAZENYCH * ($page - 1)) . ',' . POCET_ZOBRAZENYCH . '', [$dotazZ['err_errno'], $dotazZ['err_errfile'], $dotazZ['err_errline'], $dotazZ['err_errtext']]);
            } else {
                $dotazQ = eDb::dotazVsetky('SELECT * FROM ' . $tabulka . ' ORDER BY err_id DESC LIMIT ' . (POCET_ZOBRAZENYCH * ($page - 1)) . ',' . POCET_ZOBRAZENYCH . '', []);
            }
            $chyby = [];
//foreach ($dotazQ as $key => $item) {
//    $chyby[$item['err_TS']][$key] = $item;
//}
////print_er($chyby, $tabulka . '  */*   ' . date('Y-m-d H:i:s', time()));
            ?>

            <style>
                table {
                    border-collapse: collapse;
                }
                table, th, td {
                    border: 2px solid black; 
                    vertical-align: top;
                }
            </style>
            <div style="padding: 20px; background-color: #AAD2E7">
                <form action="http://er.aureus.sk/index.php">
                    <input type="hidden" name="tabulka" value="<?= getPremenna('tabulka') ?>">
                    <input type="text" name="zaznam" value="<?= getPremenna('zaznam') ?>">
                    <button>Zobraz podobné</button><br />
                    <button type="button" onclick="location.href = 'index.php?tabulka=<?= getPremenna('tabulka') ?>'">Zobraz vsetky</button>
                </form>
            </div>
            <table style="background-color: #31b0d5; width: 100%">
                <th colspan="3">
                    <h3>
                        <?= $tabulka . '  */*   ' . date('Y-m-d H:i:s', time()) ?> */* zobrazených : <?= POCET_ZOBRAZENYCH ?> z <?= count($dotazNo) ?>&nbsp;&nbsp;&nbsp;&nbsp;
                        <button onclick="location.href = 'errlog?tabulka=<?= $tabulka ?>&page=<?= $page - 1 ?>'" <?= ($page == 1) ? 'disabled' : '' ?>><</button>
                        <button disabled="" style="background-color: #AAD2E7"><b><?= $page ?></b></button>
                        <button onclick="location.href = 'errlog?tabulka=<?= $tabulka ?>&page=<?= $page + 1 ?>'" <?= ($page == $pageMax) ? 'disabled' : '' ?>>></button>
                    </h3>
                </th>
                <tr>
                    <td><b>ID</b></td>
                    <td><b>ErrTS / rozdiel času</b></td>
                    <td><b>Chyba</b></td>
                </tr>
                <?php
                foreach ($dotazQ as $chyba) {
                    $rozdielCasu = time() - strtotime($chyba['err_TS']);
//                    if (isset($adresaUrl[getPremenna('tabulka')]) && substr($chyba['err_url'], -4) != '.php') {
                    if (isset($adresaUrl[getPremenna('tabulka')])) {
                        $url = '<a href="' . $adresaUrl[getPremenna('tabulka')] . $chyba['err_url'] . '" target="_blank">' . $adresaUrl[getPremenna('tabulka')] . $chyba['err_url'] . '</a>';
                    } else {
                        $url = $chyba['err_url'];
                    }
                    $chyba_errtext = explode('JSON', $chyba['err_errtext'])[0];
                    if (substr($chyba_errtext, 0, 1) == '{') {
                        $chyba_errtext = json_decode($chyba_errtext, true);
                    }
                    $chyba_backtrace = explode('JSON', $chyba['err_errtext'])[1] ?? '';
                    ?>
                    <tr <?= ($chyba['err_videne'] == 0) ? 'style="background-color: wheat"' : '' ?>>
                        <td style="width: 30px"><?= $chyba['err_id'] ?></td>
                        <td style="width: 170px"><?= $chyba['err_TS'] ?> <br /><br />rozdiel: <b><?= cas($rozdielCasu) ?></b></td>
                        <td style="width: auto">
                            <div><b>Prihlaseny : </b><?= $chyba['err_prihlaseny'] ?></div>
                            <div><b>IP : </b><?= (isset($chyba['err_IP'])) ? $chyba['err_IP'] : $chyba['err_IPo'] ?></div>
                            <div><b>URL : </b><?= $url ?></div>
                            <div>Typ chyby : <b><?= $chyba['err_errno'] ?></b></div>
                            <div><b>Subor : </b><?= $chyba['err_errfile'] ?></div>
                            <div><b>Riadok :</b><?= $chyba['err_errline'] ?></div>
                            <div style="max-width: 1100px"><?= print_era($chyba_errtext) ?></div>
                            <div style="max-width: 1100px">
                                <?php if (!empty($chyba_backtrace)) { ?>
                                    <?= print_era(json_decode($chyba_backtrace, true)) ?>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
//print_er($chyby, $tabulka . '  */*   ' . date('Y-m-d H:i:s', time()));
                if (getPremenna('tabulka') != NULL) {
                    eDb::dotaz('UPDATE ' . getVaGe('tabulka') . ' SET `err_videne` = ?', [1]);
                }
                ?>
            </table>
        </div>
    </body>
</html>
