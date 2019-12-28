<?php

class Application {

  private static $_config = NULL;

  public static $timezone = "Europe/Istanbul";
  public static $locale = "tr";
  public static $debug = false;

  public static $cacher = false;
  public static $mailer = false;
  public static $model = false;

  public static function extract() {

    function set($key, $value) {
      Application::set($key, $value);
    }

    function modules($keys) {
      foreach ($keys as $index => $key) {
        Application::set($key, true);
      }
    }
  }

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

    $time = time();

    // kernel class - start
    $directories = ['lib/kernel/'];
    foreach ($directories as $directory) {
      foreach (glob($directory . "*.php") as $class) {
        echo $class . "<br/>";
        require_once $class;
      }
    }
    // kernel class - end

    ApplicationLogger::info("Completed lib in " . sprintf ("(%.2f ms)", (microtime(true) - $time) * 1000));

    // Fatal error handling
    register_shutdown_function('ApplicationDebug::shutdown');

    // Exception handling
    set_exception_handler('ApplicationDebug::exception');

    // Error handling
    set_error_handler('ApplicationDebug::error');

    // config - start
    self::extract();

    // Configuration : sets sessions and protection
    ApplicationConfig::application();

    // config options - start
    date_default_timezone_set(self::$timezone);
    ApplicationDebug::init(self::$debug);
    ApplicationI18n::init(self::$locale);
    // config options - end

    // config modules - start
    if (self::$model) {
      $directories = ['lib/modules/model/', 'app/models/'];
      foreach ($directories as $directory) {
        foreach (glob($directory . "*.php") as $class) {
          require_once $class;
        }
      }
      ApplicationDatabase::init();
    }

    if (self::$mailer) {
      $directories = ['lib/modules/mailer/', 'app/mailers/'];
      foreach ($directories as $directory) {
        foreach (glob($directory . "*.php") as $class) {
          require_once $class;
        }
      }
      ApplicationMailer::init();
    }

    if (self::$cacher) {
      $directories = ['lib/modules/cacher/'];
      foreach ($directories as $directory) {
        foreach (glob($directory . "*.php") as $class) {
          require_once $class;
        }
      }
      ApplicationCacher::init();
    }
    // config modules - end
    // config - end

    // Alias : get global functions
    ApplicationAlias::extract();

    // Route : load routes in configuration file
    ApplicationConfig::route();

    // Dispatcher : request dispatch to controller
    ApplicationDispatcher::dispatch();

    // Cacher : close
    if (self::$cacher) ApplicationCacher::close();

    // Database : close
    if (self::$model) ApplicationDatabase::close();

  }
}
?>