<?php // backend code here ?>
<?php
// php/profile.php
header('Content-Type: application/json; charset=utf-8');

/*
  Handles:
    - action=get    -> returns profile for token
    - action=update -> updates profile for token (upsert in Mongo)
    - action=logout -> remove token from Redis
*/

try {
    require __DIR__ . '/vendor/autoload.php';
} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'error'=>'Server misconfiguration: vendor autoload missing. Run composer install.']);
    exit;
}

// ---- config ----
$MYSQL_HOST = '127.0.0.1';
$MYSQL_PORT = 3306;
$MYSQL_DB   = 'guvi_intern';
$MYSQL_USER = 'db_user';
$MYSQL_PASS = 'db_pass';

$MONGO_URI = 'mongodb://127.0.0.1:27017';
$MONGO_DB  = 'guvi_intern';

$REDIS_HOST = '127.0.0.1';
$REDIS_PORT = 6379;
$SESSION_TTL = 86400;
// ----------------

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'get';
$token = $input['token'] ?? null;

if (!$token && $action !== 'logout') {
    echo json_encode(['success'=>false,'error'=>'No token provided']);
    exit;
}

// connect redis
try {
    $redis = new Predis\Client([
      'scheme' => 'tcp',
      'host' => $REDIS_HOST,
      'port' => $REDIS_PORT,
    ]);
} catch (\Exception $e) {
    echo json_encode(['success'=>false,'error'=>'Redis connection error: '.$e->getMessage()]);
    exit;
}

// logout: delete key
if ($action === 'logout') {
    if ($token) $redis->del("session:$token");
    echo json_encode(['success'=>true]);
    exit;
}

// get user id from token
$userId = $redis->get("session:$token");
if (!$userId) {
    echo json_encode(['success'=>false,'error'=>'Invalid or expired session']);
    exit;
}
$userId = (int)$userId;

// connect mongo
$mongoClient = new MongoDB\Client($MONGO_URI);
$profiles = $mongoClient->selectDatabase($MONGO_DB)->selectCollection('profiles');

if ($action === 'get') {
    $doc = $profiles->findOne(['user_id' => $userId]);
    if (!$doc) {
        echo json_encode(['success'=>false,'error'=>'Profile not found']);
        exit;
    }
    // convert BSON to JSON-friendly array
    $arr = json_decode(json_encode($doc), true);
    echo json_encode(['success'=>true,'profile'=>$arr]);
    exit;
}

if ($action === 'update') {
    $update = [];
    if (array_key_exists('name',$input)) $update['name'] = $input['name'];
    if (array_key_exists('age',$input)) $update['age'] = $input['age'] !== '' ? (int)$input['age'] : null;
    if (array_key_exists('dob',$input)) $update['dob'] = $input['dob'] ?: null;
    if (array_key_exists('contact',$input)) $update['contact'] = $input['contact'] ?: null;
    $update['updated_at'] = new MongoDB\BSON\UTCDateTime();
    $profiles->updateOne(['user_id' => $userId], ['$set' => $update], ['upsert' => true]);
    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['success'=>false,'error'=>'Unknown action']);
exit;
