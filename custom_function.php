<?php

$custom_function = array(
  "name" => "generate_faq",
  "description" => "Generates a FAQ from a manual.",
  "parameters" => array(
      "type" => "object",
      "properties" => array(
          "faq_items" => array(
              "type" => "array",
              "description" => "A list of FAQ items.",
              "items" => array(
                  "type" => "object",
                  "properties" => array(
                      "question" => array(
                          "type" => "string",
                          "description" => "The question."
                      ),
                      "answer" => array(
                          "type" => "string",
                          "description" => "The answer."
                      )
                  ),
                  "required" => array("question", "answer")
              )
          )
      ),
      "required" => array("faq_items")
  )
);