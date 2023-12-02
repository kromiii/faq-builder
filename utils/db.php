<?php

class DB
{
    private $wpdb;

    function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    function create_table()
    {
        $table_name = $this->wpdb->prefix . 'faq_builder_items';
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            question text NOT NULL,
            answer text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    function delete_table()
    {
        $table_name = $this->wpdb->prefix . 'faq_builder_items';
        $sql = "DROP TABLE IF EXISTS $table_name;";
        $this->wpdb->query($sql);
    }

    function get_faq_items()
    {
        $table_name = $this->wpdb->prefix . 'faq_builder_items';
        $sql = "SELECT * FROM $table_name";
        return $this->wpdb->get_results($sql);
    }

    function save_faq_items($faq_items)
    {
        $table_name = $this->wpdb->prefix . 'faq_builder_items';
        $this->wpdb->query("TRUNCATE TABLE $table_name");
        foreach ($faq_items as $faq_item) {
            $this->wpdb->insert(
                $table_name,
                array(
                    'question' => $faq_item['question'],
                    'answer' => $faq_item['answer'],
                )
            );
        }
    }
}
