<?php // backend code here ?>
<?php
// php/login.php
header('Content-Type: application/json; charset=utf-8');

/*
  Login verifies MySQL credentials, creates a session token stored in Redis,
  and returns the token to client (client must save it to localStorage).
*/

try {
    require __DIR__ . '/vendor/autoload.php';
} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'error'=>'Server misconfiguration: vendor autoload missing. Run composer install.']);
    exit;
}

// ---- Fill config ----
$MYSQL_HOST = '127.0.0.1';
$MYSQL_PORT = 3306;
$MYSQL_DB   = 'guvi_intern';
$MYSQL_USER = 'db_user';
$MYSQL_PASS = 'db_pass';

$REDIS_HOST = '127.0.0.1';
$REDIS_PORT = 6379;
$SESSION_TTL = 86400;
// ---------------------

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['success'=>false,'error'=>'Missing credentials']);
    exit;
}

$mysqli = new mysqli($MYSQL_HOST, $MYSQL_USER, $MYSQL_PASS, $MYSQL_DB, $MYSQL_PORT);
if ($mysqli->connect_errno) {
    echo json_encode(['success'=>false,'error'=>'MySQL connect error']);
    exit;
}
$mysqli->set_charset('utf8mb4');

$stmt = $mysqli->prepare("SELECT id, password_hash, name FROM users WHERE email = ?");
$stmt->bind_param('s',$email);
$stmt->execute();
$stmt->bind_result($id, $password_hash, $name);
if (!$stmt->fetch()) {
    echo json_encode(['success'=>false,'error'=>'Invalid email or password']);
    $stmt->close();
    exit;
}
$stmt->close();

if (!password_verify($password, $password_hash)) {
    echo json_encode(['success'=>false,'error'=>'Invalid email or password']);
    exit;
}

// create token
$token = bin2hex(random_bytes(24));

// store token -> user_id in redis
try {
    $redis = new Predis\Client([
      'scheme' => 'tcp',
      'host' => $REDIS_HOST,
      'port' => $REDIS_PORT,
    ]);
    $redis->setex("session:$token", $SESSION_TTL, (string)$id);
} catch (\Exception $e) {
    echo json_encode(['success'=>false,'error'=>'Redis error: '.$e->getMessage()]);
    exit;
}

echo json_encode(['success'=>true,'token'=>$token,'user'=>['id'=>(int)$id,'name'=>$name,'email'=>$email]]);
exit;
