<!doctype html>
<html lang="it">
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
		$registerError = 'Email troppo lunga, non puoi accorciarla a 1000 caratteri max?';
	} else if(strlen($_POST['nome']) <= 0) {
		$registerError = 'Il nome non può essere vuoto';
	} else if(strlen($_POST['email']) <= 0) {
		$registerError = 'L\'indirizzo email non può essere vuoto';
	} else if($_POST['checkbox1'] !== 'on') {
		$registerError = 'Non hai accettato le condizioni';
	} else if(preg_match("#^[a-zA-Z0-9\-_ .,;:!?]+$#", $_POST['nome']) !== 1) {
		$registerError = 'Il nome contiene caratteri non validi';
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
?>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
	<link href="https://www.linux.it/shared/index.php?f=main.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" href="index.css">
	<title>Torneo di Teeworlds</title>
</head>
<body>
<div id="header">
<h1>Torneo di Teeworlds - Linux Day 2020</h1>
</div>
<div class="container mt-3">
<ul class="nav nav-tabs mb-3" role="tablist">
	<li class="nav-item" role="presentation">
		<a class="nav-link <?= $register ? '' : 'active' ?>" id="torneo-tab" data-toggle="tab" href="#torneo" role="tab" aria-controls="torneo" aria-selected="<?= $register ? 'false' : 'true' ?>">Torneo</a>
	</li>
	<li class="nav-item" role="presentation">
		<a class="nav-link" id="classifica-tab" data-toggle="tab" href="#classifica" role="tab" aria-controls="classifica" aria-selected="false">Classifica</a>
	</li>
	<li class="nav-item" role="presentation">
		<a class="nav-link <?= $register ? 'active' : '' ?>" id="registrati-tab" data-toggle="tab" href="#registrati" role="tab" aria-controls="registrati" aria-selected="<?= $register ? 'true' : 'false' ?>">Registrati</a>
	</li>
</ul>
<?php
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
<div class="tab-content ml-3 mr-3" id="theTabs">
	<div class="tab-pane <?= $register ? '' : 'show active' ?>" id="torneo" role="tabpanel" aria-labelledby="torneo-tab">
		<h2>Il torneo</h2>
		<p>Benvenuto nel torneo prode guerriero!</p>
		<p>La tua missione, se la vorrai accettare, sarà quella di scalare la vetta della gloria della galassia Tee diventando il fragger più spietato che si sia mai visto!</p>
		<p>Il torneo è un semplice death match vanilla con round fissi da dieci minuti.</p>
		<p>Registrarsi è semplice: premi il bottone qui in basso e inserisci lo username che intendi usare nel gioco e una email.</p>
		<p>L'email viene usata puramente per contattare il vincitore. Rispettiamo la privacy dei Tee quindi l'email è interamente opzionale - basta metterne una invalida durante la registrazione, come example@example.com - tuttavia in caso di assenza di un indirizzo email del vincitore, vista la nostra incapacità di identificare i giocatori se non appunto tramite mail, il premio andrà al primo posto che ha specificato un indirizzo valido.</p>
		<p>Il vincitore viene proclamato in base a un punteggio calcolato secondo la seguente formula: ***FORMULA***</p>
		<p>Le regole sono semplici:</p>
		<ul>
			<li>Non fare account doppi</li>
			<li>Non entrare con più di un profilo contemporaneamente</li>
			<li>Non fare niente che possa rovinare il gioco ad altri giocatori</li>
		</ul>
		<p>Il torneo si terrà il giorno ... dalle ... alle ...</p>
		<?php if($iscritti !== NULL && $iscritti >= 5): ?><p>Ci sono <?= $iscritti ?> giocatori iscritti al torneo!</p><?php endif; ?>
		<p><a class="btn btn-primary" id="registrati-goto-button" data-toggle="tab" aria-controls="registrati" href="#registrati" onclick="$('#registrati-tab').tab('show')">Registrati!</a></p>
	</div>
	<div class="tab-pane" id="classifica" role="tabpanel" aria-labelledby="classifica-tab">
		<table class="table table-striped table-borderless">
			<thead class="thead-dark">
			<tr>
				<th scope="col">🏆</th>
				<th scope="col">Nome</th>
				<th scope="col">Kills (uniche)</th>
				<th scope="col">Deaths</th>
			</tr>
			</thead>
			<tbody>
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

				$db->close();
				$db = NULL;

				unlink('update_in_progress.lock');
			}

			echo file_get_contents('classifica.html');
			?>
			</tbody>
		</table>

		<small>Ultimo aggiornamento: <?= date('Y-m-d H:i:s', $lastUpdate) ?></small>
	</div>
	<div class="tab-pane <?= $register ? 'show active' : '' ?>" id="registrati" role="tabpanel" aria-labelledby="registrati-tab">
		<?php if($register && $registerError === NULL): ?>
			<h2>Registrazione completata</h2>
			<p>Questa è la password <small>(case-insensitive)</small> che userai per entrare nel server:</p>
			<div class="alert alert-primary password" role="alert">
				<?= htmlspecialchars($password) ?>
			</div>
			<p>Salvala, stampala, scrivila, prendi nota, <strong>non avrai modo di tornare a questa pagina</strong>!</p>
		<?php else: ?>
			<h2>Registrati</h2>
			<?php if($registerError !== NULL): ?>
				<div class="alert alert-danger" role="alert">
					<?= $registerError ?>
				</div>
			<?php endif; ?>
			<form action="#" method="post">
				<div class="form-group">
					<label for="registratiFormNome">Nome nel gioco</label>
					<input name="nome" pattern="[a-zA-Z0-9\-_ .,;:!?]+" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" type="text" required="required" maxlength="15" class="form-control" id="registratiFormNome" aria-describedby="nomeHelp">
					<small id="nomeHelp" class="form-text text-muted">Il nome visualizzato nel gioco, massimo 15 caratteri. Sono ammessi caratteri alfanumerici, spazio, trattino, underscore e alcuni segni di punteggiatura: .,;:!?</small>
				</div>
				<div class="form-group">
					<label for="registratiFormEmail">Indirizzo email</label>
					<input name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" type="email" required="required" class="form-control" id="registratiFormEmail" aria-describedby="emailHelp">
					<small id="emailHelp" class="form-text text-muted">Utilizzato solo per contattare il vincitore.</small>
				</div>
				<div class="form-group form-check">
					<input name="checkbox1" value="on" type="checkbox" required="required" class="form-check-input" id="registratiFormCheck1">
					<label class="form-check-label" for="registratiFormCheck1">Ho letto e accetto le condizioni generali e particolari e le cose della privacy e prometto di non barare.</label>
				</div>
				<button type="submit" class="btn btn-primary">Invia</button>
			</form>
		<?php endif; ?>
	</div>
</div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
</body>
</html>
