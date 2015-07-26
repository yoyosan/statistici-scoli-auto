<?php

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/vendor/autoload.php';

$app = new Silex\Application();
$dbFile = 'data/statistici.sqlite';
$dbConnection = new \PDO('sqlite:'.$dbFile);

/**
 * @param string $numeInstructor
 * @return array
 */
function existaInstructorul($numeInstructor, $idScoala)
{
    global $dbConnection;

    $stmtStatus = $dbConnection->prepare('SELECT id FROM instructor WHERE nume LIKE :nume AND id_scoala = :idScoala');
    $stmtStatus->execute(
        [
            'nume' => $numeInstructor,
            'idScoala' => $idScoala,
        ]
    );
    $exists = $stmtStatus->fetchAll(\PDO::FETCH_NUM);

    return $exists;
}

function existaJudet($judet)
{
    global $dbConnection;

    $stmtStatus = $dbConnection->prepare('SELECT id FROM judet WHERE nume LIKE :nume');
    $stmtStatus->execute(
        [
            'nume' => $judet,
        ]
    );
    $exists = $stmtStatus->fetchAll(\PDO::FETCH_NUM);

    return $exists;
}

function existaScoala($scoala, $idJudet)
{
    global $dbConnection;

    $stmtStatus = $dbConnection->prepare('SELECT id FROM scoala WHERE nume LIKE :nume AND id_judet = :idJudet');
    $stmtStatus->execute(
        [
            'nume' => $scoala,
            'idJudet' => $idJudet,
        ]
    );
    $exists = $stmtStatus->fetchAll(\PDO::FETCH_NUM);

    return $exists;
}

function existaStatistica($idInstructor)
{
    global $dbConnection;

    $stmtStatus = $dbConnection->prepare('SELECT id FROM statistici WHERE id_instructor = :id');
    $stmtStatus->execute(
        [
            'id' => $idInstructor,
        ]
    );
    $exists = $stmtStatus->fetchAll(\PDO::FETCH_NUM);

    return $exists;
}

function adaugaInstructor($numeInstructor, $idScoala)
{
    global $dbConnection;

    $stmtNew = $dbConnection->prepare('INSERT INTO instructor(nume, id_scoala) VALUES (:instructor, :idScoala)');
    $stmtNew->execute(
        [
            'instructor' => $numeInstructor,
            'idScoala' => $idScoala,
        ]
    );

    return $dbConnection->lastInsertId();
}

function adaugaScoala($scoala, $idJudet)
{
    global $dbConnection;

    $stmtNew = $dbConnection->prepare('INSERT INTO scoala(nume, id_judet) VALUES (:scoala, :idJudet)');

    $stmtNew->execute(
        [
            'scoala' => $scoala,
            'idJudet' => $idJudet,
        ]
    );

    return $dbConnection->lastInsertId();
}

function adaugaJudet($judet)
{
    global $dbConnection;

    $stmtNew = $dbConnection->prepare('INSERT INTO judet(nume) VALUES (:judet)');

    $stmtNew->execute(
        [
            'judet' => $judet,
        ]
    );

    return $dbConnection->lastInsertId();
}

function adaugaStatistica($idInstructor, $numarCandidatiExaminati, $numarCandidatiAdmisi, $procentajCandidatiAdmisi)
{
    global $dbConnection;

    $stmtNew = $dbConnection->prepare('INSERT INTO statistici VALUES (NULL, :p1, :p2, :p3, :p4)');

    $stmtNew->execute(
        [
            'p1' => $idInstructor,
            'p2' => $numarCandidatiExaminati,
            'p3' => $numarCandidatiAdmisi,
            'p4' => $procentajCandidatiAdmisi,
        ]
    );

    return $dbConnection->lastInsertId();
}

function adaugaStatus()
{
    global $dbConnection;

    $stmtNew = $dbConnection->prepare('INSERT INTO status VALUES (:p1, :p2)');

    $stmtNew->execute(
        [
            'p1' => \time(),
            'p2' => 1,
        ]
    );

    return $dbConnection->lastInsertId();
}

