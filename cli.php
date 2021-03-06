<?php

class ONPAGE_CLI {

	public function import($args, $assoc_args) {
    if ($assoc_args['halp']) {
      echo "Use --force-slug-regen to force slug regeneration\n";
      return;
    }
    op_record("Beginning import...");
    $t1 = microtime(true);
    op_import_snapshot((bool) @$assoc_args['force-slug-regen'], (string) @$assoc_args['file_name']);
    $t2 = microtime(true);
    print_r([
      'log' => op_record('finish'),
      'c_count' => OpLib\Term::localized()->count(),
      'p_count' => OpLib\Post::localized()->count(),
      'time' => $t2 - $t1,
    ]);
	}
	public function reset($args, $assoc_args) {
    op_record("Deletion of all your On Page data is about to begin...");
    sleep(3);
    op_record("Deletion started");
    op_reset_data();
    op_record("Deletion completed");
	}
	public function listmedia($args, $assoc_args) {
    print_r(op_list_files());
	}
}

add_action( 'cli_init', function() {
	WP_CLI::add_command( 'onpage', 'ONPAGE_CLI' );
	WP_CLI::add_command( 'reset', 'ONPAGE_CLI' );
	WP_CLI::add_command( 'listmedia', 'ONPAGE_CLI' );
});