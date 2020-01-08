<?php

class ApplicationConfig {

  const APPFILE      = "config/application.php";
  const ROUTESFILE   = "config/routes.php";
  const LOCALESPATH  = "config/locales/";

  const DATABASEFILE = "config/database.ini";
  const CACHERFILE   = "config/cacher.ini";
  const MAILERFILE   = "config/mailer.ini";

  // genel yapılandırma ayarlarını set et
  public static function application() {

    if (!file_exists(self::APPFILE))
      throw new Exception("Uygulama yapılandırma ayar dosyası mevcut değil → " . self::APPFILE);

    // configuration application load in this file
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

    $localefile = self::LOCALESPATH . $locale . ".php";
    if (!file_exists($localefile))
      throw new Exception("Yerel ayar dosyası mevcut değil → " . $localefile);

    $words = include $localefile;
    return $words;
  }

}
?>
