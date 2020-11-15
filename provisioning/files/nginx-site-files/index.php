<!DOCTYPE HTML>

<?php

function genpassword() {
	$characters = '123456789abcdefghijklmnpqrstuvwxyz';
	$password = '';
	for ($i = 0; $i < 15; $i++) {
		$password .= $characters[mt_rand(0, 34 - 1)];
	}
	return $password;
}

include 'config.php';

// Not a form submission
$register = false;

// If this is a form submission
if(isset($_POST) && isset($_POST['nome']) && isset($_POST['email']) && isset($_POST['checkbox1'])) {
	$register = true;
	$registerError = NULL;
	if(strlen($_POST['nome']) > 16) {
		$registerError = 'Nome troppo lungo';
	} else if(strlen($_POST['email']) > 1000) {
		$registerError = 'Email troppo lunga, non puoi accorciarla a 1000 caratteri?';
	} else if(strlen($_POST['nome']) <= 0) {
		$registerError = 'Il nome non può essere vuoto';
	} else if(strlen($_POST['email']) <= 0) {
		$registerError = 'L\'indirizzo email non può essere vuoto';
	} else if($_POST['checkbox1'] !== 'on') {
		$registerError = 'Non hai accettato le condizioni';
	} else if(preg_match("#^[a-zA-Z0-9\-_ .,;:!?]+$#", $_POST['nome']) !== 1) {
		$registerError = 'Il nome contiene caratteri non validi';
	} else if(!REGISTRATIONS_ENABLED) {
        $registerError = 'Le registrazioni sono chiuse';
    }
	if($registerError === NULL) {
		// Generate password
		$password = genpassword();

		// Open database for writing, so it's locked
		$db = new SQLite3(DATABASE_PATH, SQLITE3_OPEN_READWRITE);

		// Select identical passwords, to retry in case there are any
		$stmt = $db->prepare("SELECT true FROM players WHERE password = ?");
		$stmt->bindParam(1, $password, SQLITE3_TEXT);

		$valid = false;
		// TODO: add an attempts limit then die()?
		while(!$valid) {
			$result = $stmt->execute();
			if($result === false) {
				die('Error 1');
			}
			// Any result = password exists
			if($result->fetchArray(SQLITE3_NUM) !== false) {
				// Generate another one
				$password = genpassword();
				// Bound parameter is already bound
				// reset required in older PHP version
				$result->finalize();
				$stmt->reset();
			} else {
				$valid = true;
			}
		}
		$stmt->close();

		// Now check name and email
		$stmt = $db->prepare("SELECT true FROM players WHERE name = ? OR email = ?");
		$stmt->bindValue(1, $_POST['nome']);
		$stmt->bindValue(2, $_POST['email']);
		$result = $stmt->execute();
		if($result === false) {
			die('Error 2');
		}
		if($result->fetchArray(SQLITE3_NUM) !== false) {
			$registerError = 'Nome o email già utilizzati';
		}
		$stmt->close();

		if(!$registerError) {
			$stmt = $db->prepare("INSERT INTO players(name, email, password) VALUES (?, ?, ?)");
			$stmt->bindValue(1, $_POST['nome']);
			$stmt->bindValue(2, $_POST['email']);
			$stmt->bindValue(3, $password);
			$result = $stmt->execute();
			if($result === false) {
				$registerError = 'Errore di scrittura del database';
			} else {
				$stmt->close();
			}
		}

		// Release the lock
		$db->close();
		$db = NULL;
	}
}

$db = new SQLite3(DATABASE_PATH, SQLITE3_OPEN_READONLY);
$iscritti = $db->query("SELECT COUNT(*) FROM players;");
$iscritti = $iscritti->fetchArray(SQLITE3_NUM);
$db->close();
$db = NULL;
if($iscritti) {
	$iscritti = $iscritti[0];
} else {
	$iscritti = NULL;
}

?>

