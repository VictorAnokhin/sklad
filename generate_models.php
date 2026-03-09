<?php

$host = 'sklad_db'; 
$user = 'sklad';
$pass = 'sklad';
$db   = 'sklad';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$tablesResult = $mysqli->query("SHOW TABLES");
$tables = [];
while ($row = $tablesResult->fetch_row()) {
    $tables[] = $row[0];
}

$modelsDir = __DIR__ . '/app/Models';

$tableToModel = [
    'user' => 'LegacyUser'
];

function camelCase($str) {
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
}

foreach ($tables as $table) {
    if (in_array($table, ['migrations', 'failed_jobs', 'password_reset_tokens', 'personal_access_tokens', 'users'])) {
        continue;
    }

    $modelName = $tableToModel[$table] ?? camelCase($table);
    $columnsResult = $mysqli->query("SHOW COLUMNS FROM `$table`");
    $columns = [];
    $hasId = false;
    while ($col = $columnsResult->fetch_assoc()) {
        $columns[] = $col['Field'];
        if ($col['Field'] === 'id') {
            $hasId = true;
        }
    }

    $relations = "";
    
    if (in_array('firma', $columns)) {
        $relations .= "    public function firmaObj()\n    {\n        return \$this->belongsTo(Firma::class, 'firma');\n    }\n\n";
    } elseif (in_array('idfirma', $columns)) {
        $relations .= "    public function firmaObj()\n    {\n        return \$this->belongsTo(Firma::class, 'idfirma');\n    }\n\n";
    }
    
    if (in_array('sklad', $columns) && $table !== 'sklad') {
        $relations .= "    public function skladObj()\n    {\n        return \$this->belongsTo(Sklad::class, 'sklad');\n    }\n\n";
    } elseif (in_array('idsklad', $columns)) {
        $relations .= "    public function skladObj()\n    {\n        return \$this->belongsTo(Sklad::class, 'idsklad');\n    }\n\n";
    }
    
    if (in_array('userid', $columns) && $table !== 'user') {
        $relations .= "    public function legacyUser()\n    {\n        return \$this->belongsTo(LegacyUser::class, 'userid');\n    }\n\n";
    } elseif (in_array('iduser', $columns)) {
        $relations .= "    public function legacyUser()\n    {\n        return \$this->belongsTo(LegacyUser::class, 'iduser');\n    }\n\n";
    }
    
    if (in_array('docid', $columns) && in_array($table, ['z_body', 'zd_document', 'z_price'])) {
        $relations .= "    public function doc()\n    {\n        return \$this->belongsTo(ZDocument::class, 'docid');\n    }\n\n";
    }

    $primaryKeyProp = $hasId ? "" : "    protected \$primaryKey = null;\n    public \$incrementing = false;\n";

    $code = "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\n\nclass {$modelName} extends Model\n{\n    protected \$table = '{$table}';\n    public \$timestamps = false;\n    protected \$guarded = [];\n\n{$primaryKeyProp}\n{$relations}}\n";

    $filePath = $modelsDir . '/' . $modelName . '.php';
    file_put_contents($filePath, $code);
    echo "Model $modelName updated.\n";
}

echo "Done.\n";
