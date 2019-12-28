<?php

Application::config(function() {

  set("timezone", "Europe/Istanbul");
  set("debug", true);
  set("locale", "tr");

  //  set("logger", "production.log");

  // modules(["cacher", "mailer"]);
  modules(["cacher", "mailer", "model"]);
  // set("cacher", false);
  // set("model", false);
  // set("mailer", false);
});

?>