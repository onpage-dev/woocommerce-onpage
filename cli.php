<?php

class ONPAGE_CLI
{
  function __construct()
  {
    op_ignore_user_scopes(true);
  }

  public function import($args, $assoc_args)
  {
    if (isset($assoc_args['halp'])) {
      echo "Use --force to force import\n";
      echo "Use --force-slug-regen to trigger slug regeneration\n";
      echo "Use --regen-snapshot to regenerate the snapshot before importing\n";
      return;
    }
    $regen_snapshot = isset($assoc_args['regen-snapshot']);
    if ($regen_snapshot) {
      $sett = op_settings();
      op_download_json("https://{$sett->company}.onpage.it/api/view/{$sett->token}/generate-snapshot") or die("Error: canot regenerate snapshot - check your settings\n");
    }

    $force_import = isset($assoc_args['force']);
    op_record("Beginning import...");
    $t1 = microtime(true);
    op_import_snapshot((bool) @$assoc_args['force-slug-regen'], (string) @$assoc_args['file_name'], !$force_import);
    $t2 = microtime(true);
    print_r([
      'log' => op_record('finish'),
      'c_count' => OpLib\Term::localized()->count(),
      'p_count' => OpLib\Post::localized()->count(),
      'time' => $t2 - $t1,
    ]);
  }
  public function reset($args, $assoc_args)
  {
    op_record("Deletion of all your On Page data is about to begin...");
    sleep(3);
    op_record("Deletion started");
    op_reset_data();
    op_record("Deletion completed");
  }
  public function listmedia($args, $assoc_args)
  {
    print_r(op_list_files());
  }
}

add_action('cli_init', function () {
  WP_CLI::add_command('onpage', 'ONPAGE_CLI');
});
