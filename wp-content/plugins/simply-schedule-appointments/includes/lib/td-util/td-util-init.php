<?php
if ( !class_exists( 'Extended_CPT' ) ) {
	require_once dirname( __FILE__ ).'/extended-cpts/extended-cpts.php';
}

$td_util_classes = array(
	'class-td-model.php' => 'TD_Model',
	'class-td-api-model.php' => 'TD_API_Model',
	'class-td-cpt-model.php' => 'TD_CPT_Model',
	'class-td-db-model.php' => 'TD_DB_Model',

	'class-td-async-action-model.php' => 'TD_Async_Action_Model',
);

foreach ($td_util_classes as $filename => $class_name) {
	if ( !class_exists( $class_name ) ) {
		include dirname( __FILE__ ) . '/' . $filename;
	}
}