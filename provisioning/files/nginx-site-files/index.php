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
	}
}
?>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
	<link rel="stylesheet" href="index.css">
	<title>Torneo di Teeworlds</title>
</head>
<body>
<h1>Torneo di Teeworlds (qui si può mettere un'immagine)</h1>
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

<div class="tab-content ml-3 mr-3" id="theTabs">
	<div class="tab-pane <?= $register ? '' : 'show active' ?>" id="torneo" role="tabpanel" aria-labelledby="torneo-tab">
		<h2>Il torneo</h2>
		<p>Qui si torna e si ritorna.</p>
		<p>Il torneo si terrà il giorno ... dalle ... alle ...</p>
		<p>Si vince in base a ... e il premio è una pacca sulla spalla</p>
		<p>Le regole sono:</p>
		<ul>
			<li>Lorem</li>
			<li>Ipsum</li>
			<li>Boh, soprattutto il boh</li>
		</ul>
		<p><a class="btn btn-primary" id="registrati-goto-button" data-toggle="tab" aria-controls="registrati" href="#registrati" onclick="$('#registrati-tab').tab('show')">Registrati!</a></p>
	</div>
	<div class="tab-pane" id="classifica" role="tabpanel" aria-labelledby="classifica-tab">
		<table class="table table-striped table-borderless">
			<thead class="thead-dark">
			<tr>
				<th scope="col">🏆</th>
				<th scope="col">Nome</th>
				<th scope="col">Kills</th>
				<th scope="col">Deaths</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<th>1</th>
				<td>ASd</td>
				<td>123</td>
				<td>456</td>
			</tr>
			</tbody>
		</table>
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
					<input name="nome" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" type="text" required="required" maxlength="16" class="form-control" id="registratiFormNome" aria-describedby="nomeHelp">
					<small id="nomeHelp" class="form-text text-muted">Il nome visualizzato nel gioco, massimo 16 caratteri (?).</small>
				</div>
				<div class="form-group">
					<label for="registratiFormEmail">Indirizzo email</label>
					<input name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" type="email" required="required" class="form-control" id="registratiFormEmail" aria-describedby="emailHelp">
					<small id="emailHelp" class="form-text text-muted">Utilizzato solo per il reset password che non c'è.</small>
				</div>
				<div class="form-group form-check">
					<input name="checkbox1" value="on" type="checkbox" required="required" class="form-check-input" id="registratiFormCheck1">
					<label class="form-check-label" for="registratiFormCheck1">Ho letto e accetto le condizioni generali e particolari e le cose della privacy e prometto di non barare.</label>
				</div>
				<button type="submit" class="btn btn-primary">Submit</button>
			</form>
		<?php endif; ?>
	</div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
</body>
</html>