<html>
	<head>
		<title>Paradigm Shift by HTML5 UP</title>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
		<meta name="description" content="" />
		<meta name="keywords" content="" />
		<link rel="stylesheet" href="assets/css/main.css" />
		<link rel="stylesheet" href="assets/css/alert.css" />

        <!-- Override the last element in the history with a GET request to the webpage itself. -->
        <!-- This is done to prevent form resubmissions if someone refreshes after signing up.  -->
        <script>
            if(window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        </script>

        <!-- Configure MathJax -->
        <script>
            MathJax = {
                options: {
                    enableMenu: false
                },
                chtml: {
                    scale: 1,
                    minscale: 1,
                    matchFontHeight: true,
                    mtextInheritFount: true
                }
            }
        </script>
	</head>
	<body class="is-preload">
		<div id="wrapper">
			<section class="intro">
				<header>
					<h1>Torneo Teeworlds</h1>
					<p>Sabato 28 Novembre</p>
					<ul class="actions">
						<li><a href="#first" class="arrow scrolly"><span class="label">Next</span></a></li>
					</ul>
				</header>
				<div class="content">
					<span class="image fill" data-position="center"><img src="images/pic01.jpg" alt="" /></span>
				</div>
			</section>

			<section id="first">
				<header>
					<h2>Intro</h2>
				</header>
				<div class="content">
                    <h2>Il torneo</h2>
                    <p>Benvenuto nel torneo, prode guerriero!</p>
                    <p>La tua missione, se la vorrai accettare, sarà quella di scalare la vetta della gloria della galassia Tee diventando il fragger più spietato che si sia mai visto!</p>
                    <br>
                    <p>Il torneo è un semplice death match vanilla con round fissi da dieci minuti.</p>
                    <p>Registrarsi è semplice: premi il bottone qui in basso e inserisci lo username che intendi usare nel gioco e una email.</p>
                    <br>
                    <p>Il vincitore viene proclamato in base a un punteggio calcolato secondo la seguente formula:</p>
                    <p>$$punteggio =  \frac{kill \cdot kill\ uniche}{(death + 1) \cdot giocatori} $$</p>
                    <p>Dove <b>kill uniche</b> è il numero di giocatori distinti uccisi durante il torneo e <b>giocatori</b> è il numero totale di giocatori iscritti.</p>
                    <p>Le regole sono semplici:</p>
                    <ul>
                        <li>Non fare account doppi</li>
                        <li>Non entrare con più di un profilo contemporaneamente</li>
                        <li>Non fare niente che possa rovinare il gioco ad altri giocatori</li>
                    </ul>
                    <p>Il torneo si terrà il giorno Sabato 28 Novembre dalle 15:00 a oltranza</p>
                    <?php if($iscritti !== NULL && $iscritti >= 5): ?><p>Ci sono attualmente <?= $iscritti ?> giocatori iscritti al torneo!</p><?php endif; ?>
                </div>
            </section>
            <?php
            if(file_exists('classifica.html')) {
                $lastUpdate = filemtime('classifica.html');
            } else {
                $lastUpdate = 0;
            }
            // Old file
            if($lastUpdate + CLASSIFICA_UPDATE_SECONDS < time() && !file_exists('update_in_progress.lock')) {
                touch('update_in_progress.lock');
                $lastUpdate = time();
                // Update it
                $db = new SQLite3(DATABASE_PATH, SQLITE3_OPEN_READONLY);

                // TODO: WHERE ... with a timetstamp to filter different turns
                $stmt = $db->prepare("
                    SELECT k.name AS killer, d.name AS killed
                    FROM kills
                    JOIN players AS k ON killer = k.password
                    JOIN players AS d ON killed = d.password
                    ;");

                $result = $stmt->execute();
                if($result === false) {
                    die("Error 3");
                }

                // TODO: can this cause a division by 0?
                // Player => Points calculated according to (K/(D+1))*(unique_players_killed/total_players)
                $players = [];

                $killsCounter = [];
                $deathsCounter = [];
                $uniqueKills = [];

                while(($row = $result->fetchArray(SQLITE3_ASSOC)) !== false) {
                    $k = $row['killer'];
                    $d = $row['killed'];

                    // Initialize values if we never encountered this player
                    if(!isset($players[$k])) {
                        $players[$k] = 0;
                        $killsCounter[$k] = 0;
                        $deathsCounter[$k] = 0;
                        $uniqueKills[$k] = [];
                    }
                    if(!isset($players[$d])) {
                        $players[$d] = 0;
                        $killsCounter[$d] = 0;
                        $deathsCounter[$d] = 0;
                        $uniqueKills[$d] = [];
                    }

                    if($k === $d) {
                        // Special case
                        $deathsCounter[$d]++;
                    } else {
                        // +1 kill
                        $killsCounter[$k]++;
                        // +1 death
                        $deathsCounter[$d]++;
                        // Add to uniques
                        if(!isset($uniqueKills[$k][$d])) {
                            $uniqueKills[$k][$d] = 0;
                        }
                        // Can be counted if we want (how many times player X killed player Y)
                        // $uniqueKills[$k][$d]++;
                    }
                }
                
                // Only create the file if the database is not empty
                if(!empty($players)) {
                    foreach($players as $player => &$points) {
                        $points = ($killsCounter[$player] / ($deathsCounter[$player] + 1)) * (count($uniqueKills[$player]) / count($players));
                    }

                    arsort($players);

                    $html = '';
                    $i = 1;
                    // It's sorted now
                    foreach($players as $player => &$points) {
                        $name = htmlspecialchars($player);
                        $unique = count($uniqueKills[$player]);
                        $html .= "
                            <tr>
                                <th>$i</th>
                                <th>$player</th>
                                <td>${killsCounter[$player]} ($unique)</td>
                                <td>${deathsCounter[$player]}</td>
                            </tr>";
                        $i++;
                    }

                    file_put_contents('classifica.html', $html);
                    $html = NULL;
                }
                else
                {
                    unlink('classifica.html');
                }
                
                $db->close();
                $db = NULL;

                unlink('update_in_progress.lock');
            }

            if(file_exists('classifica.html')) {
                $leaderboard = file_get_contents('classifica.html');
                $updateString = date('Y-m-d H:i:s', $lastUpdate);
                $section = "
            <section>
                <header>
                    <h2>Classifica</h2>
                </header>
                <div class=\"content\">
                    <table class=\"table table-striped table-borderless\">
                        <thead class=\"thead-dark\">
                            <tr>
                                <th scope=\"col\">🏆</th>
                                <th scope=\"col\">Nome</th>
                                <th scope=\"col\">Kills (uniche)</th>
                                <th scope=\"col\">Deaths</th>
                            </tr>
                        </thead>
                        <tbody>
                            $leaderboard
                        </tbody>
                    </table>

                    <small>Ultimo aggiornamento: $updateString</small>
                </div>
            </section>
                ";
                
                echo $section;
            }
            ?>

			<section id="registrati">
				<header>
					<h2>Registrati</h2>
				</header>
				<div class="content">
                    <?php if($register && $registerError === NULL): ?>
                        <h2>Registrazione completata</h2>
                        <p>Questa è la password <small>(case-insensitive)</small> che userai per entrare nel server:</p>
                        <div class="alert alert-primary password" role="alert">
                            <?= htmlspecialchars($password) ?>
                        </div>
                        <p>Salvala, stampala, scrivila, prendi nota, <strong>non avrai modo di tornare a questa pagina</strong>!</p>
                    <?php elseif(REGISTRATIONS_ENABLED): ?>
                        <h2>Registrati</h2>
                        <?php if($registerError !== NULL): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= $registerError ?>
                            </div>
                            <br>
                        <?php endif; ?>
                        <form action="#registrati" method="post">
                            <div class="form-group">
                                <label for="registratiFormNome">Nome nel gioco</label>
                                <input name="nome" pattern="[a-zA-Z0-9\-_ .,;:!?]+" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" type="text" required="required" maxlength="15" class="form-control" id="registratiFormNome" aria-describedby="nomeHelp">
                                <small id="nomeHelp" class="form-text text-muted">Il nome visualizzato nel gioco, massimo 15 caratteri. Sono ammessi caratteri alfanumerici, spazio, trattino, underscore e alcuni segni di punteggiatura: .,;:!?</small>
                            </div>
                            <br>
                            <div class="form-group">
                                <label for="registratiFormEmail">Indirizzo email</label>
                                <input name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" type="email" required="required" class="form-control" id="registratiFormEmail" aria-describedby="emailHelp">
                                <small id="emailHelp" class="form-text text-muted">Viene utilizzato solo per contattare i vincitori!</small>
                            </div>
                            <br>
                            <div class="form-group form-check">
                                <input name="checkbox1" value="on" type="checkbox" required="required" class="form-check-input" id="registratiFormCheck1">
                                <label class="form-check-label" for="registratiFormCheck1">Ho letto <a href="#first">quanto sopra riportato</a> e prometto di non barare.</label>
                            </div>
                            <button type="submit" class="btn btn-primary">Invia</button>
                        </form>
                    <?php else: ?>
                        <h2>Registrati</h2>
                        <?php
                            if($registerError === NULL)
                            {
                                echo "<div class=\"alert alert-info\" role=\"alert\">Le registrazioni sono chiuse.</div>";
                            } else {
                                echo "<div class=\"alert alert-danger\" role=\"alert\">$registerError</div>";
                            }
                        ?>
                    <?php endif; ?>
				</div>
			</section>

			<div class="copyright">Powered by <a href="https://www.ils.org/">Italian Linux Society</a>. Design: <a href="https://html5up.net">HTML5 UP</a>.</div>
		</div>

		<script src="assets/js/jquery.min.js"></script>
		<script src="assets/js/jquery.scrolly.min.js"></script>
		<script src="assets/js/browser.min.js"></script>
		<script src="assets/js/breakpoints.min.js"></script>
		<script src="assets/js/util.js"></script>
        <script src="assets/js/main.js"></script>
        <script src="assets/js/mathjax/tex-svg.js" async></script>
	</body>
</html>
