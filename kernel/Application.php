<?php

class Application {

  private static $_configutaion = NULL;

  // default application kernel options
  public static $timezone = null;
  public static $locale = null;
  public static $debug = null;
  public static $logger = null;
  
  // default application modules status
  public static $cacher = null;
  public static $mailer = null;
  public static $model = null;
  public static $http = null;

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
