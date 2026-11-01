<?php
require __DIR__.'/includes/bootstrap.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if(!$id){http_response_code(404);exit('Jeu introuvable.');}
$s=$pdo->prepare('SELECT g.*,u.username creator FROM games g LEFT JOIN users u ON u.id=g.created_by WHERE g.id=:id');
$s->execute(['id'=>$id]);
$game=$s->fetch();
if(!$game){http_response_code(404);exit('Jeu introuvable.');}
$t=$pdo->prepare('SELECT t.name FROM tags t JOIN game_tags gt ON gt.tag_id=t.id WHERE gt.game_id=:id ORDER BY t.name');
$t->execute(['id'=>$id]);
$tags=$t->fetchAll(PDO::FETCH_COLUMN);
$tagRows=$pdo->prepare('SELECT t.name,t.slug FROM tags t JOIN game_tags gt ON gt.tag_id=t.id WHERE gt.game_id=:id ORDER BY t.name');$tagRows->execute(['id'=>$id]);$tagRows=$tagRows->fetchAll();
$recommendStmt=$pdo->prepare('SELECT g.id,g.title,g.platform,g.thumbnail_path,COUNT(gt.tag_id) score FROM games g LEFT JOIN game_tags gt ON gt.game_id=g.id AND gt.tag_id IN (SELECT tag_id FROM game_tags WHERE game_id=:current) WHERE g.id<>:exclude GROUP BY g.id,g.title,g.platform,g.thumbnail_path ORDER BY score DESC, (g.platform=:platform) DESC, g.title ASC LIMIT 5');
$recommendStmt->execute(['current'=>$id,'exclude'=>$id,'platform'=>$game['platform']]);$recommendations=$recommendStmt->fetchAll();
$pageTitle=$game['title'];
$manualSrc = $game['manual_path'] ? url($game['manual_path']) : ($game['manual_url'] ?? '');
$romSrc = url($game['rom_path']);
$platformCores = [
  'NES' => 'nes',
  'Game Boy' => 'gb',
  'Game Boy Color' => 'gbc',
  'Super Nintendo' => 'snes',
  'Nintendo 64' => 'n64',
  'PlayStation 1' => 'psx'
];
$core = $platformCores[$game['platform']] ?? '';
require __DIR__.'/includes/header.php';
?>
<section class="game-page">
  <p class="game-back"><a href="<?=e(url('index.php'))?>">← Retour à la bibliothèque</a></p>
  <header class="game-page-heading">
    <div><p class="eyebrow">NIN-TAGE // FICHE DE JEU</p><h1><?=e($game['title'])?></h1><span class="game-platform-badge"><?=e($game['platform'])?></span></div>
    <?php if($game['thumbnail_path']): ?><img class="game-heading-thumb" src="<?=e(url($game['thumbnail_path']))?>" alt="Miniature de <?=e($game['title'])?>"><?php endif; ?>
  </header>

  <nav class="game-tabs" role="tablist" aria-label="Sections du jeu">
    <button class="game-tab is-active" id="tab-info" type="button" role="tab" aria-selected="true" aria-controls="panel-info" data-tab="info">MANUEL & DESCRIPTION</button>
    <button class="game-tab" id="tab-play" type="button" role="tab" aria-selected="false" aria-controls="panel-play" data-tab="play">JOUER</button>
    <button class="game-tab" id="tab-details" type="button" role="tab" aria-selected="false" aria-controls="panel-details" data-tab="details">DÉTAILS & FAVORIS</button>
  </nav>

  <section class="game-tab-panel is-active" id="panel-info" role="tabpanel" aria-labelledby="tab-info" data-panel="info">
    <div class="game-info-layout">
      <aside class="game-cover-card">
        <?php if($game['thumbnail_path']): ?><img src="<?=e(url($game['thumbnail_path']))?>" alt="Miniature de <?=e($game['title'])?>"><?php else: ?><div class="no-cover">PAS DE<br>MINIATURE</div><?php endif; ?>
        <h2><?=e($game['title'])?></h2><p class="muted"><?=e($game['platform'])?></p>
      </aside>
      <div class="game-description-card">
        <h2>Description</h2>
        <div class="markdown-content"><?=markdown_safe((string)$game['description_markdown'])?></div>
      </div>
      <aside class="recommendations-card">
        <h2>À découvrir aussi</h2>
        <?php if($recommendations): ?><div class="recommendation-list"><?php foreach($recommendations as $rec): ?><a class="recommendation-item" href="<?=e(url('game.php?id='.(int)$rec['id']))?>"><?php if($rec['thumbnail_path']): ?><img src="<?=e(url($rec['thumbnail_path']))?>" alt=""><?php endif; ?><span><strong><?=e($rec['title'])?></strong><small><?=e($rec['platform'])?></small></span></a><?php endforeach; ?></div><?php else: ?><p class="muted">Les recommandations apparaîtront quand la bibliothèque contiendra d’autres jeux.</p><?php endif; ?>
      </aside>
    </div>
    <div class="manual-card">
      <div class="manual-heading"><div><p class="eyebrow">DOCUMENTATION</p><h2>Manuel du jeu</h2></div><?php if($manualSrc): ?><a class="btn" href="<?=e($manualSrc)?>" target="_blank" rel="noopener noreferrer">Ouvrir séparément ↗</a><?php endif; ?></div>
      <?php if($manualSrc): ?>
        <iframe class="manual-viewer" src="<?=e($manualSrc)?>#toolbar=1&navpanes=0&view=FitH" title="Manuel PDF de <?=e($game['title'])?>" loading="lazy"></iframe>
        <p class="muted manual-hint">Le PDF est intégré à cette page. Si le navigateur ne l’affiche pas, utilise le bouton « Ouvrir séparément ».</p>
      <?php else: ?><div class="empty-state"><strong>Aucun manuel disponible.</strong><span>L’administrateur peut ajouter un PDF depuis la page d’administration.</span></div><?php endif; ?>
    </div>
  </section>

  <section class="game-tab-panel" id="panel-play" role="tabpanel" aria-labelledby="tab-play" data-panel="play" hidden>
    <div class="emulator-toolbar"><div><p class="eyebrow">ZONE D’ÉMULATION</p><h2>Jouer à <?=e($game['title'])?></h2><p class="muted">L’émulateur se charge quand tu ouvres cet onglet. Une connexion Internet est nécessaire pour charger EmulatorJS.</p></div><span id="gamepadStatus" class="gamepad-status" role="status">MANETTE : RECHERCHE…</span></div>
    <div class="emulator-frame" id="emulatorFrame" data-rom="<?=e($romSrc)?>" data-platform="<?=e($game['platform'])?>" data-core="<?=e($core)?>" data-title="<?=e($game['title'])?>">
      <div id="emulatorHost"><div class="emulator-placeholder"><strong>Émulateur prêt à démarrer</strong><span>Ouvre l’onglet Jouer pour charger le moteur et la ROM.</span></div></div>
    </div>
    <div class="emulator-help"><strong>Manette :</strong> branche ou connecte ta manette puis appuie sur un bouton. Les manettes compatibles avec l’API Gamepad du navigateur sont prises en charge par EmulatorJS. <strong>Sauvegardes :</strong> utilise le menu intégré de l’émulateur pour sauvegarder ou charger un état.</div>
    <div class="emulator-actions"><button class="btn" type="button" id="fullscreenEmulator">Plein écran</button><span id="emulatorStatus" class="muted" role="status">En attente du lancement.</span></div>
  </section>

  <section class="game-tab-panel" id="panel-details" role="tabpanel" aria-labelledby="tab-details" data-panel="details" hidden>
    <div class="details-grid">
      <div class="details-card"><p class="eyebrow">FICHE TECHNIQUE</p><h2><?=e($game['title'])?></h2><dl class="game-metadata"><dt>Plateforme</dt><dd><?=e($game['platform'])?></dd><dt>Ajouté par</dt><dd><?=e($game['creator'] ?: 'Équipe Nin-tage')?></dd><dt>Fichier ROM</dt><dd><?=e($game['rom_original_name'])?></dd><dt>Émulation</dt><dd><?= $core !== '' ? 'Moteur disponible : '.e($core) : 'Plateforme non prise en charge par la configuration actuelle.' ?></dd></dl>
        <?php if($tagRows): ?><h3>Tags</h3><div class="game-tags"><?php foreach($tagRows as $tag): ?><a class="tag <?=e(tag_class($tag['slug']))?>" href="<?=e(url('index.php?tag='.rawurlencode($tag['slug'])))?>"><?=e($tag['name'])?></a><?php endforeach; ?></div><?php endif; ?>
      </div>
      <div class="details-card"><p class="eyebrow">TA COLLECTION</p><h2>Favoris</h2>
        <?php if(current_user()): ?><p>Garde ce jeu dans ta liste personnelle.</p><form method="post" action="<?=e(url('favorite.php'))?>"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="game_id" value="<?=(int)$game['id']?>"><button class="btn" type="submit">★ Ajouter / retirer des favoris</button></form><?php else: ?><p>Connecte-toi pour garder ce jeu dans tes favoris.</p><a class="btn" href="<?=e(url('login.php'))?>">Connexion</a><?php endif; ?>
        <hr><p class="muted">N’héberge et n’utilise que des ROMs et manuels que tu as le droit de conserver et de distribuer. La nostalgie n’annule pas le droit d’auteur, hélas.</p>
      </div>
    </div>
  </section>
</section>
<script src="<?=e(url('assets/js/game-tabs.js'))?>" defer></script>
<script src="<?=e(url('assets/js/gamepad-status.js'))?>" defer></script>
<script src="<?=e(url('assets/js/emulator.js'))?>" defer></script>
<?php require __DIR__.'/includes/footer.php'; ?>
