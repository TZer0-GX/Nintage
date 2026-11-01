<?php
require __DIR__.'/includes/bootstrap.php';
if(current_user()) redirect('account.php');
$errors=[];$username='';$email='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$username=trim((string)($_POST['username']??''));$email=trim((string)($_POST['email']??''));$password=(string)($_POST['password']??'');$confirm=(string)($_POST['password_confirm']??'');
 if($username===''||mb_strlen($username)>15)$errors[]='Le pseudo doit contenir entre 1 et 15 caractères.';
 if(!preg_match('/^[\p{L}\p{N}_ .-]{1,15}$/u',$username))$errors[]='Le pseudo peut contenir lettres, chiffres, espaces, points, tirets et _.';
 if($email===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($email)>254)$errors[]='Saisis une adresse e-mail au format valide. Elle ne sera pas vérifiée par e-mail.';
 if($password==='')$errors[]='Le mot de passe ne peut pas être vide.';
 if($password!==$confirm)$errors[]='Les deux mots de passe ne correspondent pas.';
 if(!$errors){$s=$pdo->prepare('SELECT id FROM users WHERE username=:u LIMIT 1');$s->execute(['u'=>$username]);if($s->fetch())$errors[]='Ce pseudo est déjà utilisé.';}
 if(!$errors){try{$hash=password_hash($password, defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_DEFAULT);$s=$pdo->prepare('INSERT INTO users(username,email,password_hash,role) VALUES(:u,:e,:p,\'utilisateur\')');$s->execute(['u'=>$username,'e'=>$email,'p'=>$hash]);session_regenerate_id(true);$_SESSION['user']=['id'=>(int)$pdo->lastInsertId(),'username'=>$username,'email'=>$email,'role'=>'utilisateur'];flash('Compte créé. Bienvenue sur Nin-tage !','success');redirect('account.php');}catch(PDOException $e){$errors[]='Impossible de créer le compte. Vérifie que le pseudo est unique.';error_log($e->getMessage());}}
}
$pageTitle='Créer un compte';require __DIR__.'/includes/header.php';
?>
<section class="panel"><h1>Créer un compte</h1><p>Les champs marqués sont nécessaires. Aucun e-mail de validation n’est envoyé.</p><?php foreach($errors as $err): ?><div class="flash error"><?=e($err)?></div><?php endforeach; ?><form method="post" autocomplete="on"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><div class="field"><label for="username">Pseudo <span class="help" tabindex="0">?<span>15 caractères maximum. Lettres, chiffres, espaces, points, tirets et underscore sont acceptés.</span></span></label><input id="username" name="username" maxlength="15" required value="<?=e($username)?>" autocomplete="username"></div><div class="field"><label for="email">Adresse e-mail <span class="help" tabindex="0">?<span>Utilisée pour ton compte. Pas de vérification par e-mail dans cette version.</span></span></label><input id="email" type="email" name="email" maxlength="254" required value="<?=e($email)?>" autocomplete="email"></div><div class="field"><label for="password">Mot de passe <span class="help" tabindex="0">?<span>Pas de règle de composition imposée. Choisis tout de même un mot de passe unique et difficile à deviner.</span></span></label><input id="password" type="password" name="password" required autocomplete="new-password"></div><div class="field"><label for="password_confirm">Confirmer le mot de passe</label><input id="password_confirm" type="password" name="password_confirm" required autocomplete="new-password"></div><button class="btn" type="submit">Créer mon compte</button></form><p>Déjà inscrit ? <a href="<?=e(url('login.php'))?>">Connexion</a></p></section>
<?php require __DIR__.'/includes/footer.php'; ?>
