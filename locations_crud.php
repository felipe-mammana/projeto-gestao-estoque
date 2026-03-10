<?php
/**
 * locations_crud.php
 * Funções CRUD para gerenciamento de locais de armazenamento
 * Auto-relacionamento com Adjacency List
 */

/**
 * Busca todos os locais pai (sem parent_id)
 * @param mysqli $conexao
 * @return array
 */
function getAllLocations($conexao)
{
    $sql = "SELECT id, nome, parent_id, descricao, ativo, criado_em 
            FROM locations 
            WHERE ativo = 1 
            ORDER BY nome ASC";
    
    $result = mysqli_query($conexao, $sql);
    $locations = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $locations[] = $row;
    }
    
    return $locations;
}

/**
 * Busca locais estruturados hierarquicamente (pai/filhos)
 * @param mysqli $conexao
 * @return array
 */
function getLocationsHierarchy($conexao)
{
    $sql = "
        SELECT 
            l1.id AS pai_id,
            l1.nome AS pai_nome,
            l1.descricao AS pai_descricao,
            l2.id AS filho_id,
            l2.nome AS filho_nome,
            l2.descricao AS filho_descricao
        FROM locations l1
        LEFT JOIN locations l2 ON l2.parent_id = l1.id AND l2.ativo = 1
        WHERE l1.parent_id IS NULL AND l1.ativo = 1
        ORDER BY l1.nome, l2.nome";
    
    $result = mysqli_query($conexao, $sql);
    
    $locations = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $paiId = $row['pai_id'];
        
        if (!isset($locations[$paiId])) {
            $locations[$paiId] = [
                'id' => $paiId,
                'nome' => $row['pai_nome'],
                'descricao' => $row['pai_descricao'],
                'sublocals' => []
            ];
        }
        
        if ($row['filho_id']) {
            $locations[$paiId]['sublocals'][] = [
                'id' => $row['filho_id'],
                'nome' => $row['filho_nome'],
                'descricao' => $row['filho_descricao']
            ];
        }
    }
    
    return $locations;
}

/**
 * Busca apenas os locais pai (sem parent_id)
 * @param mysqli $conexao
 * @return array
 */
function getParentLocations($conexao)
{
    $sql = "SELECT id, nome, descricao 
            FROM locations 
            WHERE parent_id IS NULL AND ativo = 1 
            ORDER BY nome ASC";
    
    $result = mysqli_query($conexao, $sql);
    $locations = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $locations[] = $row;
    }
    
    return $locations;
}

/**
 * Busca os filhos de um local específico
 * @param mysqli $conexao
 * @param int $parent_id
 * @return array
 */
function getChildLocations($conexao, $parent_id)
{
    $parent_id = (int)$parent_id;
    
    $sql = "SELECT id, nome, descricao 
            FROM locations 
            WHERE parent_id = $parent_id AND ativo = 1 
            ORDER BY nome ASC";
    
    $result = mysqli_query($conexao, $sql);
    $locations = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $locations[] = $row;
    }
    
    return $locations;
}

/**
 * Busca um local pelo ID
 * @param mysqli $conexao
 * @param int $id
 * @return array|null
 */
