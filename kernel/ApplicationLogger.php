<?php
class ApplicationLogger {

  const LOGDIR = "tmp/log/";

  const LEVELNAMES = ["info" => 0, "warning" => 1, "error" => 2, "fatal" => 3, "debug" => 4];
  const DRIVERNAMES = ["daily" => 1, "weekly" => 7, "montly" => 30, "yearly" => 365];

  private static $_configuration = NULL;

  private static $_file_path = false;

  private static $_level = 0;
  private static $_driver = 30; // 30 day
  private static $_file = "production.log";
  private static $_size = 5242880; // 5 MB = 5 * 1024 * 1024
  private static $_rotate = 5;


  public static function init() {
    // yapılandırma dosyasını bu fonkiyon ne kadar çağrılırsa çağrılsın sadece bir defa oku!
    if (self::$_configuration == NULL) {

      foreach (ApplicationConfig::logger() as $key => $value) {
        switch ($key) {
          case "driver":
          if (!array_key_exists($value, self::DRIVERNAMES)) {
            throw new Exception("Logger kullanımı için bilinmeyen sürücü → " . $value);
          }
          self::$_driver = self::DRIVERNAMES[$value];
          break;
          case "level":
          if (!array_key_exists($value, self::LEVELNAMES)) {
            throw new Exception("Logger kullanımı için bilinmeyen level → " . $value);
          }
          self::$_level = self::LEVELNAMES[$value];
          break;
          case "file": self::$_file = $value; break;
          case "size": self::$_size = intval($value); break;
          case "rotate": self::$_rotate = intval($value); break;
          default:
          throw new Exception("Logger yapılandırma dosyasında bilinmeyen parametre → " . $key);
        }
      }

      self::$_file_path = self::_create();

      self::$_configuration = TRUE;
    }
  }

  public static function __callStatic($level, $messages) {

    if (!array_key_exists($level, self::LEVELNAMES))
      throw new Exception("Logger kullanımı için bilinmeyen method → " . $level);

    if (self::$_level <= self::LEVELNAMES[$level]) {

      $message = strval($messages[0]);

      // if (self::$_driver < self::_day($file)) {

      //   self::_rotate($file);

      // } else

      if (self::$_size < filesize(self::$_file_path)) {

        self::_rotate();

      }
      echo "bu dosya üzerine yaziliyor :" . self::$_file_path . "<br/>";
      if (!($fh = fopen(self::$_file_path, 'a')))
        throw new Exception("Log dosyası açılamadı → " . self::$_file_path);

      fwrite($fh, $message . "\n");
      fclose($fh);
    }
  }

  private static function _newname() {
    return self::LOGDIR . self::$_file . "_". date("Y-m-d") .".log";
  }

  private static function _create() {
    // file = production.log

    if (!($file = self::_exists(self::$_file))) {
      $file = self::_newname();
      echo "dosya bulunamadı oluşturuluyor : " . $file . "<br/>";
      if (!($fh = fopen($file, 'w')))
        throw new Exception("Log dosyası oluşturulmak için açılamadı → " . $file);

      fwrite($fh, "");
      fclose($fh);
    } else
    echo "dosya var: ". $file . "<br/>";

    return $file;
  }

  private static function _exists($file) {

    $_files = scandir(self::LOGDIR);

    foreach ($_files as $_file) {

      // self::$_file = FILE
      // $_file =
      // 2020-01-01.FILE
      // 2019-12-31.FILE.1
      // 2019-12-30.FILE.2

      if (preg_match("/^(.*?)_([0-9]{4}-[0-9]{2}-[0-9]{2}).log$/si", $_file, $match)) {
        if ($match[1] == $file) {
          echo "dosyayı buldum : " . self::LOGDIR . $match[0] . "<br/>";
          print_r($match);
          return self::LOGDIR . $match[0];
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
      // 2020-01-01.FILE.1
      // 2019-12-31.FILE.2
      // 2019-12-30.FILE.3

      if (preg_match("/^" . self::$_file . "." . "(.*?)". "_([0-9]{4}-[0-9]{2}-[0-9]{2}).log$/si", $_file, $match)) {
        print_r($match);
        $_file_path_backups[$match[1]] = self::LOGDIR . $match[0];
      }

    }
    return $_file_path_backups;
  }

  private static function _day($file) {
    $created_date_file = date("Y-m-d", filectime($file));
    $now_date = date("Y-m-d");
    $diff = strtotime($now_date) - strtotime($created_date_file);

      // 1 day = 24 hours
      // 1 day = 24 * 60 * 60 = 86400 seconds
    return abs(round($diff / 86400));
  }

  private static function _file_path_backup_after($filepath) {
    $delimeter_index = strrpos($filepath, '_');
    $file = substr($filepath, 0, $delimeter_index);
    $date = substr($filepath, $delimeter_index);
    echo "işlem tamamlandı parçalar : $file  <--->  $date <br/>";
    return [$file, $date];
  }

  private static function _rotate() {
    echo self::$_file_path . " boyutu aştı<br/>";
    echo "<br/>rotate işlemi başlatılıyor...<br/>";

    $_file_rotate_end = self::$_file . "." . self::$_rotate;
    echo "son dosya var mı bakılıyor : " . $_file_rotate_end . "<br/>";

    if (($_file_rotate_end_path = self::_exists($_file_rotate_end))) {
      echo "son dosya siliniyor...";
      unlink($_file_rotate_end_path);
    }

    $_file_path_backups = self::_backups();
    echo "yedek loglar listeleniyor... <br/>";
    print_r($_file_path_backups);
    echo "<br/>";

    krsort($_file_path_backups);
    foreach ($_file_path_backups as $_file_index => $_file_path_backup) {
      $_file_path_backup_before = $_file_path_backup;
      echo $_file_path_backup_before . "<br/>";
      if (file_exists($_file_path_backup_before)) {
        echo $_file_path_backup_before . " > " . $_file_index . "<br/>";
        $_file_path_backup_after = substr($_file_path_backup_before, 0, strrpos($_file_path_backup_before, '_')) . "." . ($_file_index + 1);
        // rename($_file_path_backup_before, $_file_path_backup_after);
      }
    }
    echo "ilk dosya kaydırılıyor : " . self::$_file_path . "<br/>";

    list($file, $date) = self::_file_path_backup_after(self::$_file_path);

    print_r($file);
    echo "<br/>";
    print_r($date);
    echo "<br/>";

    rename(self::$_file_path, $file . ".1" . $date);
    self::$_file_path = self::_newname();
  }
}
?>