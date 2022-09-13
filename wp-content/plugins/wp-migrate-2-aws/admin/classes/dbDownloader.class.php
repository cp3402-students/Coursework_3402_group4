<?php
// https://github.com/coderatio/simple-backup

// patch applied
// src/SimpleBackup.php:442
// From: $this->tables_to_include = array_filter($tables, static function($table) {
// To: $this->tables_to_include = array_filter($tables, function($table) {

use Coderatio\SimpleBackup\SimpleBackup;
use Coderatio\SimpleBackup\Exceptions\NoTablesFoundException;

class WPM2AWS_DbDownloader
{
    /**
     * Set Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Helper method to call class method from WordPress
     *
     * @return void
     */
    public function searchAndSetOverSizedDataBaseTables()
    {
        add_action('admin_init', array( $this, 'findOverSizedDatabaseAndTables' ));
    }

    /**
     * Helper method to call class method from WordPress
     *
     * @return void
     */
    public function runDataBaseExport()
    {
        add_action('admin_init', array( $this, 'initiateDatabaseExport' ));
    }

    public function findOverSizedDatabaseAndTables()
    {
        $this->verifyDatabaseCredentials();

        $databaseSize = $this->getAndSetDatabaseSize(DB_NAME);

        $this->setLargeDatabaseWarning($databaseSize);

        if ($databaseSize > WPM2AWS_MAX_DB_EXPORT) {
            $databaseTables = $this->getDatabaseTables(DB_NAME);

            $this->findAndStoreLargeDatabaseTables($databaseTables);
        }
    }

