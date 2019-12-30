<?php

class ApplicationConfig {

  const APPFILE      = "config/application.php";
  const DATABASEFILE = "config/database.ini";
  const CACHERFILE   = "config/cacher.ini";
  const LOGGERFILE   = "config/logger.ini";
  const MAILERFILE   = "config/mailer.ini";
  const ROUTESFILE   = "config/routes.php";
  const LOCALESDIR   = "config/locales/";

  // genel yapılandırma ayarlarını set et
  public static function application() {

    if (!file_exists(self::APPFILE))
      throw new Exception("Uygulama yapılandırma ayar dosyası mevcut değil → " . self::APPFILE);

    // DEFAULT SETTINGS - start

    // Session options
    if (!strlen(session_id())) {

      // COOKIE: httponly ile JS'in ilgili cookie'yi okuması engelleme ayarı, JS'yi engelle
      ini_set('session.cookie_httponly', 1);

      // for $_SESSION hash kick!
      session_start();

    }

    include self::APPFILE;
  }

  // veritabanı ayar dosyasını oku
  public static function database() {

    if (!file_exists(self::DATABASEFILE))
      throw new Exception("Veritabanı ayar dosyası mevcut değil → " . self::DATABASEFILE);

    return parse_ini_file(self::DATABASEFILE);
  }

  // cache ayar dosyasını oku
  public static function cacher() {

    if (!file_exists(self::CACHERFILE))
      throw new Exception("Cacher ayar dosyası mevcut değil → " . self::CACHERFILE);

    return parse_ini_file(self::CACHERFILE);
  }

  // log ayar dosyasını oku
  public static function logger() {

    if (!file_exists(self::LOGGERFILE))
      throw new Exception("Logger ayar dosyası mevcut değil → " . self::LOGGERFILE);

    return parse_ini_file(self::LOGGERFILE);
  }

  // mail ayar dosyasını oku
  public static function mailer() {

    if (!file_exists(self::MAILERFILE))
      throw new Exception("Mailer ayar dosyası mevcut değil → " . self::MAILERFILE);

    return parse_ini_file(self::MAILERFILE);
  }

  // router dosyasını oku
  public static function routes() {

    if (!file_exists(self::ROUTESFILE))
      throw new Exception("Yönlendirme ayar dosyası mevcut değil → " . self::ROUTESFILE);

    // configuration routes load in this file
    include self::ROUTESFILE;
  }

  // yerel ayar dosyasını oku
  public static function i18n($locale) {

    $localefile = self::LOCALESDIR . $locale . ".php";
    if (!file_exists($localefile))
      throw new Exception("Yerel ayar dosyası mevcut değil → " . $localefile);

    $words = include $localefile;
    return $words;
  }

}
?>
