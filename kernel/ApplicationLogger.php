<?php

class ApplicationLogger {

  const LOGPATH = "tmp/log/";

  const LEVELNAMES = ["info" => 1, "warning" => 2, "error" => 3, "fatal" => 4, "debug" => 5];
  const DRIVERNAMES = ["daily" => 1, "weekly" => 7, "montly" => 30, "yearly" => 365];

  private static $_configuration = NULL;

  // configuration variables
  private static $_level = 1;            // 1 = info
  private static $_driver = 30;          // 30 day = montly
  private static $_file = "production";
  private static $_size = 5242880;       // 5 MB = 5 * 1024 * 1024
  private static $_rotate = 5;           // 5 backup file

  // find and fill path, created_at
  private static $_file_path = false;
  private static $_file_created_at = false;

  public static function init($options) {
    // yapılandırma dosyasını bu fonkiyon ne kadar çağrılırsa çağrılsın sadece bir defa oku!
    if (self::$_configuration == NULL) {

      foreach ($options as $key => $value) {
        switch ($key) {
          case "file": self::$_file = $value; break;
          case "level":
          if (!array_key_exists($value, self::LEVELNAMES))
            throw new Exception("Logger kullanımı için bilinmeyen level → " . $value);
          self::$_level = self::LEVELNAMES[$value];
          break;
          case "driver":
          if (!array_key_exists($value, self::DRIVERNAMES))
            throw new Exception("Logger kullanımı için bilinmeyen sürücü → " . $value);
          self::$_driver = self::DRIVERNAMES[$value];
          break;
          case "rotate": self::$_rotate = intval($value); break;
          case "size": self::$_size = intval($value); break;
          default:
          throw new Exception("Logger kullanımı için bilinmeyen parametre → " . $key);
        }
      }

      list(self::$_file_path, self::$_file_created_at) = self::_create();

      self::$_configuration = TRUE;
    }
  }

  public static function __callStatic($level, $messages) {

    if (!array_key_exists($level, self::LEVELNAMES))
      throw new Exception("Logger kullanımı için bilinmeyen method → " . $level);

    // level yazmaya uygun mu bak
    if (self::$_level <= self::LEVELNAMES[$level]) {

      if (self::$_driver < self::_expire()) {

        // sürücü süresi dolmuşsa log dosyasını döndür
        self::_rotate();

      } else if (self::$_size < filesize(self::$_file_path)) {

        // boyut aşılmışsa da log dosyasını döndür
        self::_rotate();

      }

      if (!($fh = fopen(self::$_file_path, 'a')))
        throw new Exception("Log dosyası açılamadı → " . self::$_file_path);

      $message = strval($messages[0]);

      fwrite($fh, $message . "\n");
      fclose($fh);
    }
  }

  private static function _newname() {
    $_file_created_at = date("Y-m-d");
    $_file_path = $_SERVER["DOCUMENT_ROOT"] . "/" . self::LOGPATH . self::$_file . "_". $_file_created_at .".log";
    return [$_file_path, $_file_created_at];
  }

  private static function _create() {

    if (!(list($_file_path, $_file_created_at) = self::_exists(self::$_file))) {

      list($_file_path, $_file_created_at) = self::_newname();

      if (!($fh = fopen($_file_path, 'w')))
        throw new Exception("Log dosyası oluşturulmak için açılamadı → " . $_file_path);

      fwrite($fh, "");
      fclose($fh);
    }

    return [$_file_path, $_file_created_at];
  }

  // self::$_file = FILE
  // $_files =
  // FILE_YYYY-MM-DD.log
  // FILE@1_YYYY-MM-DD.log
  // FILE@2_YYYY-MM-DD.log

  private static function _exists($file) {

    $_files = scandir(self::LOGPATH);

    foreach ($_files as $_file) {

      if (preg_match("/^(.*?)_([0-9]{4}-[0-9]{2}-[0-9]{2}).log$/si", $_file, $match)) {
        if ($match[1] == $file) {
          return [self::LOGPATH . $match[0], $match[2]];
        }
      }
    }

    return false;
  }

  // self::$_file = FILE
  // $_files =
  // FILE@1_YYYY-MM-DD.log
  // FILE@2_YYYY-MM-DD.log

  private static function _backups() {

    $_files = scandir(self::LOGPATH);

    $_file_path_backups = [];
    foreach ($_files as $_file) {

      if (preg_match("/^" . self::$_file . "@" . "(.*?)". "_([0-9]{4}-[0-9]{2}-[0-9]{2}).log$/si", $_file, $match)) {
        $_file_path_backups[$match[1]] = self::LOGPATH . $match[0];
      }
    }

    return $_file_path_backups;
  }

  private static function _expire() {

    // now date and file _created_at time diff
    $_diff_sec = strtotime(date("Y-m-d")) - strtotime(self::$_file_created_at);

    // 1 day = 24 hours
    // 1 day = 24 * 60 * 60 = 86400 seconds
    $_diff_day = abs(round($_diff_sec / 86400));

    return $_diff_day;
  }

  private static function _rotate() {

    // en son yedek varsa sil
    $_file_rotate_end = self::$_file . "@" . self::$_rotate;
    if ((list($_file_rotate_end_path,$_c) = self::_exists($_file_rotate_end))) {
      unlink($_file_rotate_end_path);
    }

    // yedekleri al
    $_file_path_backups = self::_backups();

    // taşıma yapacağından keye göre ters sırala (2->production@2_2020-01-01.log, 1->production@1_2020-01-01.log gibi)
    // son yedekten(keyden) başlamak üzere taşımaya başla ki bir birinin üzerine yazma olmasın
    krsort($_file_path_backups);
    foreach ($_file_path_backups as $_file_index => $_file_path_backup) {

      $_file_path_backup_before = $_file_path_backup;
      if (file_exists($_file_path_backup_before)) {
        $_file_path_backup_after = str_replace("@{$_file_index}_", "@" . ($_file_index + 1) . "_", $_file_path_backup_before);
        rename($_file_path_backup_before, $_file_path_backup_after);
      }
    }

    // şu an yazılan dosyayı 1 nolu yedek dosya olarak kaydet
    rename(self::$_file_path, self::LOGPATH . self::$_file . "@1_" . self::$_file_created_at . ".log");

    // yeni bir log dosyası oluştur ve bilgilerini ata
    list(self::$_file_path, self::$_file_created_at) = self::_newname();
  }
}
?>
