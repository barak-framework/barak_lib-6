<?php

class BarakApplication {

  const KERNELDIR = "lib/kernel/";
  const MODULESDIR = "lib/modules/";

  public static function run() {

    // Kernel class load
    self::_import_dir(self::KERNELDIR);

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
    ApplicationLogger::init(Application::$logger);
    date_default_timezone_set(Application::$timezone);
    ApplicationDebug::init(Application::$debug);
    ApplicationI18n::init(Application::$locale);
  }

  private static function _init_modules() { // ok
    if (Application::$model) {
      $directories = [self::MODULESDIR . 'model/', 'app/models/'];
      self::_import_dirs($directories);
      ApplicationDatabase::init();
    }

    if (Application::$mailer) {
      $directories = [self::MODULESDIR . 'mailer/', 'app/mailers/'];
      self::_import_dirs($directories);
      ApplicationMailer::init();
    }

    if (Application::$cacher) {
      $directory = self::MODULESDIR . 'cacher/';
      self::_import_dir($directory);
      ApplicationCacher::init();
    }

    if (Application::$http) {
      $directory = self::MODULESDIR . 'http/';
      self::_import_dir($directory);
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

  private static function _close_modules() { // ok
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
