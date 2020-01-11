<?php

class BarakApplication {

  const KERNELPATH = "lib/kernel/";
  const MODULESPATH = "lib/modules/";

  public static function run() {

    // Kernel class load
    self::_import_dir(self::KERNELPATH);

    // Fatal error handling
    register_shutdown_function('ApplicationDebug::shutdown');

    // Exception handling
    set_exception_handler('ApplicationDebug::exception');

    // Error handling
    set_error_handler('ApplicationDebug::error');

    // Config - start
    self::_alias_extract_configs_of_application();
    ApplicationConfig::application();

    // Config init - options
    self::_init_option_kernel();

    // Config init - modules
    self::_init_option_modules();

    // Config - end

    // Alias : get global functions
    ApplicationAlias::extract();

    // Route : load routes in configuration file
    ApplicationConfig::routes();

    // Dispatcher : request dispatch to controller
    ApplicationDispatcher::run();

    // Config close - modules
    self::_close_option_modules();

    // Config close - options
    self::_close_option_kernel();
  }

  private static function _init_option_kernel() {

    // Session options
    if (!strlen(session_id())) {

      // COOKIE: httponly ile JS'in ilgili cookie'yi okuması engelleme ayarı, JS'yi engelle
      ini_set('session.cookie_httponly', 1);

      // for $_SESSION hash kick!
      session_start();

    }

    if (!is_null(Application::$timezone)) date_default_timezone_set(Application::$timezone);
    if (!is_null(Application::$logger)) ApplicationLogger::init(Application::$logger);
    if (!is_null(Application::$debug)) ApplicationDebug::init(Application::$debug);
    if (!is_null(Application::$locale)) ApplicationI18n::init(Application::$locale);
    ApplicationFlash::init();
  }

  private static function _close_option_kernel() {
    ApplicationFlash::close();
  }

  private static function _init_option_modules() { // ok
    if (Application::$model) {
      self::_import_dirs([self::MODULESPATH . 'model/', 'app/models/']);
      ApplicationDatabase::init();
    }

    if (Application::$mailer) {
      self::_import_dirs([self::MODULESPATH . 'mailer/', 'app/mailers/']);
      ApplicationMailer::init();
    }

    if (Application::$cacher) {
      self::_import_dir(self::MODULESPATH . 'cacher/');
      ApplicationCacher::init();
    }

    if (Application::$http) {
      self::_import_dir(self::MODULESPATH . 'http/');
    }
  }

  private static function _import_dirs($directories) { // ok
    foreach ($directories as $directory)
      self::_import_dir($directory);
  }

  private static function _import_dir($directory) { // ok
    foreach (glob($directory . "*.php") as $class)
      require_once $class;
  }

  private static function _close_option_modules() { // ok
    // Cacher : close
    if (Application::$cacher) ApplicationCacher::close();

    // Database : close
    if (Application::$model) ApplicationDatabase::close();
  }

  private static function _alias_extract_configs_of_application() { // ok

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
