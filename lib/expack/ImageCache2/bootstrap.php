<?php
/**
 * rep2expack - ImageCache2 �������X�N���v�g
 */
use Doctrine\Common\Cache,
    Doctrine\Common\EventManager,
    Doctrine\DBAL\Events,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Tools\Setup,
    ImageCache2\EventListener;

// {{{ ic2_entitymanager()

/**
 * EntityManager�̃C���X�^���X���擾����
 *
 * @param array $ini
 *
 * @return Doctrine\ORM\EntityManager
 */
function ic2_entitymanager(array $ini = null)
{
    static $defaultEntityManager = null;

    $useDefaultEntityManager = false;

    // �f�t�H���g��EntityManager���g��
    if (is_null($ini)) {
        if (!is_null($defaultEntityManager)) {
            return $defaultEntityManager;
        }
        $useDefaultEntityManager = true;
        $ini = ic2_loadconfig();
    }

    // Configuration���Z�b�g�A�b�v
    if (defined('P2_CLI_RUN')) {
        $isDevMode = true;
        $proxyDir = null;
        $cache = null;
    } else {
        global $_conf;

        $isDevMode = !empty($ini['General']['devmode']);
        $proxyDir = $_conf['tmp_dir'];

        if (extension_loaded('apc')) {
            $cache = new Cache\ApcCache();
        } else {
            $cache = new Cache\FilesystemCache($_conf['cache_dir'] . '/doctrine');
        }
    }

    $config = Setup::createYAMLMetadataConfiguration(
        array(P2_CONFIG_DIR . '/ic2'), $isDevMode, $proxyDir, $cache);

    // EntityManager�̃C���X�^���X�𐶐�
    $conn = $ini['General']['database'];
    $eventManager = new EventManager();
    $eventManager->addEventListener(Events::postConnect, new EventListener);
    $entityManager = EntityManager::create($conn, $config, $eventManager);

    // �f�t�H���g��EntityManager��ۑ�
    if ($useDefaultEntityManager) {
        $defaultEntityManager = $entityManager;
    }

    return $entityManager;
}

// }}}
// {{{ ic2_loadconfig()

/**
 * ���[�U�ݒ�ǂݍ��݊֐�
 *
 * @param void
 *
 * @return array
 */
function ic2_loadconfig()
{
    static $ini = null;

    if (!is_null($ini)) {
        return $ini;
    }

    $_conf = $GLOBALS['_conf'];

    include P2_CONFIG_DIR . '/conf_ic2.inc.php';

    $ini = array();
    $_ic2conf = preg_grep('/^expack\\.ic2\\.\\w+\\.\\w+$/', array_keys($_conf));

    foreach ($_ic2conf as $key) {
        $p = explode('.', $key);
        $cat = ucfirst($p[2]);
        $name = $p[3];
        if (!isset($ini[$cat])) {
            $ini[$cat] = array();
        }
        $ini[$cat][$name] = $_conf[$key];
    }

    if (!isset($ini['General']['database'])) {
        $ini['General']['database'] = ic2_convertdsn($ini['General']['dsn']);
    }

    return $ini;
}

// }}}
// {{{ ic2_convertdsn()

/**
 * PEAR::DB��DSN��Doctrine DBAL�����ɕϊ�����
 *
 * @param string $dsn
 *
 * @return array
 */
function ic2_convertdsn($dsn)
{
    if (!class_exists('DB', false)) {
        require 'DB.php';
    }

    $parsed = DB::parseDSN($dsn);
    $conn = array();

    $phptype = strtolower($parsed['phptype']);
    switch ($phptype) {
        case 'sqlite':
            p2die('sqlite2 is no longer supported');
            break;
        case 'mysqli':
            $conn['driver'] = 'mysqli';
            break;
        default:
            $conn['driver'] = 'pdo_' . $phptype;
    }

    if ($phptype === 'sqlite') {
        $conn['path'] = $parsed['database'];
    } else {
        $conn['dbname']   = $parsed['database'];
        $conn['user']     = $parsed['username'];
        $conn['password'] = $parsed['password'];

        if ($parsed['protocol'] == 'unix') {
            $conn['unix_socket'] = $parsed['sorcket'];
        } else {
            $conn['host'] = $parsed['hostspec'];
            if ($parsed['port']) {
                $conn['port'] = $parsed['port'];
            }
        }
    }

    return $conn;
}

// }}}
// {{{ ic2_findexec()

/**
 * ���s�t�@�C�������֐�
 *
 * $search_path������s�t�@�C��$command����������
 * ������΃p�X���G�X�P�[�v���ĕԂ��i$escape���U�Ȃ炻�̂܂ܕԂ��j
 * ������Ȃ����false��Ԃ�
 *
 * @param string $command
 * @param string $search_path
 * @param bool $escape
 *
 * @return string
 */
function ic2_findexec($command, $search_path = '', $escape = true)
{
    // Windows���A���̑���OS��
    if (P2_OS_WINDOWS) {
        if (strtolower(strrchr($command, '.')) != '.exe') {
            $command .= '.exe';
        }
        $check = function_exists('is_executable') ? 'is_executable' : 'file_exists';
    } else {
        $check = 'is_executable';
    }

    // $search_path����̂Ƃ��͊��ϐ�PATH���猟������
    if ($search_path == '') {
        $search_dirs = explode(PATH_SEPARATOR, getenv('PATH'));
    } else {
        $search_dirs = explode(PATH_SEPARATOR, $search_path);
    }

    // ����
    foreach ($search_dirs as $path) {
        $path = realpath($path);
        if ($path === false || !is_dir($path)) {
            continue;
        }
        if ($check($path . DIRECTORY_SEPARATOR . $command)) {
            return ($escape ? escapeshellarg($command) : $command);
        }
    }

    // ������Ȃ�����
    return false;
}

// }}}
// {{{ ic2_load_class()

/**
 * �N���X���[�_�[
 *
 * @param string $name
 *
 * @return void
 */
function ic2_load_class($name)
{
    if (strncmp($name, 'ImageCache2_', 12) === 0) {
        include P2EX_LIB_DIR . '/' . str_replace('_', '/', $name) . '.php';
    } elseif (strncmp($name, 'ImageCache2\\', 12) === 0) {
        include P2EX_LIB_DIR . '/' . str_replace('\\', '/', $name) . '.php';
    } elseif (strncmp($name, 'Thumbnailer', 11) === 0) {
        include P2_LIB_DIR . '/' . str_replace('_', '/', $name) . '.php';
    }
}

// }}}

spl_autoload_register('ic2_load_class');

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker:
