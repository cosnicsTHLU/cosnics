@user @courses
Feature: application.weblcms.course.glossary_viewer
  In order to view the glossary tool
  As a user
  The tool in the course needs to be accessible

  Scenario: Check if the tool Glossary is accessible
    Given I am logged in
    When I go to the tool "glossary" in the course "Testcourse 1"
    Then The page should be successfully loaded
