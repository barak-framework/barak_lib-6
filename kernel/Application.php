<?php

class Application {

  private static $_configutaion = NULL;

  // application configs sets
  public static $timezone = "Europe/Istanbul";
  public static $locale = "tr";
  public static $debug = true;
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

}
?>
