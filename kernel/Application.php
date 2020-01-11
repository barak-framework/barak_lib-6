<?php

class Application {

  private static $_configutaion = NULL;

  // application kernel options overwrite
  // Çekirdek uygulama ayarları zaten varsayılan olarak yüklüdüğür.
  // Eğer bu ayarlar üzerine yazılmak isteniyorsa config/application.php içerisinde set fonksiyonunda belirtilmelidir.
  public static $timezone = null;
  public static $locale = null;
  public static $debug = null;
  public static $logger = null;
  
  // application modules default status = true/false
  // Uygulama modül ayarlarında aşağıdaki tüm modüller, yüklenmeyecek şekilde gelecektir.
  // Eğer gelmesi isteniyorsa config/application.php içerisinde modules fonksiyonunda belirtilmelidir.
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
