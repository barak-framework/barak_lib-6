<?php

class Application {

  private static $_config = NULL;

  const KERNELDIR = "lib/kernel/";

  public static $timezone = "Europe/Istanbul";
  public static $locale = "tr";
  public static $debug = false;

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
    // do not make dublicate config function on config/application.php
    if (!isset(self::$_config)) {

      // config processing
      $_functions();
      // config processed

      // bir daha ::config fonksiyonu çağrılmaması için
      self::$_config = TRUE;
    }
  }

  public static function run() {

    // Kernel class load
    self::_import_dir(self::KERNELDIR);

    // Fatal error handling
    register_shutdown_function('ApplicationDebug::shutdown');

    // Exception handling
    set_exception_handler('ApplicationDebug::exception');

    // Error handling
    set_error_handler('ApplicationDebug::error');

    // Logger init
    ApplicationLogger::init();

    // Config - start
    self::_alias_for_application();
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
    date_default_timezone_set(self::$timezone);
    ApplicationDebug::init(self::$debug);
    ApplicationI18n::init(self::$locale);
  }

  private static function _init_modules() {
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

  private static function _import_dirs($directories) {
    foreach ($directories as $directory) {
    	self::_import_dir($directory);
    }
  }

  private static function _import_dir($directory) {
  	// echo "###<br/>";
  	// echo $directory . " Yükleniyor...<br/>";
  	// echo "###<br/>";
    foreach (glob($directory . "*.php") as $class) {
    	// echo $class . "<br/>";
      require_once $class;
    }
  }

  private static function _close_modules() {
    // Cacher : close
    if (self::$cacher) ApplicationCacher::close();

    // Database : close
    if (self::$model) ApplicationDatabase::close();
  }

  private static function _alias_for_application() {

    function set($key, $value) {
      Application::set($key, $value);
    }

    function modules($keys) {
      foreach ($keys as $index => $key) {
        Application::set($key, true);
      }
    }
  }

}
?>