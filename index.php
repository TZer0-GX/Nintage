<?php
require __DIR__.'/includes/bootstrap.php';
$pageTitle='Bibliothèque';
$q=trim((string)($_GET['q']??'')); $platform=trim((string)($_GET['platform']??'')); $tagSlug=trim((string)($_GET['tag']??''));
$tagStmt=$pdo->prepare('SELECT id,name,slug FROM tags WHERE slug=:slug LIMIT 1'); $tagStmt->execute(['slug'=>$tagSlug]); $activeTag=$tagStmt->fetch();
$sql='SELECT g.*, (SELECT GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ", ") FROM game_tags gt JOIN tags t ON t.id=gt.tag_id WHERE gt.game_id=g.id) tags FROM games g WHERE 1=1'; $args=[];
if($q!==''){$sql.=' AND g.title LIKE :q';$args['q']='%'.$q.'%';}
$platforms=['NES','Game Boy','Game Boy Color','Super Nintendo','Nintendo 64','PlayStation 1'];
if(in_array($platform,$platforms,true)){$sql.=' AND g.platform=:platform';$args['platform']=$platform;}
if($activeTag){$sql.=' AND EXISTS (SELECT 1 FROM game_tags fgt WHERE fgt.game_id=g.id AND fgt.tag_id=:tag_id)';$args['tag_id']=$activeTag['id'];}
$sql.=' ORDER BY g.title ASC'; $stmt=$pdo->prepare($sql);$stmt->execute($args);$games=$stmt->fetchAll();
require __DIR__.'/includes/header.php';
?>
<section class="panel"><h1>Bibliothèque Nin-tage</h1><p>La salle d’arcade rétro, sans la pièce de 10 francs coincée dans la machine.</p><form method="get" class="two-col"><div class="field"><label for="q">Rechercher un jeu</label><input id="q" name="q" value="<?=e($q)?>" placeholder="Titre du jeu"></div><div class="field"><label for="platform">Plateforme</label><select id="platform" name="platform"><option value="">Toutes les plateformes</option><?php foreach($platforms as $p): ?><option <?= $platform===$p?'selected':'' ?> value="<?=e($p)?>"><?=e($p)?></option><?php endforeach; ?></select></div><div><button class="btn" type="submit">Rechercher</button></div></form></section>
<section class="panel"><h2>Les jeux<?= $activeTag ? ' : '.e($activeTag['name']) : '' ?></h2><?php if($activeTag): ?><p><a class="btn" href="<?=e(url('index.php'))?>">Afficher toute la bibliothèque</a></p><?php endif; ?><?php if(!$games): ?><p>Aucun jeu pour l’instant. Un administrateur pourra en ajouter depuis l’interface Admin.</p><?php else: ?><div class="game-grid"><?php foreach($games as $g): ?><article class="game-card"><a class="game-card-main" href="<?=e(url('game.php?id='.(int)$g['id']))?>"><img loading="lazy" src="<?=e(url($g['thumbnail_path'] ?: 'assets/images/games/mega-man-ii.svg'))?>" alt="Miniature de <?=e($g['title'])?>"><h3><?=e($g['title'])?></h3></a><p class="game-platform-label"><?=e($g['platform'])?></p><?php $cardTags=$pdo->prepare('SELECT name,slug FROM tags t JOIN game_tags gt ON gt.tag_id=t.id WHERE gt.game_id=:id ORDER BY t.name');$cardTags->execute(['id'=>$g['id']]); ?><div class="tag-row"><?php foreach($cardTags->fetchAll() as $ct): ?><a class="tag <?=e(tag_class($ct['slug']))?>" href="<?=e(url('index.php?tag='.rawurlencode($ct['slug'])))?>"><?=e($ct['name'])?></a><?php endforeach; ?></div></article><?php endforeach; ?></div><?php endif; ?></section>
<section id="about" class="panel"><h2>À propos</h2><p>Nin-tage est un catalogue communautaire de jeux rétro. Les ROMs et documents doivent être ajoutés uniquement si tu as le droit de les héberger et de les distribuer.</p></section>
<?php require __DIR__.'/includes/footer.php'; ?>