function getLocationById($conexao, $id)
{
    $id = (int)$id;
    
    $sql = "SELECT id, nome, parent_id, descricao, ativo 
            FROM locations 
            WHERE id = $id";
    
    $result = mysqli_query($conexao, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Cria um novo local
 * @param mysqli $conexao
 * @param string $nome
 * @param int|null $parent_id
 * @param string $descricao
 * @return array ['success' => bool, 'id' => int|null, 'error' => string|null]
 */
function createLocation($conexao, $nome, $parent_id = null, $descricao = '')
{
    $nome = mysqli_real_escape_string($conexao, trim($nome));
    $descricao = mysqli_real_escape_string($conexao, trim($descricao));
    $parent_id = !empty($parent_id) ? (int)$parent_id : null;
    
    // Validações básicas
    if (empty($nome)) {
        return ['success' => false, 'error' => 'Nome do local é obrigatório'];
    }
    
    if (strlen($nome) > 120) {
        return ['success' => false, 'error' => 'Nome não pode exceder 120 caracteres'];
    }
    
    // Verificar se parent_id existe e está ativo
    if ($parent_id !== null) {
        $check = mysqli_query($conexao, "SELECT id FROM locations WHERE id = $parent_id AND ativo = 1");
        if (!$check || mysqli_num_rows($check) == 0) {
            return ['success' => false, 'error' => 'Local pai inválido ou inativo'];
        }
    }
    
    $parent_val = $parent_id !== null ? $parent_id : "NULL";
    
    $sql = "INSERT INTO locations (nome, parent_id, descricao, ativo) 
            VALUES ('$nome', $parent_val, '$descricao', 1)";
    
    if (mysqli_query($conexao, $sql)) {
        $id = mysqli_insert_id($conexao);
        return ['success' => true, 'id' => $id, 'error' => null];
    } else {
        return ['success' => false, 'error' => mysqli_error($conexao)];
    }
}

/**
 * Atualiza um local existente
 * @param mysqli $conexao
 * @param int $id
 * @param string $nome
 * @param int|null $parent_id
 * @param string $descricao
 * @return array ['success' => bool, 'error' => string|null]
 */
function updateLocation($conexao, $id, $nome, $parent_id = null, $descricao = '')
{
    $id = (int)$id;
    $nome = mysqli_real_escape_string($conexao, trim($nome));
    $descricao = mysqli_real_escape_string($conexao, trim($descricao));
    $parent_id = !empty($parent_id) ? (int)$parent_id : null;
    
    // Validações
    if (empty($nome)) {
        return ['success' => false, 'error' => 'Nome do local é obrigatório'];
    }
    
    if (strlen($nome) > 120) {
        return ['success' => false, 'error' => 'Nome não pode exceder 120 caracteres'];
    }
    
    // Verificar se não está tentando colocar a si mesmo como pai (evitar loop infinito)
    if ($parent_id === $id) {
        return ['success' => false, 'error' => 'Um local não pode ser pai de si mesmo'];
    }
    
    // Verificar se parent_id existe
    if ($parent_id !== null) {
        $check = mysqli_query($conexao, "SELECT id FROM locations WHERE id = $parent_id AND ativo = 1");
        if (!$check || mysqli_num_rows($check) == 0) {
            return ['success' => false, 'error' => 'Local pai inválido ou inativo'];
        }
    }
    
    // Verificar se local existe
    $check = mysqli_query($conexao, "SELECT id FROM locations WHERE id = $id");
    if (!$check || mysqli_num_rows($check) == 0) {
        return ['success' => false, 'error' => 'Local não encontrado'];
    }
    
    $parent_val = $parent_id !== null ? $parent_id : "NULL";
    
    $sql = "UPDATE locations 
            SET nome = '$nome', parent_id = $parent_val, descricao = '$descricao' 
            WHERE id = $id";
    
    if (mysqli_query($conexao, $sql)) {
        return ['success' => true, 'error' => null];
    } else {
        return ['success' => false, 'error' => mysqli_error($conexao)];
    }
}

/**
 * Desativa um local (soft delete)
 * @param mysqli $conexao
 * @param int $id
 * @return array ['success' => bool, 'error' => string|null]
 */
function deleteLocation($conexao, $id)
{
    $id = (int)$id;
    
    // Verificar se local existe
    $check = mysqli_query($conexao, "SELECT id FROM locations WHERE id = $id");
    if (!$check || mysqli_num_rows($check) == 0) {
        return ['success' => false, 'error' => 'Local não encontrado'];
    }
    
    // Desativar também os filhos
    $sql = "UPDATE locations SET ativo = 0 WHERE id = $id OR parent_id = $id";
    
    if (mysqli_query($conexao, $sql)) {
        return ['success' => true, 'error' => null];
    } else {
        return ['success' => false, 'error' => mysqli_error($conexao)];
    }
}

/**
 * Retorna JSON com filhos de um local (para AJAX - Cascading Select)
 * @param mysqli $conexao
 * @param int $parent_id
 */
function getChildLocationsJSON($conexao, $parent_id)
{
    $parent_id = (int)$parent_id;
    
    $locations = getChildLocations($conexao, $parent_id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $locations
    ]);
    exit;
}
