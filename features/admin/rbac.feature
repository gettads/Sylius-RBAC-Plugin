@rbac_admin
Feature: Implement shipping restriction policy
    In order to have an overview of my policies
    As an Administrator with super_admin role
    I want to be able to give routes permissions for other Administrators with admin role

    Background:
        Given the store operates on a single channel in "United States"
        And there is an administrator "super_admin@test.test" identified by "pass"
        And there is an administrator "admin@test.test" identified by "pass"
        And Admin "super_admin@test.test" has a role "super_admin"
        And Admin "admin@test.test" has a role "admin"
        And role "admin" has access to all RBAC routes

    @ui
    Scenario: Admin can get access to all RBAC routes (before restriction).
        When I am logged in as "admin@test.test" administrator
        Then I can open pages "index,create,update,delete" for role "admin"
        And I can create new "test" role with "admin" role as a parent
        And I can add all permissions for "test" role
        And I can delete "test" role

    @ui
    Scenario: Admin can not get access to INDEX route (restricted by super_admin).
        Given I am logged in as "super_admin@test.test" administrator
        And I can open pages "update" for role "admin"
        And I set permissions "create,delete,update" only for "admin"
        And I have been logged out from administration
        When I am logged in as "admin@test.test" administrator
        Then I can open pages "create,update" only for role "admin"
        And I can not open "index" pages for role "admin"

    @ui
    Scenario: Admin can not get access to CREATE route (restricted by super_admin).
        Given I am logged in as "super_admin@test.test" administrator
        And I can open pages "update" for role "admin"
        And I set permissions "index,delete,update" only for "admin"
        And I have been logged out from administration
        When I am logged in as "admin@test.test" administrator
        Then I can open pages "index,delete,update" only for role "admin"
        And I can not open "create" pages for role "admin"

    @ui
    Scenario: Admin can not get access to UPDATE route (restricted by super_admin).
        Given I am logged in as "super_admin@test.test" administrator
        And I can open pages "update" for role "admin"
        And I set permissions "index,create,delete" only for "admin"
        And I have been logged out from administration
        When I am logged in as "admin@test.test" administrator
        Then I can open pages "index,create,delete" only for role "admin"
        And I can not open "update" pages for role "admin"

    @ui
    Scenario: Admin can not get access to DELETE route form (restricted by super_admin).
        Given I am logged in as "super_admin@test.test" administrator
        And I can open pages "update" for role "admin"
        And I set permissions "index,create,update" only for "admin"
        And I have been logged out from administration
        When I am logged in as "admin@test.test" administrator
        Then I can open pages "index,create,update" only for role "admin"
        And I can not open "delete" pages for role "admin"

    @ui
    Scenario: Admin can not get access to DELETE route if form was opened before, but permission was deleted right now.
        Given I am logged in as "admin@test.test" administrator
        When I can open pages "delete" for role "admin"
        And "delete" permission was removed from "admin" role
        Then I can not execute deleting of "admin" role