    /** Run applicable method based on DB Size */
    public function initiateDatabaseExport()
    {
        wpm2awsLogRAction('wpm2aws-run-db-download', 'DB Download Actioned');

        $this->verifyDatabaseCredentials();

        $databaseSize = $this->getAndSetDatabaseSize(DB_NAME);

        $this->setLargeDatabaseWarning($databaseSize);

        if ($databaseSize > WPM2AWS_MAX_DB_EXPORT) {
            $databaseTables = $this->getDatabaseTables(DB_NAME);

            $this->findAndStoreLargeDatabaseTables($databaseTables);

            // Run Table Export on each
            $response = array_filter($databaseTables, array($this, 'exportDbTable'));

            if (empty($response)) {
                wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Download Complete - with Errors: No Tables Processed');
                set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Complete - No Tables Processed.', 'migrate-2-aws'));
            } elseif (count($response) === count($databaseTables)) {
                wpm2awsLogRAction('wpm2aws-run-db-download', 'Success! DB Download Complete - Table Count: ' . count($databaseTables));
                set_transient('wpm2aws_admin_success_notice_' . get_current_user_id(), __('Success!<br><br>Prepare Database Complete.', 'migrate-2-aws'));
                wpm2awsAddUpdateOptions('wpm2aws_current_active_step', 3);
            } else {
                wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Download Complete - with Errors: All Tables may not have been processed');
                set_transient('wpm2aws_admin_warning_notice_' . get_current_user_id(), __('Warning!<br><br>Prepare Database Complete - All Tables may not have been processed.', 'migrate-2-aws'));
            }

            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'success');

            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } else {
            $this->exportFullDb();
        }
    }

    /**
     * Check that all the required database credentials are available.
     *
     * @return void
     */
    private function verifyDatabaseCredentials()
    {
        if (!defined('DB_NAME')) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Name Not Defined');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Database Name not set in wp-config file.', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        if (!defined('DB_USER')) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB User Not Defined');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Database User not set in wp-config file.', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        if (!defined('DB_PASSWORD')) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Password Not Defined');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Database Password not set in wp-config file.', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        if (!defined('DB_HOST')) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Host Not Defined');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Database Host not set in wp-config file.', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        }
    }

    /**
     * Get the size of the database
     *
     * @param string $databaseName
     *
     * @return int
     */
    private function getAndSetDatabaseSize($databaseName)
    {
        $storedDatabaseSize = get_option('wpm2aws_database_size');

        if (\is_numeric($storedDatabaseSize)) {
            return (int) $storedDatabaseSize;
        }

        // Get size of DB
        try {
            $databaseSize = $this->getDatabaseSize($databaseName);
            wpm2awsAddUpdateOptions('wpm2aws_database_size', $databaseSize);
            wpm2awsLogRAction('wpm2aws-run-db-download', 'DB Size: ' . $databaseSize);
        } catch (NoTablesFoundException $e) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Get Size NoTablesFoundException');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Error (Ini.2).', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (\RuntimeException $e) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Get Size RuntimeException');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Error (Ini.3).', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (Exception $e) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Get Size Exception');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Error (Ini.4).', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (Throwable $e) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Get Size Throwable');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Error (Ini.1).', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        return (int) $databaseSize;
    }

    /**
     * Set user warning if database is large and may be problematic
     *
     * @param int $databaseSize
     *
     * @return void
     */
    private function setLargeDatabaseWarning($databaseSize)
    {
        if (get_option('wpm2aws_db_size_warning') !== false) {
            return;
        }

        if (defined('WPM2AWS_MAX_SAFE_DB_SIZE') === false) {
            return;
        }

        $roundedDatabaseSize = round($databaseSize);
        $integerMaximumSafeDatabaseSize = (int) WPM2AWS_MAX_SAFE_DB_SIZE;

        if ($roundedDatabaseSize >= $integerMaximumSafeDatabaseSize) {
            wpm2awsAddUpdateOptions('wpm2aws_db_size_warning', $databaseSize);
        }
    }

    /**
     * Get all the database tables
     *
     * @param $databaseName
     *
     * @return array|void
     */
    private function getDatabaseTables($databaseName)
    {
        try {
            $databaseTables = $this->getAllDatabaseTablesWithSizes($databaseName);
            wpm2awsLogRAction('wpm2aws-run-db-download', 'DB List Tables: Success');
        } catch (NoTablesFoundException $e) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB List Tables NoTablesFoundException');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Error (Ini.6).', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (\RuntimeException $e) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB List Tables RuntimeException');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Error (Ini.7).', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (Exception $e) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB List Tables Exception');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Error (Ini.8).', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (Throwable $e) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB List Tables Throwable');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Error (Ini.5).', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        if (empty($databaseTables)) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB List Tables Empty List');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Error (Ini.9).', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        return $databaseTables;
    }

    public function exportFullDb()
    {
        // Set the database to backup
        try {
            $simpleBackup = SimpleBackup::setDatabase(
                [
                    DB_NAME,
                    DB_USER,
                    DB_PASSWORD,
                    DB_HOST
                ]
            )->storeAfterExportTo(
                WPM2AWS_PLUGIN_DIR . '/libraries/db',
                'db.sql'
            );
        } catch (NoTablesFoundException $e) {
            wpm2awsLogAction('Error! DB Download Failed: ' . $e->getMessage());
            wpm2awsLogRAction('wpm2aws-run-db-download', 'wpm2aws_download_db_complete: Fail');
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed. (2)<br>Fatal Error', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (\RuntimeException $e) {
            wpm2awsLogAction('Error! DB Download Failed: ' . $e->getMessage());
            wpm2awsLogRAction('wpm2aws-run-db-download', 'wpm2aws_download_db_complete: Fail');
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed. (3)<br>Fatal Error', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (Exception $e) {
            wpm2awsLogAction('Error! DB Download Failed: ' . $e->getMessage());
            wpm2awsLogRAction('wpm2aws-run-db-download', 'wpm2aws_download_db_complete: Fail');
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed. (4)<br>Fatal Error', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (Throwable $e) {
            wpm2awsLogAction('Error! DB Download Failed: ' . $e->getMessage());
            wpm2awsLogRAction('wpm2aws-run-db-download', 'wpm2aws_download_db_complete: Fail');
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed. (1)<br>Fatal Error', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        try {
            $response = $simpleBackup->getResponse();
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Simple Backup Response (fdb): ' . json_encode($response));
        } catch (Exception $e) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Simple Backup Response Error: ' . $e->getMessage());
            $response = (object) array(
                'status' => false,
                'message' => 'Could Not Determine Response',
            );
        } catch (Throwable $e) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Simple Backup Response Error: ' . $e->getMessage());
            $response = (object) array(
                'status' => false,
                'message' => 'Could Not Determine Response',
            );
        }

        // Throw error if Response is not as expected
        if ('object' !== gettype($response)) {
            wpm2awsLogAction('Error! DB Download Failed - Bad Response: Not Object (fdb)' . json_encode($response));
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Download Failed - Bad Response: Not Object (fdb)' . json_encode($response));
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed. <br>Error Ref: Bad Response - No Object (fdb)', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        // Throw Error if we cannot determine the Response Status
        if (property_exists($response, 'status') === false) {
            $responseStatus = 'Status Not Found';

            // Get or Create Message
            if (property_exists($response, 'message') === true) {
                $responseMessage = $response->message;
            }
            if (property_exists($response, 'message') === false || empty($responseMessage)) {
                $responseMessage = 'Could Not Determine Response Message';
            }

            wpm2awsLogAction('Error! DB Download Failed - Bad Response: (fdb) Status=' . $responseStatus . ' | Message='  . $responseMessage . ' | Response='  . json_encode($response));
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Download Failed - Bad Response: (fdb) Status=' . $responseStatus . ' | Message='  . $responseMessage . ' | Response='  . json_encode($response));
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed. <br>Error Ref: ' . $responseMessage, 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        // Return Success or Throw Error if Status is not TRUE
        if ($response->status === true) {
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'success');
            wpm2awsLogRAction('wpm2aws-run-db-download', 'wpm2aws_download_db_complete (fdb): Success');
            set_transient('wpm2aws_admin_success_notice_' . get_current_user_id(), __('Success!<br><br>Prepare Database Complete.', 'migrate-2-aws'));
            wpm2awsAddUpdateOptions('wpm2aws_current_active_step', 3);
        } else {
            // Get or Create Message
            if (property_exists($response, 'message') === true) {
                $responseMessage = $response->message;
            }
            if (property_exists($response, 'message') === false || empty($responseMessage)) {
                $responseMessage = 'Could Not Determine Response Message';
            }

            wpm2awsLogAction('Error! DB Download Failed - Bad Response: (fdb) Status=' . $response->status . ' | Message='  . $responseMessage . ' | Response='  . json_encode($response));
            wpm2awsLogRAction('wpm2aws-run-db-download', 'wpm2aws_download_db_complete (fdb): Error: Status=' . $response->status . ' |  Message='  . $responseMessage . ' | Response='  . json_encode($response));
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed. <br>Error Ref: ' . $responseMessage, 'migrate-2-aws'));
        }

        exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
    }

    /**
     * Exports a database table
     *
     * @param $exportTable
     * @return string|null
     * @throws Exception
     */
    public function exportDbTable($exportTable)
    {
        if (\is_array($exportTable) === true) {
            $tableName = null;

            if (isset($exportTable['table']) === true) {
                $tableName = $exportTable['table'];
            }

            if ($tableName === null) {
                $passedTable = json_encode($exportTable);
                wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Download Failed: Bad Parameter: Table Name  => ' . $passedTable);
                wpm2awsLogAction('Error! DB Download Failed: Bad Parameter: Table Name  => ' . $passedTable);
                wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
                set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed. <br>Fatal Error', 'migrate-2-aws'));
                exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
            }
        } else {
            $tableName = $exportTable;
        }

        $exportTableArray = array($tableName);

        // Dev Logging
        // $exportTableString = json_encode($exportTable);
        // wpm2awsLogAction('Export Table: Table Name  => ' . $exportTableString);

        $compressionLevel = 'Gzip';

        try {
            $encode = gzencode('Test Gzip');
        } catch (Exception $e) {
            wpm2awsLogRAction('Warning! Gzip Error, defaulting to no-compression: ' . $e->getMessage());
            $compressionLevel = 'None';
        } catch (Throwable $e) {
            wpm2awsLogRAction('Error! Gzip Error, defaulting to no-compression: ' . $e->getMessage());
            $compressionLevel = 'None';
        }

        $overSizedDatabaseTables = get_option('wpm2aws_download_db_over_sized_tables');

        if (\is_array($overSizedDatabaseTables) === false) {
            $overSizedDatabaseTables = array();
        }

        $overSizedDatabaseTableNames = \array_column($overSizedDatabaseTables, 'table');


        if (\in_array($tableName, $overSizedDatabaseTableNames, true) === true) {
            $simpleBackup = $this->startLimitedTableSimpleBackup($exportTableArray, $compressionLevel);
        } else {
            $simpleBackup = $this->startFullTableSimpleBackup($exportTableArray, $compressionLevel);
        }

        try {
            $response = $simpleBackup->getResponse();
        } catch (Exception $e) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Simple Backup Response Error: ' . $e->getMessage());
            $response = (object) array(
                'status' => false,
                'message' => 'Could Not Determine Response',
            );
        } catch (Throwable $e) {
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Simple Backup Response Error: ' . $e->getMessage());
            $response = (object) array(
                'status' => false,
                'message' => 'Could Not Determine Response',
            );
        }

        // Throw error if Response is not as expected
        if ('object' !== gettype($response)) {
            wpm2awsLogAction('Error! DB Download Failed - Bad Response: Not Object ' . json_encode($response));
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Download Failed - Bad Response: Not Object ' . json_encode($response));
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed. <br>Error Ref: Bad Response - No Object', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        // Throw Error if we cannot determine the Response Status
        if (property_exists($response, 'status') === false) {
            $responseStatus = 'Status Not Found';

            // Get or Create Message
            if (property_exists($response, 'message') === true) {
                $responseMessage = $response->message;
            }
            if (property_exists($response, 'message') === false || empty($responseMessage)) {
                $responseMessage = 'Could Not Determine Response Message';
            }

            wpm2awsLogAction('Error! DB Download Failed - Bad Response: Status=' . $responseStatus . ' | Message='  . $responseMessage . ' | Response='  . json_encode($response));
            wpm2awsLogRAction('wpm2aws-run-db-download', 'Error! DB Download Failed - Bad Response:  Status=' . $responseStatus . ' | Message='  . $responseMessage . ' | Response='  . json_encode($response));
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed. <br>Error Ref: ' . $responseMessage, 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        }

        return $response->status;
    }

    /**
     * Finds all over-sized database tables & stores in option value
     *
     * @param $databaseTables
     * @return void
     */
    private function findAndStoreLargeDatabaseTables($databaseTables)
    {
        $overSizedTables = \array_filter(
            $databaseTables,
            function($table) {
                $tableSize = $table['size'];

                return $tableSize >= WPM2AWS_MAX_SAFE_DB_TABLE_SIZE;
            }
        );

        if (\count($overSizedTables) > 0) {
            wpm2awsAddUpdateOptions('wpm2aws_download_db_over_sized_tables', $overSizedTables);
        }
    }

    /**
     * Starts a backup of a database table, limited to a certain number of rows
     *
     * @param array $tableArray
     * @param string $compressionLevel
     * @return SimpleBackup|void
     */
    private function startLimitedTableSimpleBackup($tableArray, $compressionLevel)
    {
       $tableName = $tableArray[0];

       $tableLimitParameters = array($tableName => WPM2AWS_OVERSIZED_DATABASE_ROW_LIMIT);

       try {
            return SimpleBackup::start()
                ->setDbHost(DB_HOST)
                ->setDbName(DB_NAME)
                ->setDbUser(DB_USER)
                ->setDbPassword(DB_PASSWORD)
                ->setCompressionLevel($compressionLevel)
                ->includeOnly($tableArray)
                ->setTableLimitsOn($tableLimitParameters)
                ->then()->storeAfterExportTo(
                    WPM2AWS_DB_TABLES_EXPORT_PATH,
                    $tableName . '.sql'
                );
        } catch (NoTablesFoundException $e) {
            wpm2awsLogRAction('Error! DB Download Failed: ' . $e->getMessage() . ' | Table: ' . $tableArray[0] . '.sql');
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed (EDT.2). <br>Fatal Error', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (\RuntimeException $e) {
            wpm2awsLogRAction('Error! DB Download Failed: ' . $e->getMessage() . ' | Table: ' . $tableArray[0] . '.sql');
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed (EDT.3). <br>Fatal Error', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (Exception $e) {
            wpm2awsLogRAction('Error! DB Download Failed: ' . $e->getMessage() . ' | Table: ' . $tableArray[0] . '.sql');
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed (EDT.4). <br>Fatal Error', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (Throwable $e) {
           wpm2awsLogRAction('Error! DB Download Failed: ' . $e->getMessage() . ' | Table: ' . $tableArray[0] . '.sql');
           wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
           set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed (EDT.1). <br>Fatal Error', 'migrate-2-aws'));
           exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
       }
    }

    /**
     * Starts a backup of a complete database table (no limit on rows)
     *
     * @param array $tableArray
     * @param string $compressionLevel
     * @return SimpleBackup|void
     */
    private function startFullTableSimpleBackup($tableArray, $compressionLevel)
    {
        $tableName = $tableArray[0];

        try {
            return SimpleBackup::start()
                ->setDbHost(DB_HOST)
                ->setDbName(DB_NAME)
                ->setDbUser(DB_USER)
                ->setDbPassword(DB_PASSWORD)
                ->setCompressionLevel($compressionLevel)
                ->includeOnly($tableArray)
                ->then()->storeAfterExportTo(
                    WPM2AWS_DB_TABLES_EXPORT_PATH,
                    $tableName . '.sql'
                );
        } catch (NoTablesFoundException $e) {
            wpm2awsLogRAction('Error! DB Download Failed: ' . $e->getMessage() . ' | Table: ' . $tableArray[0] . '.sql');
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed (EDT.2). <br>Fatal Error', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (\RuntimeException $e) {
            wpm2awsLogRAction('Error! DB Download Failed: ' . $e->getMessage() . ' | Table: ' . $tableArray[0] . '.sql');
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed (EDT.3). <br>Fatal Error', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (\Exception $e) {
            wpm2awsLogRAction('Error! DB Download Failed: ' . $e->getMessage() . ' | Table: ' . $tableArray[0] . '.sql');
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed (EDT.4). <br>Fatal Error', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        } catch (Throwable $e) {
            wpm2awsLogRAction('Error! DB Download Failed: ' . $e->getMessage() . ' | Table: ' . $tableArray[0] . '.sql');
            wpm2awsAddUpdateOptions('wpm2aws_download_db_complete', 'error');
            set_transient('wpm2aws_admin_error_notice_' . get_current_user_id(), __('Error!<br><br>Prepare Database Failed (EDT.1). <br>Fatal Error', 'migrate-2-aws'));
            exit(wp_safe_redirect(admin_url('/admin.php?page=wpm2aws')));
        }
    }

    private function getDatabaseSize($dbName)
    {
        global $wpdb;

        $sql = 'SELECT table_schema AS "Database", 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS "Size (MB)" 
        FROM information_schema.TABLES 
        WHERE table_schema = "' . $dbName . '"
        GROUP BY table_schema';

        try {
            $result = $wpdb->get_results($sql, ARRAY_A);
        } catch (\RuntimeException $e) {
            wpm2awsLogRAction('Error! DB Download Failed: Get Database Size: ' . $e->getMessage());
            throw new Exception('Error Getting Database Sizes');
        } catch (\Exception $e) {
            wpm2awsLogRAction('Error! DB Download Failed: Get Database Size: ' . $e->getMessage());
            throw new Exception('Error Getting Database Size');
        } catch (Throwable $e) {
            wpm2awsLogRAction('Error! DB Download Failed: Get Database Size: ' . $e->getMessage());
            throw new Exception('Error Listing Tables');
        }

        if (\array_key_exists(0, $result) === false) {
            return 0;
        }

        $firstRecord = $result[0];

        if (\is_array($firstRecord) === false) {
            return 0;
        }

        if (\array_key_exists('Size (MB)', $firstRecord) === false) {
            return 0;
        }

        return $firstRecord['Size (MB)'];
    }

    /**
     * Get the size in MB of all database tables
     *
     * @param $databaseName
     * @return array
     * @throws Exception
     */
    private function getAllDatabaseTablesWithSizes($databaseName)
    {
        global $wpdb;

        $sql = "SELECT 
        table_name AS 'table',
        round(((data_length + index_length) / 1024 / 1024), 2) 'size' 
        FROM information_schema.TABLES 
        WHERE table_schema = '" . $databaseName . "'
        ORDER BY (data_length + index_length) DESC;";

        try {
            $result = $wpdb->get_results($sql, ARRAY_A);
        } catch (\RuntimeException $e) {
            wpm2awsLogRAction('Error! DB Download Failed: Get All Database Tables With Size: ' . $e->getMessage());
            throw new Exception('Error Getting Database Table Sizes');
        } catch (Exception $e) {
            wpm2awsLogRAction('Error! DB Download Failed: Get All Database Tables With Size: ' . $e->getMessage());
            throw new Exception('Error Getting Database Table Sizes');
        } catch (Throwable $e) {
            wpm2awsLogRAction('Error! DB Download Failed: Get All Database Tables With Size: ' . $e->getMessage());
            throw new Exception('Error Getting Database Table Sizes');
        }

        return $result;
    }
}
