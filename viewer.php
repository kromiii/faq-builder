<?php

function show_faq() {
    global $wpdb;

    $html = "";
    $faq_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}faq_builder_items");
    $html .= "<div class='faq-boostar'>";
    $html .= "<div class='faq-boostar__inner'>";
    foreach ($faq_items as $faq_item) {
        $html .= "<h3>";
        $html .= $faq_item->question;
        $html .= "</h3>";
        $html .= "<div class='faq-boostar__answer'>";
        $html .= "<div class='faq-boostar__answer__text'>";
        $html .= $faq_item->answer;
        $html .= "</div>";
        $html .= "</div>";
    }
    $html .= "</div>";
    $html .= "</div>";
    return $html;
}
