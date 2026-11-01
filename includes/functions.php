<?php
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path=''): string {
    global $config;

    // Si base_url est renseigné, il reste prioritaire. Sinon, détecte automatiquement
    // le dossier de l'application à partir du script demandé. Cela fonctionne aussi
    // quand les pages d'administration se trouvent dans /admin/.
    $configuredBase = trim((string)($config['base_url'] ?? ''));
    if ($configuredBase !== '') {
        $base = rtrim($configuredBase, '/');
    } else {
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $directory = str_replace('\\', '/', dirname($scriptName));
        if ($directory === '.' || $directory === '/' || $directory === '\\') {
            $base = '';
        } else {
            $directory = rtrim($directory, '/');
            if (basename($directory) === 'admin') {
                $directory = dirname($directory);
            }
            $base = ($directory === '.' || $directory === '/') ? '' : $directory;
        }
    }

    return $base . '/' . ltrim($path, '/');
}
function redirect(string $path): never { header('Location: '.url($path)); exit; }
function csrf_token(): string { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function verify_csrf(): void {
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) { http_response_code(403); exit('Jeton de sécurité invalide. Rechargez la page puis réessayez.'); }
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function require_login(): array { $u=current_user(); if (!$u) redirect('login.php'); return $u; }
function require_admin(): array {
    global $pdo;
    $u = require_login();
    // Ne jamais se fier uniquement au rôle conservé dans la session : il peut avoir été rétrogradé en base.
    $stmt = $pdo->prepare('SELECT username, email, role FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $u['id']]);
    $fresh = $stmt->fetch();
    if (!$fresh) { $_SESSION = []; session_regenerate_id(true); redirect('login.php'); }
    $_SESSION['user'] = array_merge($u, $fresh);
    $u = $_SESSION['user'];
    if (($u['role'] ?? '') !== 'admin') { http_response_code(403); exit('Accès réservé aux administrateurs.'); }
    return $u;
}
function flash(string $message, string $type='info'): void { $_SESSION['flash']=['message'=>$message,'type'=>$type]; }
function take_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function markdown_inline(string $text): string {
    $text = e($text);
    // Liens HTTP(S) uniquement, avec échappement des URL et des libellés.
    $text = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', function($m) {
        $url = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer">'.$m[1].'</a>';
    }, $text);
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $text);
    $text = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $text);
    $text = preg_replace('/(?<!_)_([^_\n]+)_(?!_)/', '<em>$1</em>', $text);
    $text = preg_replace('/~~(.+?)~~/s', '<del>$1</del>', $text);
    return $text;
}
function markdown_safe(string $text): string {
    $text = str_replace(["\r\n", "\r"], "\n", trim($text));
    if ($text === '') return '<p class="markdown-empty">Aucune description disponible.</p>';
    $lines = explode("\n", $text); $out=[]; $paragraph=[]; $listType=null; $inCode=false; $code=[];
    $flushParagraph = function() use (&$paragraph,&$out) { if ($paragraph) { $out[]='<p>'.implode("<br>\n", array_map('markdown_inline',$paragraph)).'</p>'; $paragraph=[]; } };
    $flushList = function() use (&$listType,&$out) { if ($listType !== null) { $out[]='</'.$listType.'>'; $listType=null; } };
    foreach ($lines as $line) {
        if (preg_match('/^\s*```/', $line)) {
            $flushParagraph(); $flushList();
            if (!$inCode) { $inCode=true; $code=[]; } else { $out[]='<pre><code>'.e(implode("\n",$code)).'</code></pre>'; $inCode=false; }
            continue;
        }
        if ($inCode) { $code[]=$line; continue; }
        if (trim($line)==='') { $flushParagraph(); $flushList(); continue; }
        if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) { $flushParagraph();$flushList();$n=strlen($m[1]);$out[]='<h'.$n.'>'.markdown_inline($m[2]).'</h'.$n.'>';continue; }
        if (preg_match('/^\s*([-*_])(?:\s*\1){2,}\s*$/',$line)) { $flushParagraph();$flushList();$out[]='<hr>';continue; }
        if (preg_match('/^>\s?(.*)$/',$line,$m)) { $flushParagraph();$flushList();$out[]='<blockquote>'.markdown_inline($m[1]).'</blockquote>';continue; }
        if (preg_match('/^\s*[-*+]\s+(.+)$/',$line,$m)) { $flushParagraph(); if($listType!=='ul'){$flushList();$out[]='<ul>';$listType='ul';} $out[]='<li>'.markdown_inline($m[1]).'</li>';continue; }
        if (preg_match('/^\s*\d+[.)]\s+(.+)$/',$line,$m)) { $flushParagraph(); if($listType!=='ol'){$flushList();$out[]='<ol>';$listType='ol';} $out[]='<li>'.markdown_inline($m[1]).'</li>';continue; }
        $flushList(); $paragraph[]=$line;
    }
    if($inCode)$out[]='<pre><code>'.e(implode("\n",$code)).'</code></pre>';
    $flushParagraph();$flushList(); return implode("\n",$out);
}
function tag_class(string $slug): string {
    $special = ['difficile'=>'tag-difficile','classique'=>'tag-classique','mauvais'=>'tag-mauvais','sous-cote'=>'tag-sous-cote','sous-cotee'=>'tag-sous-cote','tres-difficile'=>'tag-tres-difficile','facile'=>'tag-facile'];
    return $special[$slug] ?? 'tag-standard';
}

