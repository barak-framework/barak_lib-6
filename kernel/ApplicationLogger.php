<?php
class ApplicationLogger {

  const LOGDIR = "tmp/log/";

  const LEVELNAMES = ["info" => 1, "warning" => 2, "error" => 3, "fatal" => 4, "debug" => 5];
  const DRIVERNAMES = ["daily" => 1, "weekly" => 7, "montly" => 30, "yearly" => 365];

  private static $_configuration = NULL;

  // configuration variables
  private static $_level = 1; // 1 = info
  private static $_driver = 30; // 30 day = montly
  private static $_file = "production";
  private static $_size = 5242880; // 5 MB = 5 * 1024 * 1024
  private static $_rotate = 5;

  // find and fill path, created_at
  private static $_file_path = false;
  private static $_file_created_at = false;

  public static function init($file = "production", $level = "info", $driver = "weekly", $rotate = "4", $size = "5242880") {
    // yapılandırma dosyasını bu fonkiyon ne kadar çağrılırsa çağrılsın sadece bir defa oku!
    if (self::$_configuration == NULL) {

      if (!array_key_exists($level, self::LEVELNAMES))
        throw new Exception("Logger kullanımı için bilinmeyen level → " . $level);

      if (!array_key_exists($driver, self::DRIVERNAMES))
        throw new Exception("Logger kullanımı için bilinmeyen sürücü → " . $driver);

      self::$_file = $file;
      self::$_level = self::LEVELNAMES[$level];
      self::$_driver = self::DRIVERNAMES[$driver];
      self::$_rotate = intval($rotate);
      self::$_size = intval($size);

      list(self::$_file_path, self::$_file_created_at) = self::_create();

      self::$_configuration = TRUE;
    }
  }

  public static function __callStatic($level, $messages) {

    if (!array_key_exists($level, self::LEVELNAMES))
      throw new Exception("Logger kullanımı için bilinmeyen method → " . $level);

    if (self::$_level <= self::LEVELNAMES[$level]) {

      if (self::$_driver < self::_expire()) {

        self::_rotate();

      } else if (self::$_size < filesize(self::$_file_path)) {

        self::_rotate();

      }

      $message = strval($messages[0]);
      // echo "bu dosya üzerine yaziliyor :" . self::$_file_path . "<br/>";
      if (!($fh = fopen(self::$_file_path, 'a')))
        throw new Exception("Log dosyası açılamadı → " . self::$_file_path);

      fwrite($fh, $message . "\n");
      fclose($fh);
    }
  }

  private static function _newname() {
    $_file_created_at = date("Y-m-d");
    $_file_path = $_SERVER["DOCUMENT_ROOT"] . "/" . self::LOGDIR . self::$_file . "_". $_file_created_at .".log";
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
    // echo "dosya var: ". $file . "<br/>";

    return [$_file_path, $_file_created_at];
  }

  private static function _exists($file) {

    $_files = scandir(self::LOGDIR);

    foreach ($_files as $_file) {

      // self::$_file = FILE
      // $_file =
      // FILE_2020-01-01
      // FILE@1_2019-12-31
      // FILE@2_2019-12-31

      if (preg_match("/^(.*?)_([0-9]{4}-[0-9]{2}-[0-9]{2}).log$/si", $_file, $match)) {
        if ($match[1] == $file) {
          // echo "dosyayı buldum : " . self::LOGDIR . $match[0] . "|" . $file . ":" . $match[1] . "<br/>";
          // print_r($match);
          return [self::LOGDIR . $match[0], $match[2]];
        }
      }
    }

    return false;
  }

  private static function _backups() {

    $_files = scandir(self::LOGDIR);

    $_file_path_backups = [];
    foreach ($_files as $_file) {

      // self::$_file = FILE
      // $_file =
      // FILE_2019-12-31
      // FILE@1_2019-12-31
      // FILE@2_2019-12-31

      if (preg_match("/^" . self::$_file . "@" . "(.*?)". "_([0-9]{4}-[0-9]{2}-[0-9]{2}).log$/si", $_file, $match)) {
        // print_r($match);
        $_file_path_backups[$match[1]] = self::LOGDIR . $match[0];
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
    // echo self::$_file_path . " boyutu aştı<br/>";
    // echo "<br/>rotate işlemi başlatılıyor...<br/>";

    $_file_rotate_end = self::$_file . "@" . self::$_rotate;
    // echo "son dosya var mı bakılıyor : " . $_file_rotate_end . "<br/>";

    if ((list($_file_rotate_end_path,$_c) = self::_exists($_file_rotate_end))) {
      // echo "son dosya siliniyor...";
      unlink($_file_rotate_end_path);
    }

    $_file_path_backups = self::_backups();
    // echo "yedek loglar listeleniyor... <br/>";
    // print_r($_file_path_backups);
    // echo "<br/>";

    krsort($_file_path_backups);
    foreach ($_file_path_backups as $_file_index => $_file_path_backup) {
      $_file_path_backup_before = $_file_path_backup;
      // echo $_file_path_backup_before . "<br/>";
      if (file_exists($_file_path_backup_before)) {
        $_file_path_backup_after = str_replace("@{$_file_index}_", "@" . ($_file_index + 1) . "_", $_file_path_backup_before);
        // echo $_file_path_backup_before . " |{$_file_index}| " . $_file_path_backup_after . "<br/>";
        rename($_file_path_backup_before, $_file_path_backup_after);
      }
    }
    // echo "ilk dosya kaydırılıyor : " . self::$_file_path . "<br/>";
    // echo self::$_file_path.">>>>". self::$_file . "@1_" . self::$_file_created_at;

    rename(self::$_file_path, self::LOGDIR . self::$_file . "@1_" . self::$_file_created_at . ".log");
    list(self::$_file_path, self::$_file_created_at) = self::_newname();
  }
}
?>
