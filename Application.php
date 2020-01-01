<?php

class Application {

  const KERNELDIR = "lib/kernel/";

  private static $_configutaion = NULL;

  // application config sets
  public static $timezone = "Europe/Istanbul";
  public static $locale = "tr";
  public static $debug = false;
  public static $logger = false;

  // application modules status
  public static $cacher = false;
  public static $mailer = false;
  public static $model = false;
  public static $http = false;

  public static function set($key, $value) {
    if (!isset(self::${$key}))
      throw new Exception("Application yapılandırma dosyasında bilinmeyen parametre → " . $key);

    self::${$key} = $value;
  }

  public static function config(callable $_functions) {
    // yapılandırma dosyasını bu fonkiyon ne kadar çağrılırsa çağrılsın sadece bir defa oku!
    if (!isset(self::$_configutaion)) {

      // config processing
      $_functions();
      // config processed

      // bir daha ::config fonksiyonu çağrılmaması için
      self::$_configutaion = TRUE;
    }
  }

  public static function run() {

    // Kernel class load
    self::_import_dir(self::KERNELDIR);

    echo "dizinler yüklendi";
    echo "<br/>";

    // Fatal error handling
    register_shutdown_function('ApplicationDebug::shutdown');

    // Exception handling
    set_exception_handler('ApplicationDebug::exception');

    // Error handling
    set_error_handler('ApplicationDebug::error');

    echo "shutdown - exception - error ayarlandı";
    echo "<br/>";

    // Config - start
    self::_alias_extract_config_of_application();
    ApplicationConfig::application();

    echo "uygulama ayarları yüklendi";
    echo "<br/>";

    // Config init - options
    self::_init_options();

    // Config init - modules
    self::_init_modules();

    // Config - end

    // Alias : get global functions
    ApplicationAlias::extract();

    // Route : load routes in configuration file
    ApplicationConfig::routes();

    // Dispatcher : request dispatch to controller
    ApplicationDispatcher::dispatch();

    // Config close - modules
    self::_close_modules();
  }

  private static function _init_options() {
  	ApplicationLogger::init();
    date_default_timezone_set(self::$timezone);
    ApplicationDebug::init(self::$debug);
    ApplicationI18n::init(self::$locale);
  }

  private static function _init_modules() { // ok
    if (self::$model) {
      $directories = ['lib/modules/model/', 'app/models/'];
      self::_import_dirs($directories);
      ApplicationDatabase::init();
    }

    if (self::$mailer) {
      $directories = ['lib/modules/mailer/', 'app/mailers/'];
      self::_import_dirs($directories);
      ApplicationMailer::init();
    }

    if (self::$cacher) {
      $directory = 'lib/modules/cacher/';
      self::_import_dir($directory);
      ApplicationCacher::init();
    }

    if (self::$http) {
      $directory = 'lib/modules/http/';
      self::_import_dir($directory);
    }
  }

  private static function _import_dirs($directories) { // ok
    foreach ($directories as $directory) {
    	self::_import_dir($directory);
    }
  }

  private static function _import_dir($directory) { // ok
  	// echo "###<br/>";
  	// echo $directory . " Yükleniyor...<br/>";
  	// echo "###<br/>";
    foreach (glob($directory . "*.php") as $class) {
    	// echo $class . "<br/>";
      require_once $class;
    }
  }

  private static function _close_modules() { // ok
    // Cacher : close
    if (self::$cacher) ApplicationCacher::close();

    // Database : close
    if (self::$model) ApplicationDatabase::close();
  }

  private static function _alias_extract_config_of_application() { // ok

    // APPLICATION

    function set($key, $value) {
      Application::set($key, $value);
    }

    function modules($keys) {
      foreach ($keys as $index => $key) {
        Application::set($key, true);
      }
    }

    // ROUTES

    function scope($path, $routes) {
      return ApplicationRoutes::scope($path, $routes);
    }

    function resource($table, $path = "") {
      return ApplicationRoutes::resource($table, $path);
    }

    function resources($table, $path = "") {
      return ApplicationRoutes::resources($table, $path);
    }

    function root($target = FALSE, $path = "") {
      return ApplicationRoutes::root($target, $path);
    }

    function post($rule, $target = FALSE, $path = "") {
      return ApplicationRoutes::post($rule, $target, $path);
    }

    function get($rule, $target = FALSE, $path = "") {
      return ApplicationRoutes::get($rule, $target, $path);
    }
  }

}
?>