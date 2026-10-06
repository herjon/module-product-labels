<?php
declare(strict_types=1);

$magentoRoot = getenv('MAGENTO_ROOT') ?: '';
$magentoBootstrap = $magentoRoot . '/dev/tests/unit/framework/bootstrap.php';
if ($magentoRoot === '' || !is_file($magentoBootstrap)) {
    throw new RuntimeException('Set MAGENTO_ROOT to a Magento Open Source or Mage-OS installation.');
}
require $magentoBootstrap;

// Lets the suite run from a clone of this repository outside app/code or vendor.
$moduleRoot = dirname(__DIR__, 2);
spl_autoload_register(static function (string $class) use ($moduleRoot): void {
    $prefix = 'Majistar\\ProductLabels\\';
    if (str_starts_with($class, $prefix)) {
        $file = $moduleRoot . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
}, true, true);