function is_limited(): bool { return (current_user()['role'] ?? '') === 'limite'; }
function require_interaction_permission(): void {
    global $pdo;
    $u = require_login();
    // Recharge le grade depuis SQL pour qu'une restriction prenne effet sans attendre une nouvelle connexion.
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $u['id']]);
    $role = $stmt->fetchColumn();
    if ($role === false) { $_SESSION = []; session_regenerate_id(true); redirect('login.php'); }
    $_SESSION['user']['role'] = $role;
    if ($role === 'limite') { http_response_code(403); exit('Votre compte est limité : cette action est désactivée.'); }
}
function safe_upload(array $file, string $kind): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Échec du transfert du fichier.');
    $limits = ['thumbnail'=>5*1024*1024, 'manual'=>10*1024*1024, 'rom'=>64*1024*1024];
    if (($file['size'] ?? 0) > $limits[$kind]) throw new RuntimeException('Fichier trop volumineux pour la limite configurée.');
    $allowed = [
      'thumbnail'=>['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'],
      'manual'=>['pdf'=>'application/pdf'],
      'rom'=>['nes'=>'application/octet-stream','gb'=>'application/octet-stream','gbc'=>'application/octet-stream','sfc'=>'application/octet-stream','smc'=>'application/octet-stream','z64'=>'application/octet-stream','n64'=>'application/octet-stream','v64'=>'application/octet-stream','iso'=>'application/octet-stream','bin'=>'application/octet-stream','cue'=>'application/octet-stream']
    ];
    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$kind][$ext])) throw new RuntimeException('Extension de fichier non autorisée.');
    $tmp=(string)$file['tmp_name'];
    if (!is_uploaded_file($tmp)) throw new RuntimeException('Transfert de fichier invalide.');
    if ($kind === 'thumbnail') {
        $info = @getimagesize($tmp);
        if (!$info || !in_array($info['mime'], ['image/jpeg','image/png','image/webp'], true)) throw new RuntimeException('La miniature doit être une vraie image JPG, PNG ou WebP.');
    }
    if ($kind === 'manual' && (new finfo(FILEINFO_MIME_TYPE))->file($tmp) !== 'application/pdf') throw new RuntimeException('Le fichier ne semble pas être un PDF valide.');
    $folder = ['thumbnail'=>'thumbnails','manual'=>'manuals','rom'=>'roms'][$kind];
    $targetDir = dirname(__DIR__).'/uploads/'.$folder;
    if (!is_dir($targetDir) || !is_writable($targetDir)) throw new RuntimeException('Le dossier uploads/'.$folder.' est absent ou non accessible en écriture.');
    $name=bin2hex(random_bytes(16)).'.'.$ext;
    if (!move_uploaded_file($tmp, $targetDir.'/'.$name)) throw new RuntimeException('Impossible de sauvegarder le fichier.');
    return 'uploads/'.$folder.'/'.$name;
}
