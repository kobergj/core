@cli
Feature: Change from one database to another
  As an admin
  I want to be able to convert database from one type to another
  So that I can upgrade from one database type to another


  Scenario: convert database types
    When the administrator changes the database type to "mysql"
    Then the command should have been successful
# And the command output should contain the text ""


# Scenario: converting to sqlite database type is not supported
#   When the administrator triese to change the database type to "sqlite"
#   Then the command should have failed with exit code 1
#   And the command error output should contain the text "Converting to SQLite (sqlite3) is currently not supported."


# Scenario: converting to same database type is not possible
#   Given the administrator has changed the database type to "mysql"
#   When the administrator triese to change the database type to "mysql"
#   Then the command should have failed with exit code 1
#   And the command error output should contain the text "Can not convert from mysql to mysql."