$app->get(
    '/{judet}',
    function ($judet) use ($app, $dbConnection) {
        $stmtStatus = $dbConnection->prepare('SELECT id FROM judet WHERE nume = :nume');
        $stmtStatus->execute(
            [
                'nume' => strtoupper($judet),
            ]
        );
        $idJudetBucuresti = $stmtStatus->fetchColumn(0);
        $html = <<<HTML
Nu sunt date. Click pe <a href="/procesare">procesare</a> pentru a popula tabela cu date.
HTML;

        $stmtStatus = $dbConnection->prepare('SELECT id, nume FROM scoala WHERE id_judet = :idJudet');
        $stmtStatus->execute(
            [
                'idJudet' => $idJudetBucuresti,
            ]
        );
        $scoli = $stmtStatus->fetchAll(\PDO::FETCH_ASSOC);

        if ($scoli) {
            $html = "";
        }

        $topScoli = [];
        $topInstructori = [];
        foreach ($scoli as $scoala) {
            $html .= '<h3 id="' . str_replace(' ', '_', $scoala['nume'])
                    . '"><a target="_blank" href="http://google.com/#q=' . urlencode($scoala['nume']) . '">'
                    . $scoala['nume'] . '</a> - <a href="#">Sus</a></h3>';
            $stmtStatus = $dbConnection->prepare('SELECT id, nume FROM instructor WHERE id_scoala = :id');
            $stmtStatus->execute(
                [
                    'id' => $scoala['id'],
                ]
            );
            $instructori = $stmtStatus->fetchAll(\PDO::FETCH_ASSOC);

            $html .= <<<HTML
<table>
    <tr>
        <th>Nume instructor</th>
        <th>Numar candidati examinati</th>
        <th>Numar candidati admisi</th>
        <th>Procentaj candidati admisi</th>
    </tr>
HTML;
;

            $totalCandidatiAdmisi = 0;
            $totalCandidatiExaminati = 0;
            foreach ($instructori as $instructor) {
                $stmtStatus = $dbConnection->prepare('SELECT * FROM statistici WHERE id_instructor = :id');
                $stmtStatus->execute(
                    [
                        'id' => $instructor['id'],
                    ]
                );
                $statistica = $stmtStatus->fetch(\PDO::FETCH_ASSOC);

                if ($statistica) {
                    $html .= <<<HTML
    <tr>
        <td>{$instructor['nume']}</td>
        <td>{$statistica['numar_candidati_examinati']}</td>
        <td>{$statistica['numar_candidati_admisi']}</td>
        <td>{$statistica['procentaj_candidati_admisi']}</td>
    </tr>
HTML;
                    $totalCandidatiAdmisi += $statistica['numar_candidati_admisi'];
                    $totalCandidatiExaminati += $statistica['numar_candidati_examinati'];
                }
            }

            $procentajTotalCandidatiAdmisi = round($totalCandidatiAdmisi / $totalCandidatiExaminati * 100, 2);

            $html .= <<<HTML
</table><hr />
<table>
    <tr>
        <th>Total candidati examinati</th>
        <th>Total candidati admisi</th>
        <th>Procentaj total candidati admisi</th>
    </tr>
    <tr>
        <td>{$totalCandidatiExaminati}</td>
        <td>{$totalCandidatiAdmisi}</td>
        <td>{$procentajTotalCandidatiAdmisi}</td>
    </tr>
</table>
HTML;
            if ($totalCandidatiExaminati >= 10) {
                $topScoli[$procentajTotalCandidatiAdmisi] = [
                    'nume' => $scoala['nume'],
                    'candidati_examinati' => $totalCandidatiExaminati,
                    'candidati_admisi' => $totalCandidatiAdmisi,
                    'procentaj_admisi' => $procentajTotalCandidatiAdmisi
                ];
            }
        }

        $topScoliHtml = <<<HTML
<table>
    <tr>
        <th>Nume scoala</th>
        <th>Total candidati examinati</th>
        <th>Total candidati admisi</th>
        <th>Procentaj total candidati admisi</th>
    </tr>
HTML;
        krsort($topScoli);
        foreach ($topScoli as $scor => $scoala) {
            $hashScoala = '#' . str_replace(' ', '_', $scoala['nume']);
            $topScoliHtml .= <<<HTML
    <tr>
        <td><a href="{$hashScoala}">{$scoala['nume']}</a></td>
        <td>{$scoala['candidati_examinati']}</td>
        <td>{$scoala['candidati_admisi']}</td>
        <td>{$scoala['procentaj_admisi']}</td>
    </tr>
HTML;
        }
        $topScoliHtml .= '</table>';

        $html = "<h1>Statistici pentru \"$judet\" 2014</h1>" . $topScoliHtml . $html;

        return new Response($html);
    }
)
->value('judet', 'Bucuresti');

$app->get(
    '/procesare',
    function () use ($app, $dbConnection) {
        $statistics = [];
        $statsFile = 'statistics/promovabilitate-scoli-instructori-2014.csv';

        $stmtStatus = $dbConnection->prepare('SELECT status FROM status');
        $stmtStatus->execute();
        $exists = $stmtStatus->fetchAll(\PDO::FETCH_NUM);

        if (count($exists) === 0) {
            if ($handle = fopen($statsFile, 'r+')) {
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $statistics[] = $data;
                }

                fclose($handle);
            }

            /**
             * $statistic[1] - judet scoala
             *      Daca denumirea este Total, continuam
             * $statistic[5] - denumire scoala
             * $statistic[14] - nume instructor
             * $statistic[20] - numar de canditati examinati
             * $statistic[22] - numar de candidati admisi
             * $statistic[25] - procentaj candidati admisi
             */
            $targetKeys = [1, 5, 14, 20, 22, 25];
            foreach ($statistics as $statistic) {

                $checkEmpty = true;
                foreach ($targetKeys as $key) {
                    if (strlen($statistic[$key]) === 0) {
                        $checkEmpty &= false;
                    }

                    if (!$checkEmpty) {
                        continue 2;
                    }
                }

                if (strtolower($statistic[14]) === 'total') {
                    continue;
                }

                if (stripos($statistic[1], 'jude') !== false) {
                    continue;
                }

                $judetScoala = $statistic[1];
                $numeScoala = $statistic[5];
                $numeInstructor = $statistic[14];
                $numarCandidatiExaminati = $statistic[20];
                $numarCandidatiAdmisi = $statistic[22];
                $procentajCandidatiAdmisi = $statistic[25];

                if ($judet = existaJudet($judetScoala)) {
                    $idJudet = $judet[0][0];
                } else {
                    $idJudet = adaugaJudet($judetScoala);
                }

                if ($scoala = existaScoala($numeScoala, $idJudet)) {
                    $idScoala = $scoala[0][0];
                } else {
                    $idScoala = adaugaScoala($numeScoala, $idJudet);
                }

                if ($instructor = existaInstructorul($numeInstructor, $idScoala)) {
                    $idInstructor = $instructor[0][0];
                } else {
                    $idInstructor = adaugaInstructor($numeInstructor, $idScoala);
                }

                if (!existaStatistica($idInstructor)) {
                    adaugaStatistica(
                        $idInstructor,
                        $numarCandidatiExaminati,
                        $numarCandidatiAdmisi,
                        $procentajCandidatiAdmisi
                    );
                }
            }

            adaugaStatus();
        }

        return new RedirectResponse('/');
    }
);

$app->run();