# s7e1-jdaystudio
A skeleton example project built on Symfony 7.0, by John Day.

Using some default recommended packages. Limited scope and contrived requirements. Includes solutions to a few problems I found difficult to research / solve, esp enforcing single browser access and the auth tests.

Areas touched:

Database
- Users table
- - database connection
- - initial migration (table setup)
- - doctrine EventListener (sanitizer)

Basic authentication / authorization setup
- login page
- auto logout and delete
- - using login / logout Symfony Event Subscribers
- login throttling
- single session control (only one browser at a time)
- - using custom Voter and AccessDenied Listener

Form/object validations
- including a custom validator (with optional parameter)
- user model mapped by a FormType
- alternative validation options
- - using Validation Groups
- using a placeholder attribute
- - with FormType and Twig

Routing
- basic symfony authorization zones
- a DELETE route with optional parameter
- - returns HTTP Status code only
- 1 Public, 1 Private Json API endpoint
- custom auth voter integration

Basic display and web assets usage
- nested / reusable twig templates (fragments)
- basic include of the Jquery library and setup of global var
- using separate css and javascript files for different pages

TwigExtension functions
- an App Button with
- - route-name / label / data-value parameters
- - logs errors for debug
- a sliding checkbox replacement

Custom Command
- for (re)creating of admin user
- - with console input
- - object validation based on command options

Tests
- examples of a Unit, Integration and Application test
- - using services in Integration testing
- - alternative test environment parameters
- - testing with a vhost that is NOT localhost
- - setting up an authenticated user during tests (required CompilerPassInterface)
- - following through multiple requests with a test user

Application specific functionality
- a single admin, NO self registration
- example application parameters (time limits)
- live running status and processing of users

