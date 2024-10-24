<?php

class ONPAGE_CLI
{
  function __construct()
  {
    op_ignore_user_scopes(true);
  }

  public function import($args, $assoc_args)
  {
    global $_GLOBALS;
    if (isset($assoc_args['halp'])) {
      echo "Use --force to force import\n";
      echo "Use --force-slug-regen to trigger slug regeneration\n";
      echo "Use --regen-snapshot to regenerate the snapshot before importing\n";
      echo "Use --timing to show timing info\n";
      return;
    }
    if (@$assoc_args['timing']) {
      print_r($_GLOBALS);
      $_GLOBALS['op_enable_timing_log'] = true;
    }

    op_record("Beginning import...");
    $t1 = microtime(true);
    op_import_snapshot(
      (bool) @$assoc_args['force-slug-regen'],
      (string) @$assoc_args['file_name'],
      isset($assoc_args['force']),
      isset($assoc_args['regen-snapshot'])
    );
    $t2 = microtime(true);
    print_r([
      // 'log' => op_record('finish'),
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
  public function cleanmeta($args, $assoc_args)
  {
    op_record("Deleting orphaned meta...");
    print_r(op_delete_orphan_meta());
  }
}

add_action('cli_init', function () {
  WP_CLI::add_command('onpage', 'ONPAGE_CLI');
});
